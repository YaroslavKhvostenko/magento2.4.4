<?php

namespace ZP\LoyaltyProgram\Api\Model\Controller\Adminhtml\Program\MassAction;

use Magento\Framework\App\RequestInterface;
use ZP\LoyaltyProgram\Api\Data\RequestHelperInterface as BaseRequestHelperInterface;

interface RequestHelperInterface extends BaseRequestHelperInterface
{
    /**
     * @param RequestInterface $request
     * @return array
     * @throws \Exception
     */
    public function getProgramIdsFromRequest(RequestInterface $request): array;
}
