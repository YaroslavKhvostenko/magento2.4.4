<?php
declare(strict_types=1);

namespace ZP\LoyaltyProgram\Plugin\SalesRule\Model;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerExtensionInterface;
use Magento\Customer\Api\Data\CustomerExtensionInterfaceFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\SalesRule\Model\Utility;
use Magento\SalesRule\Model\Rule;
use Magento\SalesRule\Model\Data\Rule as RuleDataModel;
use Magento\Quote\Model\Quote\Address;
use Magento\Customer\Model\Data\Customer;
use Magento\Store\Model\StoreManagerInterface;
use ZP\LoyaltyProgram\Model\LoyaltyProgram;
use ZP\LoyaltyProgram\Api\LoyaltyProgramRepositoryInterface;
use ZP\LoyaltyProgram\Model\Prepare\Data\DataPreparer;
use ZP\LoyaltyProgram\Model\Validators\Data\Validator;
use ZP\LoyaltyProgram\Plugin\SalesRule\Model\Rule\DataProviderPlugin as RuleProgramConfig;
use ZP\LoyaltyProgram\Api\LoyaltyProgramManagementInterface;
use Magento\Customer\Model\Group;

class UtilityPlugin
{
    public const WEBSITE_IDS = 'website_ids';
    public const CUSTOMER_GROUP_IDS = 'customer_group_ids';
    public const LOYALTY_PROGRAM_IDS = 'loyalty_program_ids';

    private array $conditionsResult = [
        self::WEBSITE_IDS => false,
        self::CUSTOMER_GROUP_IDS => false,
        self::LOYALTY_PROGRAM_IDS => false
    ];
    private array $ruleData = [
        self::WEBSITE_IDS => [],
        self::CUSTOMER_GROUP_IDS => [],
        self::LOYALTY_PROGRAM_IDS => []
    ];
    private array $customerData = [
        self::WEBSITE_IDS => 0,
        self::CUSTOMER_GROUP_IDS => 0,
        self::LOYALTY_PROGRAM_IDS => 0
    ];
    private array $loyaltyProgramData = [
        self::WEBSITE_IDS => 0,
        self::CUSTOMER_GROUP_IDS => []
    ];

    public function __construct(
        private CustomerRepositoryInterface $customerRepository,
        private CustomerExtensionInterfaceFactory $customerExtensionFactory,
        private LoyaltyProgramRepositoryInterface $loyaltyProgramRepository,
        private StoreManagerInterface $storeManager,
        private LoyaltyProgramManagementInterface $loyaltyProgramManagement,
        private DataPreparer $prepareData,
        private Validator $dataValidator
    ) {}

    /**
     * @param Utility $subject
     * @param bool $result
     * @param Rule $rule
     * @param Address $address
     * @return bool
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws \Exception
     */
    public function afterCanProcessRule(Utility $subject, bool $result, Rule $rule, Address $address): bool
    {
        if (!$result || !$rule->getData(RuleProgramConfig::IS_LOYALTY_RULE)) {
            return $result;
        }

        $customerId = $address->getCustomerId();
        if ($customerId === null || $customerId === false) {
            $quote = $address->getQuote();
            $websiteId = $this->storeManager->getWebsite()->getId();
            if (!$this->dataValidator->isDataInteger($websiteId)) {
                return $this->returnFalse($rule, $address);
            }

            $websiteId = (int)$websiteId;
            $groupId = Group::NOT_LOGGED_IN_ID;
            if (!$this->loyaltyProgramManagement->checkProgramConditions($quote, $websiteId, $groupId)) {
                return $this->returnFalse($rule, $address);
            }

            $customerProgram = $this->loyaltyProgramManagement->returnCustomerProgram();
        } else {
            if (!$this->dataValidator->isDataInteger($customerId)) {
                return $this->returnFalse($rule, $address);
            }

            $customer = $this->getCustomer((int)$customerId);
            if (!$this->validateCustomerData($customer)) {
                $this->returnFalse($rule, $address);
            }

            $websiteId = (int)$customer->getWebsiteId();
            $groupId = (int)$customer->getGroupId();

            $customerProgram = $this->getOrAssignLoyaltyProgram($customer);
        }

        if (!$this->validateLoyaltyProgram($customerProgram, $groupId, $websiteId, $rule)) {
            return $this->returnFalse($rule, $address);
        }

        return $this->returnTrue($rule, $address);
    }

    /**
     * @param $customer
     * @return LoyaltyProgram|null
     * @throws \Exception
     */
    private function getOrAssignLoyaltyProgram($customer): ?LoyaltyProgram
    {
        $customerLoyaltyProgram = $this->getCustomerProgram($customer);

        if ($customerLoyaltyProgram && !$customerLoyaltyProgram->getIsActive()) {
            $customerLoyaltyProgram = $this->loyaltyProgramManagement->assignLoyaltyProgram($customer);
        }

        return $customerLoyaltyProgram;
    }

    private function validateCustomerData($customer): bool
    {
        $websiteId = $customer->getWebsiteId();
        $groupId = $customer->getGroupId();
        if ($this->dataValidator->isDataInteger($websiteId) && $this->dataValidator->isDataInteger($groupId)) {
            return true;
        }

        return false;
    }

    private function compareGroups(
        int $customerGroupId,
        array $ruleCustomerGroupsIds,
        array $programCustomerGroupIds,
    ): bool {
        if (in_array($customerGroupId, $ruleCustomerGroupsIds) && in_array($customerGroupId, $programCustomerGroupIds)) {
            return (bool)array_uintersect($programCustomerGroupIds, $ruleCustomerGroupsIds, "strcasecmp");
        }

        return false;
    }

    private function compareWebsites(
        int $customerWebsiteId,
        int $programWebsiteId,
        array $ruleWebsiteIds
    ): bool {
        return $customerWebsiteId === $programWebsiteId && in_array($customerWebsiteId, $ruleWebsiteIds);
    }

    private function compareLoyaltyPrograms(int $customerProgramId, array $ruleProgramIds): bool
    {
        return in_array($customerProgramId, $ruleProgramIds);
    }

    private function returnTrue(Rule $rule, Address $address): bool
    {
        return $this->returnResult($rule, $address, true);
    }

    private function returnFalse(Rule $rule, Address $address): bool
    {
        return $this->returnResult($rule, $address, false);
    }

    private function returnResult(Rule $rule, Address $address, bool $result): bool
    {
        $rule->setIsValidForAddress($address, $result);
        return $result;
    }

    /**
     * @param Rule $rule
     * @return bool
     * @throws \Exception
     */
    private function setRuleDataProperty(Rule $rule): bool
    {
        $customerGroupsIds = $this->getMultiselectFieldIntData(
            $rule->getCustomerGroupIds(), RuleDataModel::KEY_CUSTOMER_GROUPS, 'SalesRule'
        );

        $websiteIds = $this->getMultiselectFieldIntData(
            $rule->getWebsiteIds(), RuleDataModel::KEY_WEBSITES, 'SalesRule'
        );

        $loyaltyProgramIds = $this->getMultiselectFieldIntData(
            $rule->getData(RuleProgramConfig::LOYALTY_PROGRAM_IDS),
            RuleProgramConfig::LOYALTY_PROGRAM_IDS,
            'SalesRule'
        );

        if (!$loyaltyProgramIds || !$websiteIds || !$customerGroupsIds) {
            return false;
        }

        $this->ruleData[self::WEBSITE_IDS] = $websiteIds;
        $this->ruleData[self::CUSTOMER_GROUP_IDS] = $customerGroupsIds;
        $this->ruleData[self::LOYALTY_PROGRAM_IDS] = $loyaltyProgramIds;

        return true;
    }

    /**
     * @param mixed $data
     * @param string $fieldName
     * @param string $entityName
     * @return array
     * @throws \Exception
     */
    private function getMultiselectFieldIntData(mixed $data, string $fieldName, string $entityName): array
    {
        $data = $this->dataValidator->validateMultiselectFieldIntData($data, $fieldName, $entityName);

        return $this->prepareData->makeArrayKeysLikeValues($this->prepareData->arrayValuesToInteger($data));
    }

    private function setCustomerDataProperty(int $customerGroupId, int $websiteId, int $loyaltyProgramId): bool
    {
        $this->customerData[self::WEBSITE_IDS] = $websiteId;
        $this->customerData[self::CUSTOMER_GROUP_IDS] = $customerGroupId;
        $this->customerData[self::LOYALTY_PROGRAM_IDS] = $loyaltyProgramId;

        return true;
    }

    /**
     * @param LoyaltyProgram|null $loyaltyProgram
     * @return bool
     * @throws \Exception
     */
    private function setLoyaltyProgramDataProperty(?LoyaltyProgram $loyaltyProgram): bool
    {
        if (!$loyaltyProgram) {
            return false;
        }

        $customerGroupsIds = $this->prepareData->makeArrayKeysLikeValues($loyaltyProgram->getCustomerGroupIds());
        $websiteId = $loyaltyProgram->getWebsiteId();
        if (!$customerGroupsIds || $websiteId === null) {
            return false;
        }

        $this->loyaltyProgramData[self::CUSTOMER_GROUP_IDS] = $customerGroupsIds;
        $this->loyaltyProgramData[self::WEBSITE_IDS] = $websiteId;
        return true;
    }

    /**
     * @param int $customerId
     * @return Customer
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function getCustomer(int $customerId): Customer
    {
        return $this->customerRepository->getById($customerId);
    }

    private function getCustomerProgram(Customer $customer): ?LoyaltyProgram
    {
        try {
            $extensionAttributes = $customer->getExtensionAttributes();
            if ($extensionAttributes === null) {
                $extensionAttributes = $this->customerExtensionFactory->create();
            }
            /** @var CustomerExtensionInterface $extensionAttributes */

            $loyaltyProgramId = $extensionAttributes->getLoyaltyProgramId();
            if ($loyaltyProgramId !== null || $loyaltyProgramId !== false) {
                return $this->loyaltyProgramRepository->get((int)$loyaltyProgramId);
            }

            return null;
        } catch (\Exception $exception) {
            return null;
        }
    }

    /**
     * @param LoyaltyProgram $customerProgram
     * @param int $groupId
     * @param int $websiteId
     * @param Rule $rule
     * @return bool
     * @throws \Exception
     */
    private function validateLoyaltyProgram(
        ?LoyaltyProgram $customerProgram,
        int $groupId,
        int $websiteId,
        Rule $rule
    ): bool {
        return $this->setLoyaltyProgramDataProperty($customerProgram) &&
            $this->setCustomerDataProperty($groupId, $websiteId, $customerProgram->getProgramId()) &&
            $this->setRuleDataProperty($rule) &&
            $this->checkConditionsResult();
    }

    private function checkConditionsResult(): bool
    {
        foreach ($this->conditionsResult as $key => $value) {
            $ruleData = $this->ruleData[$key];
            $customerData = $this->customerData[$key];
            $loyaltyProgramData = $this->loyaltyProgramData[$key] ?? null;
            $this->conditionsResult[$key] = $this->compareConditionData($key, $ruleData, $customerData, $loyaltyProgramData);
        }

        return !in_array(false, $this->conditionsResult);
    }

    private function compareConditionData(string $fieldType ,$ruleData, $customerData, $programData = null): bool
    {
        return match ($fieldType) {
            self::CUSTOMER_GROUP_IDS => $this->compareGroups($customerData, $ruleData, $programData),
            self::WEBSITE_IDS => $this->compareWebsites($customerData, $programData, $ruleData),
            self::LOYALTY_PROGRAM_IDS => $this->compareLoyaltyPrograms($customerData, $ruleData),
            default => false
        };
    }
}
