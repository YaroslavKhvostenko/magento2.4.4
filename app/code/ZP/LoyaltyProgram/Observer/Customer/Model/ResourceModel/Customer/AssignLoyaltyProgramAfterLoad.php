<?php
declare(strict_types=1);

namespace ZP\LoyaltyProgram\Observer\Customer\Model\ResourceModel\Customer;

use Magento\Customer\Api\Data\CustomerExtensionInterfaceFactory;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Data\Customer as CustomerDataModel;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\StoreManagerInterface;
use ZP\LoyaltyProgram\Api\LoyaltyProgramManagementInterface;
use ZP\LoyaltyProgram\Api\LoyaltyProgramRepositoryInterface;
use ZP\LoyaltyProgram\Model\Configs\Program\Scope\Config as ProgramScopeConfig;
use ZP\LoyaltyProgram\Observer\Customer\Model\ResourceModel\AbstractAssignLoyaltyProgram;
use ZP\LoyaltyProgram\Model\Registry\Observer\Customer\Model\ResourceModel\Customer\AfterLoad\Register;
use ZP\LoyaltyProgram\Model\Validators\Data\Validator;

class AssignLoyaltyProgramAfterLoad extends AbstractAssignLoyaltyProgram implements ObserverInterface
{
    public function __construct(
        LoyaltyProgramManagementInterface $loyaltyProgramManagement,
        StoreManagerInterface $storeManager,
        ProgramScopeConfig $programScopeConfig,
        CustomerExtensionInterfaceFactory $customerExtensionFactory,
        LoyaltyProgramRepositoryInterface $loyaltyProgramRepository,
        Validator $dataValidator,
        private Register $register
    ) {
        parent::__construct(
            $loyaltyProgramManagement,
            $storeManager,
            $programScopeConfig,
            $customerExtensionFactory,
            $loyaltyProgramRepository,
            $dataValidator
        );
    }

    public function execute(Observer $observer)
    {
        if (!$this->register->getFlag()) {
            parent::execute($observer);
            $this->register->setFlag(true);
        }
    }

    protected function getCustomer(Observer $observer): CustomerDataModel
    {
        $customer = $observer->getEvent()->getCustomer();
        if ($customer && $customer instanceof Customer) {
            $customerDataModel = $customer->getDataModel();
        } else {
            $customerDataModel = $customer;
        }

        return $customerDataModel;
    }
}
