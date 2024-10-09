<?php

namespace ZP\LoyaltyProgram\Api\Model\Validators\Controller\Adminhtml\Program\MassAction;

use ZP\LoyaltyProgram\Api\Data\ValidatorInterface as BaseValidatorInterface;
use ZP\LoyaltyProgram\Setup\Patch\Data\AddBasicPrograms as BasicProgramsConfig;

interface ValidatorInterface extends BaseValidatorInterface
{
    /**
     * @param array $programIds
     * @return array
     * @throws \Exception
     */
    public function validateProgramIds(array $programIds): array;

    /**
     * @param array $programIds
     * @param string $actionType
     * @throws \Exception
     */
    public function checkProgramIds(array $programIds, string $actionType): void;
}
