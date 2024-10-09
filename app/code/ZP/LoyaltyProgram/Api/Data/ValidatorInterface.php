<?php

namespace ZP\LoyaltyProgram\Api\Data;

interface ValidatorInterface
{
    /**
     * @param mixed $data
     * @return bool
     */
    public function isDataInteger(mixed $data): bool;
}
