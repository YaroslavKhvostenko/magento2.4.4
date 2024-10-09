<?php
declare(strict_types=1);

namespace ZP\LoyaltyProgram\Api\Model\Controller\Adminhtml\Program;

use Magento\Framework\App\RequestInterface;
use ZP\LoyaltyProgram\Api\Data\RequestHelperInterface as BaseRequestHelperInterface;

interface RequestHelperInterface extends BaseRequestHelperInterface
{
    /**
     * @param RequestInterface $request
     * @return string|null
     */
    public function getProgramIdFromRequest(RequestInterface $request): ?string;
}
