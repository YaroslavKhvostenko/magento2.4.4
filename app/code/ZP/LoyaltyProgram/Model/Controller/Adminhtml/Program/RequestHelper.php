<?php
declare(strict_types=1);

namespace ZP\LoyaltyProgram\Model\Controller\Adminhtml\Program;

use ZP\LoyaltyProgram\Api\Model\Controller\Adminhtml\Program\RequestHelperInterface;
use Magento\Framework\App\RequestInterface;
use ZP\LoyaltyProgram\Model\LoyaltyProgram;

class RequestHelper extends AbstractRequestHelper implements RequestHelperInterface
{
    public const REQUEST_FIELD = LoyaltyProgram::PROGRAM_ID;

    /**
     * @param RequestInterface $request
     * @return string|null
     */
    public function getProgramIdFromRequest(RequestInterface $request): ?string
    {
        return $this->getData($request);
    }

    protected function programIdRequestCondition(RequestInterface $request): bool
    {
        if (array_key_exists($this->getRequestField(), $request->getParams())) {
            $this->programIdData = $request->getParam($this->getRequestField());

            return true;
        }

        return false;
    }

    protected function requestConditionFalseResultReaction(): void
    {
        // do nothing
    }
}
