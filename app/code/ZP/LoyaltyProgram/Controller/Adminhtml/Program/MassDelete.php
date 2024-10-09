<?php
declare(strict_types=1);

namespace ZP\LoyaltyProgram\Controller\Adminhtml\Program;

use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Backend\App\Action\Context;
use Magento\Ui\Component\MassAction\Filter;
use Psr\Log\LoggerInterface;
use ZP\LoyaltyProgram\Model\Controller\Adminhtml\Program\MassAction\RequestHelper;
use ZP\LoyaltyProgram\Model\LoyaltyProgram;
use ZP\LoyaltyProgram\Model\ResourceModel\LoyaltyProgram\CollectionFactory;
use ZP\LoyaltyProgram\Model\ResourceModel\LoyaltyProgram\Collection;
use ZP\LoyaltyProgram\Controller\Adminhtml\Program\AbstractControllers\HttpPostActionInterface\MassAction\Controller;
use ZP\LoyaltyProgram\Model\Controller\Adminhtml\Program\CustomerProgramManagement;
use ZP\LoyaltyProgram\Model\Prepare\Data\DataPreparer;
use ZP\LoyaltyProgram\Model\Controller\Adminhtml\Program\SalesRuleProgramsManagement;
use ZP\LoyaltyProgram\Model\Controller\Adminhtml\Program\Helper;
use ZP\LoyaltyProgram\Api\LoyaltyProgramRepositoryInterface;
use ZP\LoyaltyProgram\Api\Model\Validators\Controller\Adminhtml\Program\MassAction\ValidatorInterface;

class MassDelete extends Controller
{
    private const DELETE = 'delete';

    private int $deletedProgramsCount = 0;

    public function __construct(
        Context $context,
        LoggerInterface $logger,
        LoyaltyProgramRepositoryInterface $programRepository,
        CustomerProgramManagement $customerProgramManagement,
        Helper $helper,
        ValidatorInterface $dataValidator,
        RequestHelper $requestHelper,
        Filter $filter,
        CollectionFactory $collectionFactory,
        DataPreparer $prepareData,
        SalesRuleProgramsManagement $salesRuleProgramsManagement
    ) {
        parent::__construct(
            $context,
            $logger,
            $programRepository,
            $customerProgramManagement,
            $helper,
            $dataValidator,
            $requestHelper,
            $filter,
            $collectionFactory,
            $prepareData
        );

        $this->salesRuleProgramsManagement = $salesRuleProgramsManagement;
    }

    public function execute()
    {
        try {
            /** @var Redirect $resultRedirect */
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $this->checkProgramIds(self::DELETE);

            /** @var Collection $collection */
            $collection = $this->filter->getCollection($this->collectionFactory->create());

            if (!$collection->getSize()) {
                $this->messageManager->addNoticeMessage(
                    __('Program(s) with specified ids don\'t exist.')
                );
            } else {
                $this->validateCollectionPrograms($collection);
                if ($this->programsForAction) {
                    $this->programsData = $this->programsForAction;
                    $this->beforeDelete();
                    $this->deletedProgramsCount = count($this->programsForAction);

                    /**
                     * @var int $programId
                     * @var LoyaltyProgram $program
                     */
                    foreach ($collection->getItems() as $program) {
                        $this->programRepository->delete($program);
                    }

                    $this->afterDelete();

                }

                $this->addMessages();
            }
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage(
                __('Sorry something went wrong while trying to delete program(s)!')
            );
            $this->logger->notice(__($exception->getMessage()));
        }

        return $resultRedirect->setPath('*/*/');
    }

    protected function validateCollectionPrograms(Collection &$collection): void
    {
        parent::validateCollectionPrograms($collection);

        foreach ($collection->getItems() as $programId => $program) {
            $this->programsForAction[] = $programId;
        }
    }

    private function beforeDelete(): void
    {
        $this->beforeAction();
    }

    /**
     * @throws \Exception
     */
    private function afterDelete(): void
    {
        $this->afterAction();
    }

    protected function addMessages(): void
    {
        if ($this->deletedProgramsCount) {
            $this->messageManager->addSuccessMessage(
                __('A total of %1 program(s) has(have) been deleted.', $this->deletedProgramsCount)
            );
        }

        if ($this->basicProgramMsgStatus) {
            $this->messageManager->addNoticeMessage(__('You are not allowed to delete BASIC PROGRAMS!'));
        }
    }
}
