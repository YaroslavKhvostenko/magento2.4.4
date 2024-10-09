<?php
declare(strict_types=1);

namespace ZP\LoyaltyProgram\Model\Source\Adminhtml\Program\Form\Fields\Field;

use ZP\LoyaltyProgram\Model\LoyaltyProgram;

class PreviousProgramOptions extends ReferenceProgramOptions
{
    /**
     * @param LoyaltyProgram $program
     * @return int|null
     * @throws \Exception
     */
    protected function getReferenceProgramId(LoyaltyProgram $program): ?int
    {
        return $program->getPreviousProgram();
    }
}
