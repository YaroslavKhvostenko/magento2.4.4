<?php
declare(strict_types=1);

namespace VConnect\Blog\Controller\Adminhtml;

use Magento\Backend\App\Action;
use Magento\Backend\Model\View\Result\Page;

abstract class Post extends Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'VConnect_Blog::post';

    /**
     * Init page
     *
     * @param \Magento\Backend\Model\View\Result\Page $resultPage
     * @return \Magento\Backend\Model\View\Result\Page
     */
    protected function initPage(Page $resultPage): Page
    {
        $resultPage->setActiveMenu('VConnect_Blog::post');

        return $resultPage;
    }
}
