<?php
declare(strict_types=1);

namespace ZP\LoyaltyProgram\Model\Controller\Adminhtml\Program;

use Magento\Framework\App\RequestInterface;
use ZP\LoyaltyProgram\Api\Data\RequestHelperInterface;

abstract class AbstractRequestHelper implements RequestHelperInterface
{
    public const REQUEST_FIELD = '';

    protected null|string|array $programIdData = null;

    protected function getRequestField(): string
    {
        return static::REQUEST_FIELD;
    }

    protected function getData(RequestInterface $request): null|string|array
    {
        if (!$this->programIdRequestCondition($request)) {
            $this->requestConditionFalseResultReaction();
        }

        return $this->programIdData;
    }

    abstract protected function programIdRequestCondition(RequestInterface $request): bool;

    abstract protected function requestConditionFalseResultReaction(): void;
}
