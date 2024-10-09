<?php
declare(strict_types=1);

namespace ZP\LoyaltyProgram\Model\Controller\Adminhtml\Program\MassAction;

use ZP\LoyaltyProgram\Model\Controller\Adminhtml\Program\AbstractRequestHelper;
use ZP\LoyaltyProgram\Api\Model\Controller\Adminhtml\Program\MassAction\RequestHelperInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Ui\Component\MassAction\Filter;

class RequestHelper extends AbstractRequestHelper implements RequestHelperInterface
{
    public const REQUEST_FIELD = Filter::SELECTED_PARAM;
    public const EXCLUDED_PARAM = Filter::EXCLUDED_PARAM;
    private array $postData = [];

    public function isRequestParamExcludedFalse(RequestInterface $request): bool
    {
        $this->postData = $request->getPostValue();
        return array_key_exists(self::EXCLUDED_PARAM, $this->postData) &&
            $this->postData[self::EXCLUDED_PARAM] === 'false';
    }

    /**
     * @param RequestInterface $request
     * @return array
     * @throws \Exception
     */
    public function getProgramIdsFromRequest(RequestInterface $request): array
    {
        return $this->getData($request);
    }

    protected function programIdRequestCondition(RequestInterface $request): bool
    {
        if (array_key_exists($this->getRequestField(), $this->postData) && $this->postData[$this->getRequestField()]) {
            $this->programIdData = $this->postData[$this->getRequestField()];

            return true;
        }

        return false;
    }

    protected function requestConditionFalseResultReaction(): void
    {
        throw new \Exception(
            'Param \'' . $this->getRequestField() . '\' does not exist or empty in POST data!'
        );
    }
}
