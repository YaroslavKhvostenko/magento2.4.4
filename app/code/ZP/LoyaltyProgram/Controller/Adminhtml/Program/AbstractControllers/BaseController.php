<?php
declare(strict_types=1);

namespace ZP\LoyaltyProgram\Controller\Adminhtml\Program\AbstractControllers;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Psr\Log\LoggerInterface;

abstract class BaseController extends Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'ZP_LoyaltyProgram::manage';

    public function __construct(Context $context, protected LoggerInterface $logger)
    {
        parent::__construct($context);
    }
}
