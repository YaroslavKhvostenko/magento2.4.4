<?php
declare(strict_types=1);

namespace ZP\LoyaltyProgram\Api\Model\Validators\Controller\Adminhtml\Program;

use ZP\LoyaltyProgram\Api\Data\ValidatorInterface as BaseValidatorInterface;

interface ValidatorInterface extends BaseValidatorInterface
{
    /**
     * @param mixed $programId
     * @return int
     * @throws \Exception
     */
    public function validateProgramId(mixed $programId): int;

    /**
     * @param int|null $programId
     * @return bool
     */
    public function isBasicProgram(?int $programId): bool;
}
