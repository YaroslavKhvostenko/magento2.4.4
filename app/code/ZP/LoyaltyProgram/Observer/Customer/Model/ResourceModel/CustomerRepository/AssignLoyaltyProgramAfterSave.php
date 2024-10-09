<?php
declare(strict_types=1);

namespace ZP\LoyaltyProgram\Observer\Customer\Model\ResourceModel\CustomerRepository;

use Magento\Customer\Model\Data\Customer;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use ZP\LoyaltyProgram\Observer\Customer\Model\ResourceModel\AbstractAssignLoyaltyProgram;

class AssignLoyaltyProgramAfterSave extends AbstractAssignLoyaltyProgram implements ObserverInterface
{
    protected function getCustomer(Observer $observer): Customer
    {
        return $observer->getEvent()->getData('customer_data_object');
    }
}
