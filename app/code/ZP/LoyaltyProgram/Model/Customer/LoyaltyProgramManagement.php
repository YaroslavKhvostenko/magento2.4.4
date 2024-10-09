<?php
declare(strict_types=1);

namespace ZP\LoyaltyProgram\Model\Customer;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
use ZP\LoyaltyProgram\Api\Data\LoyaltyProgramInterface;
use ZP\LoyaltyProgram\Api\LoyaltyProgramManagementInterface;
use ZP\LoyaltyProgram\Api\LoyaltyProgramRepositoryInterface;
use ZP\LoyaltyProgram\Model\LoyaltyProgram;
use ZP\LoyaltyProgram\Model\ResourceModel\LoyaltyProgram\CollectionFactory as ProgramCollectionFactory;
use ZP\LoyaltyProgram\Model\ResourceModel\LoyaltyProgram\Collection as ProgramCollection;
use Magento\Quote\Model\Quote;
use ZP\LoyaltyProgram\Model\Configs\Program\Scope\Config as ProgramScopeConfig;
use ZP\LoyaltyProgram\Model\Configs\Customer\Program\Config as CustomerProgramConfig;
use Psr\Log\LoggerInterface;
use Magento\Sales\Model\Order as OrderConfig;
use ZP\LoyaltyProgram\Model\Prepare\Data\DataPreparer;
use ZP\LoyaltyProgram\Api\Model\Validators\Controller\Adminhtml\Program\ValidatorInterface;
use ZP\LoyaltyProgram\Model\MessageManager\Customer\LoyaltyProgramManagement\MessageManager;

class LoyaltyProgramManagement implements LoyaltyProgramManagementInterface
{
    private string $result;
    private int $customerId;
    private int $customerWebsiteId;
    private int $customerGroupId;
    private AdapterInterface $connection;
    private ?LoyaltyProgram $customerProgram;
    private ?LoyaltyProgram $programToAssign = null;


    public function __construct(
        private ResourceConnection $resourceConnection,
        private ProgramCollectionFactory $programCollectionFactory,
        private ProgramScopeConfig $programScopeConfig,
        private LoyaltyProgramRepositoryInterface $loyaltyProgramRepository,
        private DataPreparer $prepareData,
        private LoggerInterface $logger,
        private ValidatorInterface $dataValidator
    ) {
        $this->connection = $this->resourceConnection->getConnection();
    }

    /**
     * @param CustomerInterface $customer
     * @return LoyaltyProgramInterface|null
     * @throws \Exception
     */
    public function assignLoyaltyProgram(CustomerInterface $customer): ?LoyaltyProgramInterface
    {
        try {
            $this->customerProgram = null;
            $this->setCustomerData($customer);
            $customerProgramId = $this->getCustomerProgramId($customer);
            if (!$this->checkProgramConditions($customer, $this->customerWebsiteId, $this->customerGroupId)) {
                if ($customerProgramId !== null) {
                    $this->deleteCustomerProgram();
                    $this->result = MessageManager::REMOVED;
                }

                return $this->returnCustomerProgram();
            }

            $this->executeProgramAssignment($customer, $customerProgramId);

            $this->programToAssign = null;

            return $this->returnCustomerProgram();
        } catch (\Exception $exception) {
            $this->logger->notice($exception->getMessage());

            return $this->returnCustomerProgram();
        }
    }

    private function setCustomerData(CustomerInterface $customer): void
    {
        if (!$this->customerExist($customer)) {
            throw new \Exception('Please specify current customer ID, GroupId and WebsiteId');
        }

        $this->customerId = (int)$customer->getId();
        $this->customerGroupId = (int)$customer->getGroupId();
        $this->customerWebsiteId = (int)$customer->getWebsiteId();
    }

    private function customerExist(CustomerInterface $customer): bool
    {
        return $this->dataValidator->isDataInteger($customer->getId()) &&
                $this->dataValidator->isDataInteger($customer->getGroupId()) &&
                $this->dataValidator->isDataInteger($customer->getWebsiteId());
    }

    /**
     * @param CustomerInterface $customer
     * @return int|null
     * @throws NoSuchEntityException
     * @throws \Exception
     */
    private function getCustomerProgramId(CustomerInterface $customer): ?int
    {
        $programId = $customer->getExtensionAttributes()->getLoyaltyProgramId();
        $programId = $programId ? (int)$programId : $this->selectCustomerProgram();

        if ($programId !== null) {
            $customerProgram = $this->loyaltyProgramRepository->get($programId);
            if ($customerProgram->getIsActive()) {
                $this->setCustomerProgram($customerProgram);
            }
        }

        return $programId;
    }

    private function selectCustomerProgram(): ?int
    {
        $select = $this->connection->select()
            ->from(CustomerProgramConfig::CUSTOMER_PROGRAM_TABLE, CustomerProgramConfig::PROGRAM_ID)
            ->where(CustomerProgramConfig::CUSTOMER_ID . ' = ?', $this->customerId);
        $customerProgram = $this->connection->fetchOne($select);

        return $customerProgram !== null && $customerProgram !== false && $customerProgram !== '' ? (int)$customerProgram : null;
    }

    private function setCustomerProgram(LoyaltyProgramInterface $loyaltyProgram): void
    {
        $this->customerProgram = $loyaltyProgram;
    }

    /**
     * @param CustomerInterface|Quote $entity
     * @param int $websiteId
     * @param int $customerGroupId
     * @return bool
     * @throws \Exception
     */
    public function checkProgramConditions(
        CustomerInterface|Quote $entity,
        int $websiteId,
        int $customerGroupId
    ): bool {
        if (!$this->programScopeConfig->isEnabled($websiteId)) {
            return false;
        }

        $grandTotal = $this->getEntityGrandTotal($entity);
        /** @var LoyaltyProgram[] $programs */
        $programs = $this->getPrograms();
        if (!$programs) {
            return false;
        }

        if (!$this->validateProgramsToAssign($programs, $grandTotal, $websiteId, $customerGroupId)) {
            $this->result = MessageManager::UNABLE;

            return false;
        }

        $this->setCustomerProgram($this->programToAssign);

        return true;
    }

    /**
     * @param CustomerInterface|Quote $entity
     * @return float
     */
    private function getEntityGrandTotal(CustomerInterface|Quote $entity): float
    {
        return $entity instanceof CustomerInterface ? $this->getCustomerGrandTotal((int)$entity->getWebsiteId()) :
            (float)$entity->getGrandTotal();
    }

    private function getCustomerGrandTotal(int $websiteId): float
    {
        $select = $this->connection->select()
            ->from('sales_order', 'SUM(' . OrderConfig::GRAND_TOTAL . ')')
            ->where(OrderConfig::CUSTOMER_ID . ' = ?', $this->customerId);

        if ($this->programScopeConfig->isApplySubtotalChangesAfterInvoice($websiteId)) {
            $select->where(OrderConfig::STATE . ' = ?', OrderConfig::STATE_PROCESSING);
        }

        $grandTotal = $this->connection->fetchOne($select);

        return $grandTotal ? (float)$grandTotal : 0.0;
    }

    /**
     * @return array
     */
    private function getPrograms(): array
    {
        return $this->programCollectionFactory->create()
            ->excludeBasicPrograms()
            ->addFieldToFilter(LoyaltyProgram::IS_ACTIVE, LoyaltyProgram::ACTIVE)
            ->addFieldToFilter(LoyaltyProgram::ORDER_SUBTOTAL, ['neq' => 'NULL'])
            ->setOrder(LoyaltyProgram::ORDER_SUBTOTAL, ProgramCollection::SORT_ORDER_ASC)
            ->getItems();
    }

    /**
     * @param array $programs
     * @param float $grandTotal
     * @param int $websiteId
     * @param int $customerGroupId
     * @return bool
     * @throws \Exception
     */
    private function validateProgramsToAssign(
        array $programs,
        float $grandTotal,
        int $websiteId,
        int $customerGroupId
    ): bool {
        return $this->validateGrandTotal($programs, $grandTotal) &&
               $this->validateWebsites($websiteId, $this->programToAssign) &&
               $this->validateCustomerGroups($customerGroupId, $this->programToAssign);
    }

    /**
     * @param array $programs
     * @param float $entityGrandTotal
     * @return bool
     * @throws \Exception
     */
    private function validateGrandTotal(array $programs, float $entityGrandTotal): bool
    {
        $programIdsArray = $this->prepareData->makeArrayValuesLikeKeys($programs);
        foreach ($programs as $programId => $program) {
            unset($programIdsArray[$programId]);
            $programOrderSubtotal = $this->getProgramOrderSubtotal($program);
            $nextProgramOrderSubtotal = $this->getNextProgramOrderSubtotal($programIdsArray, $programs, $program);

            if ($this->compareGrandTotals($entityGrandTotal, $programOrderSubtotal, $nextProgramOrderSubtotal)) {
                $this->programToAssign = $program;
            }
        }

        return (bool)$this->programToAssign;
    }

    /**
     * @param LoyaltyProgram $program
     * @return float|null
     * @throws \Exception
     */
    private function getProgramOrderSubtotal(LoyaltyProgram $program): ?float
    {
        $orderSubtotal = $program->getOrderSubtotal();
        $programId = $program->getProgramId();
        if ($orderSubtotal === null && !$this->dataValidator->isBasicProgram($programId)) {
            $this->addLogMsg($program, LoyaltyProgram::ORDER_SUBTOTAL);
        }

        return $orderSubtotal ? (float)$orderSubtotal : null;
    }

    private function addLogMsg(LoyaltyProgram $program, string $field): void
    {
        $this->logger->notice(
            'Program \'' . $program->getProgramName() . '\' ' .
            'with id : ' . '\'' . $program->getProgramId() . '\', doesn\'t have ' . '\'' . $field . '\' ' . 'data, ' .
            'it is NULL. Update this program please!'
        );
    }

    private function compareGrandTotals(
        float $entityGrandTotal,
        ?float $programSubtotal,
        ?float $nextProgramSubtotal = null
    ): bool {
        if ($nextProgramSubtotal === null) {
            return $entityGrandTotal >= $programSubtotal;
        }

        if (!($entityGrandTotal >= $programSubtotal && $entityGrandTotal < $nextProgramSubtotal)) {
            return $entityGrandTotal >= $programSubtotal && $entityGrandTotal > $nextProgramSubtotal;
        }

        return true;
    }

    /**
     * @param array $programIds
     * @param array $programs
     * @param LoyaltyProgram $program
     * @return float|null
     * @throws \Exception
     */
    private function getNextProgramOrderSubtotal(array $programIds, array $programs, LoyaltyProgram $program): ?float
    {
        $nextProgramId = $program->getNextProgram();
        if ($nextProgramId === null) {
            $nextProgramOrderSubtotal = $this->getSubtotalFromFirstArrayElement($programIds, $programs);
            $this->addLogMsg($program, LoyaltyProgram::NEXT_PROGRAM);
        } else {
            if (array_key_exists($nextProgramId, $programs)) {
                $nextProgramOrderSubtotal = $this->getProgramOrderSubtotal($programs[$nextProgramId]);
            } else {
                try {
                    $nextProgram = $this->loyaltyProgramRepository->get($nextProgramId);
                    $nextProgramOrderSubtotal = $this->getProgramOrderSubtotal($nextProgram);
                } catch (NoSuchEntityException $exception) {
                    $nextProgramOrderSubtotal = null;
                }

                if ($nextProgramOrderSubtotal === null) {
                    $nextProgramOrderSubtotal = $this->getSubtotalFromFirstArrayElement($programIds, $programs);
                }
            }
        }

        return $nextProgramOrderSubtotal;
    }

    /**
     * @param array $programIds
     * @param array $programs
     * @return float|null
     * @throws \Exception
     */
    private function getSubtotalFromFirstArrayElement(array $programIds, array $programs): ?float
    {
        return $programIds ? $this->getProgramOrderSubtotal($programs[array_key_first($programIds)]) : null;
    }

    /**
     * @param int $customerWebsiteId
     * @param LoyaltyProgram $loyaltyProgram
     * @return bool
     * @throws \Exception
     */
    private function validateWebsites(int $customerWebsiteId, LoyaltyProgram $loyaltyProgram): bool
    {
        return $customerWebsiteId === $loyaltyProgram->getWebsiteId();
    }

    /**
     * @param int $customerGroupId
     * @param LoyaltyProgram $loyaltyProgram
     * @return bool
     * @throws \Exception
     */
    private function validateCustomerGroups(int $customerGroupId, LoyaltyProgram $loyaltyProgram): bool
    {
        return in_array(
            $customerGroupId,
            $this->prepareData->makeArrayKeysLikeValues($loyaltyProgram->getCustomerGroupIds())
        );
    }

    public function returnResult(): string
    {
        return $this->result;
    }

    /**
     * @param CustomerInterface $customer
     * @param int|null $customerProgramId
     * @throws \Exception
     */
    private function executeProgramAssignment(
        CustomerInterface $customer,
        ?int $customerProgramId
    ): void {
        $programIdToAssign = $this->programToAssign->getProgramId();
        if ($customerProgramId && $customerProgramId === $programIdToAssign) {
            $this->result = MessageManager::NO_NEED;

            return;
        }

        $condition = null;
        $data = [
            CustomerProgramConfig::PROGRAM_ID => $programIdToAssign,
            CustomerProgramConfig::CUSTOMER_EMAIL => $customer->getEmail()
        ];

        if (!$customerProgramId) {
            $data = array_merge($data, [CustomerProgramConfig::CUSTOMER_ID => $this->customerId]);
            $this->result = MessageManager::ASSIGNED;
        } else {
            $condition = CustomerProgramConfig::CUSTOMER_ID . ' = ' . $this->customerId;
            $this->result = MessageManager::UPDATED;
        }

        $this->assignProgram($data, $condition);
    }

    public function returnCustomerProgram(): ?LoyaltyProgramInterface
    {
        return $this->customerProgram;
    }

    private function deleteCustomerProgram(): void
    {
        $this->connection->delete(
            CustomerProgramConfig::CUSTOMER_PROGRAM_TABLE,
            CustomerProgramConfig::CUSTOMER_ID . ' = ' . $this->customerId
        );
    }

    private function assignProgram(array $data, string $condition = null): void
    {
        $condition === null ?
            $this->connection->insert(CustomerProgramConfig::CUSTOMER_PROGRAM_TABLE, $data) :
            $this->connection->update(CustomerProgramConfig::CUSTOMER_PROGRAM_TABLE, $data, $condition);
    }
}
