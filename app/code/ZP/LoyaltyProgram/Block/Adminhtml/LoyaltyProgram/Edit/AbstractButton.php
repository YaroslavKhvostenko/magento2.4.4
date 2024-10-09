<?php
declare(strict_types=1);

namespace ZP\LoyaltyProgram\Block\Adminhtml\LoyaltyProgram\Edit;

use Magento\Backend\Block\Widget\Context;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use ZP\LoyaltyProgram\Api\Data\LoyaltyProgramInterface;
use Magento\Framework\App\RequestInterface;
use ZP\LoyaltyProgram\Api\Model\Validators\Controller\Adminhtml\Program\ValidatorInterface;

abstract class AbstractButton implements ButtonProviderInterface
{
    protected ?int $programId = null;
    protected RequestInterface $request;

    /**
     * AbstractButton constructor.
     * @param Context $context
     * @param ValidatorInterface $dataValidator
     * @throws \Exception
     */
    public function __construct(protected Context $context, protected ValidatorInterface $dataValidator)
    {
        $this->request = $this->context->getRequest();
        $this->setProgramIdPropertyValue();
    }

    public function getButtonData(): array
    {
        $buttonData =[];
        if ($this->validateButtonWorkConditions()) {
            $buttonData = $this->getData();
        }

        return $buttonData;
    }

    protected function validateButtonWorkConditions(): bool
    {
        return !$this->isEditAction() || $this->validateProgramId();
    }

    protected function isEditAction(): bool
    {
        return $this->request->getActionName() === 'edit';
    }

    protected function validateProgramId(): bool
    {
        return !$this->dataValidator->isBasicProgram($this->programId);
    }

    /**
     * @throws \Exception
     */
    protected function setProgramIdPropertyValue(): void
    {
        $programId = $this->request->getParam(LoyaltyProgramInterface::PROGRAM_ID);
        if ($programId !== null) {
            $this->programId = $this->dataValidator->validateProgramId($programId);
        }
    }

    abstract protected function getData(): array;
}
