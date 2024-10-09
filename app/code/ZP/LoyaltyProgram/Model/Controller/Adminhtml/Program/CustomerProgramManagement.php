<?php
declare(strict_types=1);

namespace ZP\LoyaltyProgram\Model\Controller\Adminhtml\Program;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use ZP\LoyaltyProgram\Api\LoyaltyProgramManagementInterface;
use ZP\LoyaltyProgram\Model\Prepare\Data\DataPreparer;
use ZP\LoyaltyProgram\Model\Configs\Customer\Program\Config as CustomerProgramConfig;
use Magento\Customer\Api\CustomerRepositoryInterface;

class CustomerProgramManagement
{
    private AdapterInterface $connection;
    private array $mergedCustomerIds = [];
    private int $countCustomers = 0;

    public function __construct(
        ResourceConnection $resourceConnection,
        private DataPreparer $prepareData,
        private CustomerRepositoryInterface $customerRepository,
        private SearchCriteriaBuilder $searchCriteriaBuilder,
        private LoyaltyProgramManagementInterface $programManagement
    ) {
        $this->connection = $resourceConnection->getConnection();
    }

    public function collectCustomersFromPrograms(array $programIds): void
    {
        $programIds = $this->prepareData->arrayValuesToInteger($programIds);
        $programCustomerIds = [];
        foreach ($programIds as $programId) {
            $programCustomerIds[$programId] = $this->prepareData->makeArrayValuesLikeKeys($this->selectCustomerIds($programId));
        }

        $this->mergedCustomerIds = $this->prepareData->combineArraysInsideArray($programCustomerIds);
        $this->countCustomers = count($this->mergedCustomerIds);
    }

    /**
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws \Exception
     */
    public function reassignProgramsToCustomers(): void
    {
        if ($this->mergedCustomerIds) {
            if ($this->countCustomers === 1) {
                $customers[] = $this->customerRepository->getById(array_key_first($this->mergedCustomerIds));
            } else {
                $searchCriteria = $this->searchCriteriaBuilder
                    ->addFilter('entity_id', $this->mergedCustomerIds, 'in')
                    ->create();
                $customers = $this->customerRepository->getList($searchCriteria)->getItems();
            }

            foreach ($customers as $customer) {
                $this->programManagement->assignLoyaltyProgram($customer);
            }
        }
    }

    public function deleteProgramsFromCustomers(): void
    {
        if ($this->mergedCustomerIds) {
            $customerIds = implode(',', $this->mergedCustomerIds);
            $this->connection->delete(
                CustomerProgramConfig::CUSTOMER_PROGRAM_TABLE,
                CustomerProgramConfig::CUSTOMER_ID . ' IN (' . $customerIds . ')'
            );
        }
    }

    private function selectCustomerIds(int $programId): array
    {
        /** @var  Select $select */
        $select = $this->connection->select()
            ->from(CustomerProgramConfig::CUSTOMER_PROGRAM_TABLE, CustomerProgramConfig::CUSTOMER_ID)
            ->where(CustomerProgramConfig::PROGRAM_ID . ' = ' . $programId);

        return $this->connection->fetchAssoc($select);
    }

    public function getCustomersCount(): int
    {
        return $this->countCustomers;
    }
}
