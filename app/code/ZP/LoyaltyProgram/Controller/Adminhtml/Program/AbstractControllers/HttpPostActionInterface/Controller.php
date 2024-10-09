<?php
declare(strict_types=1);

namespace ZP\LoyaltyProgram\Controller\Adminhtml\Program\AbstractControllers\HttpPostActionInterface;

use ZP\LoyaltyProgram\Api\Data\RequestHelperInterface;
use ZP\LoyaltyProgram\Controller\Adminhtml\Program\AbstractControllers\BaseController;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Backend\App\Action\Context;
use Psr\Log\LoggerInterface;
use ZP\LoyaltyProgram\Api\LoyaltyProgramRepositoryInterface;
use ZP\LoyaltyProgram\Model\Controller\Adminhtml\Program\CustomerProgramManagement;
use ZP\LoyaltyProgram\Model\Controller\Adminhtml\Program\Helper;
use ZP\LoyaltyProgram\Api\Data\ValidatorInterface;
use ZP\LoyaltyProgram\Model\Controller\Adminhtml\Program\SalesRuleProgramsManagement;

abstract class Controller extends BaseController implements HttpPostActionInterface
{
    protected array $programsData = [];
    protected CustomerProgramManagement $customerProgramManagement;
    protected ?SalesRuleProgramsManagement $salesRuleProgramsManagement = null;

    public function __construct(
        Context $context,
        LoggerInterface $logger,
        protected LoyaltyProgramRepositoryInterface $programRepository,
        CustomerProgramManagement $customerProgramManagement,
        protected Helper $helper,
        protected ValidatorInterface $dataValidator,
        protected RequestHelperInterface $requestHelper
    ) {
        parent::__construct($context, $logger);
        $this->customerProgramManagement = $customerProgramManagement;
    }

    abstract protected function addMessages(): void;

    public function beforeAction(): void
    {
        $this->customerProgramManagement->collectCustomersFromPrograms($this->programsData);
        if ($this->salesRuleProgramsManagement) {
            $this->salesRuleProgramsManagement->collectRules($this->programsData);
        }
    }

    /**
     * @throws \Exception
     */
    public function afterAction(): void
    {
        if ($this->customerProgramManagement->getCustomersCount()) {
            if ($this->helper->getActiveProgramsCount()) {
                $this->customerProgramManagement->reassignProgramsToCustomers();
            } else {
                $this->customerProgramManagement->deleteProgramsFromCustomers();
            }
        }

        if ($this->salesRuleProgramsManagement && $this->salesRuleProgramsManagement->getRulesCount()) {
            $this->salesRuleProgramsManagement->deleteProgramsFromSalesRules();
        }
    }
}
