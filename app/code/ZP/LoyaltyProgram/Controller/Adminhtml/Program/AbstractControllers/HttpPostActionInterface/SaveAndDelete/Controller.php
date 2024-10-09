<?php
declare(strict_types=1);

namespace ZP\LoyaltyProgram\Controller\Adminhtml\Program\AbstractControllers\HttpPostActionInterface\SaveAndDelete;

use Magento\Backend\App\Action\Context;
use Psr\Log\LoggerInterface;
use ZP\LoyaltyProgram\Api\LoyaltyProgramRepositoryInterface;
use ZP\LoyaltyProgram\Api\Model\Controller\Adminhtml\Program\RequestHelperInterface;
use ZP\LoyaltyProgram\Api\Model\Validators\Controller\Adminhtml\Program\ValidatorInterface;
use ZP\LoyaltyProgram\Controller\Adminhtml\Program\AbstractControllers\HttpPostActionInterface\Controller as BaseController;
use ZP\LoyaltyProgram\Model\Controller\Adminhtml\Program\CustomerProgramManagement;
use ZP\LoyaltyProgram\Model\Controller\Adminhtml\Program\Helper;

abstract class Controller extends BaseController
{
    protected const BASIC_PROGRAM_ERR = '';

    protected ?string $programName = null;
    protected ?int $programId = null;

    public function __construct(
        Context $context,
        LoggerInterface $logger,
        LoyaltyProgramRepositoryInterface $programRepository,
        CustomerProgramManagement $customerProgramManagement,
        Helper $helper,
        ValidatorInterface $dataValidator,
        RequestHelperInterface $requestHelper
    ) {
        parent::__construct(
            $context,
            $logger,
            $programRepository,
            $customerProgramManagement,
            $helper,
            $dataValidator,
            $requestHelper
        );
    }

    protected function validateProgramId(mixed $programId): void
    {
        if ($programId === null) {
            $this->nullProgramIdReaction();
        } else {
            $this->programId = $this->dataValidator->validateProgramId($programId);
        }
    }

    protected function isBasicProgram(): void
    {
        if ($this->dataValidator->isBasicProgram($this->programId)) {
            throw new \Exception('BASIC PROGRAMS are forbidden to ' . $this->getBasicProgramErr() . '!');
        }

        $this->programsData = [$this->programId];
    }

    protected function getBasicProgramErr(): string
    {
        return static::BASIC_PROGRAM_ERR;
    }

    protected abstract function nullProgramIdReaction(): void;
}
