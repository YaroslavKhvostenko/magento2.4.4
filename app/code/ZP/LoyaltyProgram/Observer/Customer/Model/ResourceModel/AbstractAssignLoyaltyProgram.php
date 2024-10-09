<?php
declare(strict_types=1);

namespace ZP\LoyaltyProgram\Observer\Customer\Model\ResourceModel;

use Magento\Customer\Model\Data\Customer;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use ZP\LoyaltyProgram\Api\LoyaltyProgramManagementInterface;
use ZP\LoyaltyProgram\Model\Configs\Program\Scope\Config as ProgramScopeConfig;
use Magento\Customer\Api\Data\CustomerExtensionInterfaceFactory;
use ZP\LoyaltyProgram\Api\LoyaltyProgramRepositoryInterface;
use ZP\LoyaltyProgram\Model\Validators\Data\Validator;

abstract class AbstractAssignLoyaltyProgram
{
    public function __construct(
        protected LoyaltyProgramManagementInterface $loyaltyProgramManagement,
        protected StoreManagerInterface $storeManager,
        protected ProgramScopeConfig $programScopeConfig,
        protected CustomerExtensionInterfaceFactory $customerExtensionFactory,
        protected LoyaltyProgramRepositoryInterface $loyaltyProgramRepository,
        protected Validator $dataValidator
    ) {}

    /**
     * @param Observer $observer
     * @throws \Exception
     */
    public function execute(Observer $observer)
    {
        if ($this->isProgramEnabled()) {
            $this->processCustomer($this->getCustomer($observer));
        }
    }

    /**
     * @return bool
     * @throws LocalizedException
     */
    private function isProgramEnabled(): bool
    {
        return $this->programScopeConfig->isEnabled((int)$this->storeManager->getWebsite()->getId());
    }

    /**
     * @param Customer $customer
     * @throws \Exception
     */
    private function processCustomer(Customer $customer): void
    {
        $customerId = $customer->getId();

        if ($customerId === null || !$this->dataValidator->isDataInteger($customerId)) {
            throw new \Exception('Wrong data type of customer_id!');
        }

        $customerProgramId = $this->getCustomerLoyaltyProgramId($customer);

        if ($customerProgramId !== null && $this->dataValidator->isDataInteger($customerProgramId)) {
            $this->handleLoyaltyProgram($customer, (int)$customerProgramId);
        }
    }

    private function getCustomerLoyaltyProgramId(Customer $customer): ?int
    {
        $customerExtension = $customer->getExtensionAttributes() ?: $this->customerExtensionFactory->create();
        return $customerExtension->getLoyaltyProgramId();
    }

    /**
     * @param Customer $customer
     * @param int $customerProgramId
     * @throws \Exception
     */
    private function handleLoyaltyProgram(Customer $customer, int $customerProgramId): void
    {
        try {
            $customerProgram = $this->loyaltyProgramRepository->get($customerProgramId);
        } catch (NoSuchEntityException $exception) {
            return; // Do nothing if the program does not exist
        }

        if (!$customerProgram || !$customerProgram->getIsActive()) {
            $this->loyaltyProgramManagement->assignLoyaltyProgram($customer);
        }
    }

    abstract protected function getCustomer(Observer $observer): Customer;
}
