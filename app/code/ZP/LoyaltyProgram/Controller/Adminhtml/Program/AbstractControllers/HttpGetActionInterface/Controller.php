<?php
declare(strict_types=1);

namespace ZP\LoyaltyProgram\Controller\Adminhtml\Program\AbstractControllers\HttpGetActionInterface;

use ZP\LoyaltyProgram\Controller\Adminhtml\Program\AbstractControllers\BaseController;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Backend\Model\View\Result\Page;
use Magento\Backend\App\Action\Context;
use Psr\Log\LoggerInterface;

abstract class Controller extends BaseController implements HttpGetActionInterface
{
    public function __construct(
        Context $context,
        LoggerInterface $logger
    ) {
        parent::__construct($context, $logger);
    }

    /**
     * Init page
     *
     * @param Page $resultPage
     * @return Page
     */
    protected function initPage(Page $resultPage): Page
    {
        $resultPage->setActiveMenu(self::ADMIN_RESOURCE);

        return $resultPage;
    }
}
