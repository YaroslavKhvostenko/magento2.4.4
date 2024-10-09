<?php
declare(strict_types=1);

namespace ZP\LoyaltyProgram\Controller\Adminhtml\Program;

use ZP\LoyaltyProgram\Api\LoyaltyProgramRepositoryInterface;
use ZP\LoyaltyProgram\Controller\Adminhtml\Program\AbstractControllers\HttpPostActionInterface\MassAction\Controller;
use Magento\Framework\Controller\ResultFactory;
use Magento\Backend\App\Action\Context;
use Magento\Ui\Component\MassAction\Filter;
use ZP\LoyaltyProgram\Model\Controller\Adminhtml\Program\MassAction\RequestHelper;
use ZP\LoyaltyProgram\Model\LoyaltyProgram;
use ZP\LoyaltyProgram\Model\ResourceModel\LoyaltyProgram\CollectionFactory;
use ZP\LoyaltyProgram\Model\ResourceModel\LoyaltyProgram\Collection;
use ZP\LoyaltyProgram\Model\Controller\Adminhtml\Program\CustomerProgramManagement;
use ZP\LoyaltyProgram\Model\Controller\Adminhtml\Program\Helper;
use Psr\Log\LoggerInterface;
use ZP\LoyaltyProgram\Model\Prepare\Data\DataPreparer;
use ZP\LoyaltyProgram\Api\Model\Validators\Controller\Adminhtml\Program\MassAction\ValidatorInterface;

class MassStatus extends Controller
{
    private const EDIT = 'edit';
    public const STATUS = 'status';
    private bool $activeStatusFromRequest;
    private int $updatedProgramsCount = 0;
    private int $notUpdatedProgramsCount = 0;

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
    }

    public function execute()
    {
        try {
            /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $this->checkProgramIds(self::EDIT);

            /** @var Collection $collection */
            $collection = $this->filter->getCollection($this->collectionFactory->create());
            if (!$collection->getSize()) {
                $this->messageManager->addNoticeMessage('Program(s) with specified ids don\'t exist.');
            } else {
                $this->setActiveStatusFromRequest();

                $this->validateCollectionPrograms($collection);

                if ($this->updatedProgramsCount) {
                    if ($this->programsForAction) {
                        $this->programsData = $this->programsForAction;
                        $this->beforeSave();
                    }

                    /** @var LoyaltyProgram $program */
                    foreach ($collection->getItems() as $program) {
                        $this->programRepository->save($program);
                    }
                }

                if ($this->programsForAction) {
                    $this->afterSave();
                }

                $this->addMessages();
            }
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage(
                __('Sorry something went wrong while trying to update program(s) status!')
            );
            $this->logger->notice(__($exception->getMessage()));
        }

        return $resultRedirect->setPath('*/*/');
    }

    private function setActiveStatusFromRequest(): void
    {
        if (array_key_exists(self::STATUS, $this->getRequest()->getParams())) {
            $this->activeStatusFromRequest = (bool)$this->getRequest()->getParam(self::STATUS);
        } else {
            throw new \Exception('No status from request!!!');
        }
    }

    /**
     * @param Collection $collection
     * @throws \Exception
     */
    protected function validateCollectionPrograms(Collection &$collection): void
    {
        parent::validateCollectionPrograms($collection);

        /**
         * @var int $programId
         * @var LoyaltyProgram $program
         */
        foreach ($collection->getItems() as $programId => $program) {
            if ($this->isSameStatus($this->getActiveStatusFromRequest(), $program->getIsActive())) {
                $collection->removeItemByKey($programId);
                $this->notUpdatedProgramsCount++;
            } else {
                if ($this->isActiveStatusChangedToDisable($this->getActiveStatusFromRequest(), $program->getIsActive())) {
                    $this->programsForAction[] = $programId;
                }

                $program->setIsActive($this->getActiveStatusFromRequest());
                $this->updatedProgramsCount++;
            }
        }
    }

    private function isSameStatus(bool $statusFromRequest, bool $programStatus): bool
    {
        return $statusFromRequest === $programStatus;
    }

    public function getActiveStatusFromRequest(): bool
    {
        return $this->activeStatusFromRequest;
    }

    private function isActiveStatusChangedToDisable(bool $statusFromRequest, bool $isCurrentStatusTrue): bool
    {
        return $isCurrentStatusTrue && $isCurrentStatusTrue !== $statusFromRequest;
    }

    private function beforeSave(): void
    {
        $this->beforeAction();
    }

    /**
     * @throws \Exception
     */
    private function afterSave(): void
    {
        $this->afterAction();
    }

    protected function addMessages(): void
    {
        if ($this->updatedProgramsCount) {
            $this->messageManager->addSuccessMessage(
                __('A total of %1 program(s) has(have) been updated.', $this->updatedProgramsCount)
            );
        } else {
            $this->messageManager->addNoticeMessage(
                __(
                    'You have selected the same active status that is set already for %1 selected program(s)!',
                    $this->notUpdatedProgramsCount
                )
            );
        }

        if ($this->basicProgramMsgStatus) {
            $this->messageManager->addNoticeMessage(__('You are not allowed to edit BASIC PROGRAMS!'));
        }
    }
}
