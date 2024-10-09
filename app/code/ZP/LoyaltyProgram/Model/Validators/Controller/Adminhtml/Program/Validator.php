<?php
declare(strict_types=1);

namespace ZP\LoyaltyProgram\Model\Validators\Controller\Adminhtml\Program;

use ZP\LoyaltyProgram\Model\LoyaltyProgram;
use ZP\LoyaltyProgram\Model\Validators\Data\Validator as BaseValidator;
use ZP\LoyaltyProgram\Api\Model\Validators\Controller\Adminhtml\Program\ValidatorInterface;
use ZP\LoyaltyProgram\Setup\Patch\Data\AddBasicPrograms as BasicProgramsConfig;

class Validator extends BaseValidator implements ValidatorInterface
{
    /**
     * @param mixed $programId
     * @return int
     * @throws \Exception
     */
    public function validateProgramId(mixed $programId): int
    {
        if (!$this->isDataInteger($programId)) {
            $programId = $this->getExceptionValues($programId);
            throw new \Exception(
                'Wrong data type of \'' . LoyaltyProgram::PROGRAM_ID . '\'. ' .
                'It must be integer like 1 or integer in string like \'1\'! ' .
                'You received : \'' . $programId . '\'!'
            );
        }

        return (int)$programId;
    }

    /**
     * @param int|null $programId
     * @return bool
     */
    public function isBasicProgram(?int $programId): bool
    {
        return $programId === BasicProgramsConfig::PROGRAM_MIN || $programId === BasicProgramsConfig::PROGRAM_MAX;
    }
}
