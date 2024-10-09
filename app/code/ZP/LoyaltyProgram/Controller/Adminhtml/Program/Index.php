<?php
declare(strict_types=1);

namespace ZP\LoyaltyProgram\Controller\Adminhtml\Program;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\View\Result\Page;
use Psr\Log\LoggerInterface;
use Magento\Framework\Controller\ResultInterface;
use ZP\LoyaltyProgram\Controller\Adminhtml\Program\AbstractControllers\HttpGetActionInterface\Controller;

class Index extends Controller
{
    public function __construct(Context $context, LoggerInterface $logger)
    {
        parent::__construct($context, $logger);
    }

    public function execute(): ResultInterface
    {
        try {
            /** @var Page $resultPage */
            $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
            $this->initPage($resultPage);
            $resultPage->getConfig()->getTitle()->prepend(__('Manage Loyalty Program'));

            return $resultPage;
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage(__($exception->getMessage()));
            $this->logger->notice(__($exception->getMessage()));
        }

        return $this->resultRedirectFactory->create()->setPath('*/*/');
    }
}
