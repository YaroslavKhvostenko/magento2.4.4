<?php
declare(strict_types=1);

namespace ZP\LoyaltyProgram\Model\MessageManager\Console\Command\Customer\AssignLoyaltyProgram;

use ZP\LoyaltyProgram\Model\MessageManager\Customer\LoyaltyProgramManagement\MessageManager as BaseMessageManager;

class MessageManager extends BaseMessageManager
{
    public const NOT_EXIST = 'not_exist';
    public const WRONG_DATA = 'wrong_data';
    private const NOT_EXIST_MSG = 'Customer(s) with such id(s) not exist : ';
    private const WRONG_DATA_MSG = 'Data type of this customer(s) id(s) not correct : ';
    private const RESULTS = [
        self::WRONG_DATA => [],
        self::NOT_EXIST => []
    ];
    private const RESULT_MSGS = [
        self::NOT_EXIST => self::NOT_EXIST_MSG,
        self::WRONG_DATA => self::WRONG_DATA_MSG,
    ];

    public function __construct()
    {
        parent::__construct();
        $this->results = array_merge($this->results, self::RESULTS);
        $this->resultMsgs = array_merge($this->resultMsgs, self::RESULT_MSGS);
    }
}
