<?php
declare(strict_types=1);

namespace ZP\LoyaltyProgram\Controller\Adminhtml\Program;

use Magento\Framework\Controller\Result\Forward;
use Magento\Backend\Model\View\Result\ForwardFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Backend\App\Action\Context;
use Psr\Log\LoggerInterface;
use ZP\LoyaltyProgram\Controller\Adminhtml\Program\AbstractControllers\HttpGetActionInterface\Controller;

class NewAction extends Controller
{
    public function __construct(
        Context $context,
        LoggerInterface $logger,
        protected ForwardFactory $resultForwardFactory
    ) {
        parent::__construct($context, $logger);
    }

    /**
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        try {
            /** @var Forward $resultForward */
            $resultForward = $this->resultForwardFactory->create();

            return $resultForward->forward('edit');
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage(__($exception->getMessage()));
            $this->logger->notice(__($exception->getMessage()));
        }

        return $this->resultRedirectFactory->create()->setPath('*/*/');
    }
}
