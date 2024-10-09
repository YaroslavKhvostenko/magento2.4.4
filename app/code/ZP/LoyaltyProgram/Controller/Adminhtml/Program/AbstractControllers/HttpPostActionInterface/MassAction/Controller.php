<?php
declare(strict_types=1);

namespace ZP\LoyaltyProgram\Controller\Adminhtml\Program\AbstractControllers\HttpPostActionInterface\MassAction;

use Magento\Backend\App\Action\Context;
use Magento\Ui\Component\MassAction\Filter;
use Psr\Log\LoggerInterface;
use ZP\LoyaltyProgram\Api\LoyaltyProgramRepositoryInterface;
use ZP\LoyaltyProgram\Controller\Adminhtml\Program\AbstractControllers\HttpPostActionInterface\Controller as BaseController;
use ZP\LoyaltyProgram\Model\Controller\Adminhtml\Program\CustomerProgramManagement;
use ZP\LoyaltyProgram\Model\Controller\Adminhtml\Program\Helper;
use ZP\LoyaltyProgram\Model\LoyaltyProgram;
use ZP\LoyaltyProgram\Model\Prepare\Data\DataPreparer;
use ZP\LoyaltyProgram\Model\ResourceModel\LoyaltyProgram\Collection;
use ZP\LoyaltyProgram\Model\ResourceModel\LoyaltyProgram\CollectionFactory;
use ZP\LoyaltyProgram\Api\Model\Validators\Controller\Adminhtml\Program\MassAction\ValidatorInterface;
use ZP\LoyaltyProgram\Api\Model\Controller\Adminhtml\Program\MassAction\RequestHelperInterface;

abstract class Controller extends BaseController
{
    protected bool $basicProgramMsgStatus = false;
    protected array $programsForAction = [];

    public function __construct(
        Context $context,
        LoggerInterface $logger,
        LoyaltyProgramRepositoryInterface $programRepository,
        CustomerProgramManagement $customerProgramManagement,
        Helper $helper,
        ValidatorInterface $dataValidator,
        RequestHelperInterface $requestHelper,
        protected Filter $filter,
        protected CollectionFactory $collectionFactory,
        protected DataPreparer $prepareData,
    ) {
        parent::__construct(
            $context,
            $logger,
            $programRepository,
            $customerProgramManagement,
            $helper,
            $dataValidator,
            $requestHelper
        );
    }

    protected function validateCollectionPrograms(Collection &$collection): void
    {
        /**
         * @var int $programId
         * @var LoyaltyProgram $program
         */
        foreach ($collection->getItems() as $programId => $program) {
            if ($this->dataValidator->isBasicProgram($programId)) {
                $collection->removeItemByKey($programId);
                $this->basicProgramMsgStatus = true;
            }
        }
    }

    protected function checkProgramIds(string $actionType): void
    {
        if (!$this->requestHelper->isRequestParamExcludedFalse($this->getRequest())) {
            $programIds = $this->requestHelper->getProgramIdsFromRequest($this->getRequest());
            $programIds = $this->prepareData->makeArrayKeysLikeValues(
                $this->dataValidator->validateProgramIds($programIds)
            );
            $this->dataValidator->checkProgramIds($programIds, $actionType);
        }
    }
}
