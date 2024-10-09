<?php
declare(strict_types=1);

namespace ZP\LoyaltyProgram\Model\Registry\Observer\Customer\Model\ResourceModel\Customer\AfterLoad;

class Register
{
    private bool $flag = false;

    public function setFlag(bool $flag)
    {
        $this->flag = $flag;
    }

    public function getFlag(): bool
    {
        return $this->flag;
    }
}
