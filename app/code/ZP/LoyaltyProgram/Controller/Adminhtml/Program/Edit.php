<?php
declare(strict_types=1);

namespace ZP\LoyaltyProgram\Controller\Adminhtml\Program;

use ZP\LoyaltyProgram\Controller\Adminhtml\Program\AbstractControllers\HttpGetActionInterface\Controller;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Result\PageFactory;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultInterface;
use ZP\LoyaltyProgram\Model\Controller\Adminhtml\Program\RequestHelper;
use ZP\LoyaltyProgram\Model\LoyaltyProgram;
use ZP\LoyaltyProgram\Api\LoyaltyProgramRepositoryInterface;
use Psr\Log\LoggerInterface;
use ZP\LoyaltyProgram\Api\Model\Validators\Controller\Adminhtml\Program\ValidatorInterface;

class Edit extends Controller
{
    private ?int $programId = null;

    public function __construct(
        Context $context,
        LoggerInterface $logger,
        protected PageFactory $resultPageFactory,
        private LoyaltyProgramRepositoryInterface $programRepository,
        private ValidatorInterface $dataValidator,
        private RequestHelper $requestHelper
    ) {
        parent::__construct($context, $logger);
    }

    /**
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        try {
            $programId = $this->requestHelper->getProgramIdFromRequest($this->getRequest());
            $this->validateProgramId($programId);
            $this->isBasicProgram();
            /** @var LoyaltyProgram|null $program */
            $program = $this->programId !== null ? $this->programRepository->get($this->programId) : null;

            /** @var Page $resultPage */
            $resultPage = $this->resultPageFactory->create();
            $this->initPage($resultPage)->addBreadcrumb(
                $program ? __('Edit Program') : __('New Program'),
                $program ? __('Edit Program') : __('New Program')
            );
            $resultPage->getConfig()->getTitle()->prepend(__('Programs'));
            $resultPage->getConfig()->getTitle()->prepend(
                $program ? $program->getProgramName() : __('New Program')
            );

            return $resultPage;
        } catch (NoSuchEntityException $exception) {
            $this->messageManager->addErrorMessage(__('This Loyalty Program does not exist any more.'));
            $this->logger->notice(
                __('Someone tried to edit program that already does not exist!' . $exception->getMessage())
            );
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage(__($exception->getMessage()));
            $this->logger->notice(__($exception->getMessage()));
        }

        return $this->resultRedirectFactory->create()->setPath('*/*/');
    }

    /**
     * @param mixed $programId
     * @throws \Exception
     */
    private function validateProgramId(mixed $programId): void
    {
        if ($programId !== null) {
            $this->programId = $this->dataValidator->validateProgramId($programId);
        }
    }

    protected function isBasicProgram(): void
    {
        if ($this->dataValidator->isBasicProgram($this->programId)) {
            throw new \Exception('BASIC PROGRAMS are forbidden to ' . Save::BASIC_PROGRAM_ERR . '!');
        }
    }
}
