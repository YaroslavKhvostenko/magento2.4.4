<?php
declare(strict_types=1);

namespace ZP\LoyaltyProgram\Console\Command\Customer;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use ZP\LoyaltyProgram\Api\LoyaltyProgramManagementInterface;
use ZP\LoyaltyProgram\Model\Prepare\Data\DataPreparer;
use ZP\LoyaltyProgram\Model\Validators\Data\Validator;
use ZP\LoyaltyProgram\Model\MessageManager\Console\Command\Customer\AssignLoyaltyProgram\MessageManager;
use Magento\Framework\Console\Cli;

class AssignLoyaltyProgram extends Command
{
    private const CUSTOMER_IDS = 'customer_ids';
    private bool $isAllCustomers;

    /**
     * @param string|null $name
     */
    public function __construct(
        private CustomerRepositoryInterface $customerRepository,
        private SearchCriteriaBuilder $searchCriteriaBuilder,
        private LoyaltyProgramManagementInterface $loyaltyProgramManagement,
        private DataPreparer $prepareData,
        private Validator $dataValidator,
        private MessageManager $messageManager,
        string $name = null
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->setDescription(
            'A command to assign loyalty program to customer(s).'
        );

        $this->addArgument(
            self::CUSTOMER_IDS,
            InputArgument::IS_ARRAY,
            'Separate multiple ids with a space.'
        );

        parent::configure();
    }


    public function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $result = Cli::RETURN_SUCCESS;
            $customerIds = $this->validateCustomerIds($this->getCustomerIds($input));
            if (
                !$this->isAllCustomers() && !$customerIds &&
                !$this->messageManager->isResultTypeEmpty(MessageManager::WRONG_DATA)
            ) {
                $result = Cli::RETURN_FAILURE;
            }

            if ($this->isResultSuccess($result)) {
                /** @var CustomerInterface[] $customers */
                $customers = $this->getCustomers($customerIds);
                if (!$this->compareCustomersCount($customers, $customerIds) && !$customers) {
                    $result = Cli::RETURN_FAILURE;
                    if ($this->messageManager->isResultTypeEmpty(MessageManager::NOT_EXIST)) {
                        $output->writeln('We don\'t have any customers at the moment!');

                        return $result;
                    }
                }

                if ($this->isResultSuccess($result)) {
                    $this->assignLoyaltyProgramsToCustomers($customers);
                }
            }

            $output->writeln($this->messageManager->getResultMessage());

            return $result;
        } catch (\Exception $exception) {
            $output->writeln($exception->getMessage());

            return Cli::RETURN_FAILURE;
        }
    }

    private function isAllCustomers(): bool
    {
        return $this->isAllCustomers;
    }

    /**
     * @param array $customerIds
     * @return array
     * @throws \Exception
     */
    private function checkDataType(array $customerIds): array
    {
        foreach ($customerIds as $key => $customerId) {
            if (!$this->dataValidator->isDataInteger($customerId)) {
                $this->messageManager->setResultValue(MessageManager::WRONG_DATA, $customerId);
                unset($customerIds[$key]);
            }
        }

        return $this->prepareData->makeArrayKeysLikeValues($this->prepareData->arrayValuesToInteger($customerIds));
    }

    /**
     * @param array $customers
     * @param array $customerIds
     * @return bool
     * @throws \Exception
     */
    private function compareCustomersCount(
        array $customers,
        array $customerIds
    ): bool {

        if (!$customers || count($customers) !== count($customerIds)) {
            foreach ($customerIds as $customerId) {
                if (!array_key_exists($customerId, $customers)) {
                    $this->messageManager->setResultValue(MessageManager::NOT_EXIST, $customerId);
                }
            }
        }

        return (bool)$customers;
    }

    /**
     * @param CustomerInterface[] $customers
     * @throws \Exception
     */
    private function assignLoyaltyProgramsToCustomers(array $customers): void
    {
        /** @var CustomerInterface $customer */
        foreach ($customers as $customerId => $customer) {
            $this->loyaltyProgramManagement->assignLoyaltyProgram($customer);
            $result = $this->loyaltyProgramManagement->returnResult();
            $this->messageManager->setResultValue($this->loyaltyProgramManagement->returnResult(), $customerId);
        }
    }

    private function getCustomerIds(InputInterface $input)
    {
        $customerIds = $input->getArgument(self::CUSTOMER_IDS);
        $this->setIsAllCustomersProperty(!$customerIds);

        return $customerIds;
    }

    /**
     * @param array $customerIds
     * @return array
     * @throws \Exception
     */
    private function validateCustomerIds(array $customerIds): array
    {
        return $this->isAllCustomers() ? $customerIds : $this->checkDataType($customerIds);
    }

    /**
     * @param array $customerIds
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function getCustomers(array $customerIds): array
    {
        $customers = [];
        if (count($customerIds) === 1) {
            $data[] = $this->customerRepository->getById($customerIds[array_key_first($customerIds)]);
        } else {
            if (!$this->isAllCustomers()) {
                $this->searchCriteriaBuilder->addFilter('entity_id', $customerIds, 'in');
            }

            $data = $this->customerRepository->getList($this->searchCriteriaBuilder->create())->getItems();
        }

        foreach ($data as $customer) {
            $customers[(int)$customer->getId()] = $customer;
        }

        return $customers;
    }

    private function setIsAllCustomersProperty(bool $value): void
    {
        $this->isAllCustomers = $value;
    }

    private function isResultSuccess(int $result): bool
    {
        return $result === Cli::RETURN_SUCCESS;
    }
}
