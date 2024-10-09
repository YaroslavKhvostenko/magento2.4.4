<?php
declare(strict_types=1);

namespace ZP\LoyaltyProgram\Plugin\Customer\Model;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Customer\Api\Data\CustomerExtensionInterface;
use Magento\Customer\Api\Data\CustomerExtensionInterfaceFactory;
use ZP\LoyaltyProgram\Model\Configs\Customer\Program\Config as CustomerProgramConfig;

class CustomerRepositoryPlugin
{
    public function __construct(
        private ResourceConnection $resourceConnection,
        private CustomerExtensionInterfaceFactory $customerExtensionFactory
    )
    {}

    public function afterGetById(CustomerRepositoryInterface $subject, CustomerInterface $customer): CustomerInterface
    {
        return $this->get($subject, $customer);
    }

    public function afterGet(CustomerRepositoryInterface $subject, CustomerInterface $customer): CustomerInterface
    {
        return $this->get($subject, $customer);
    }

    private function get(CustomerRepositoryInterface $subject, CustomerInterface $customer): CustomerInterface
    {
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from(CustomerProgramConfig::CUSTOMER_PROGRAM_TABLE, CustomerProgramConfig::PROGRAM_ID)
            ->where(CustomerProgramConfig::CUSTOMER_ID . ' = (?)', $customer->getId());
        $result = $connection->fetchOne($select);
        if ($result !== null && $result !== false) {
            $extensionAttributes = $customer->getExtensionAttributes();
            if ($extensionAttributes === null) {
                $extensionAttributes = $this->customerExtensionFactory->create();
            }

            /** @var CustomerExtensionInterface $extensionAttributes */
            $extensionAttributes->setLoyaltyProgramId((int)$result);
            $customer->setExtensionAttributes($extensionAttributes);
        }

        return $customer;
    }
}
