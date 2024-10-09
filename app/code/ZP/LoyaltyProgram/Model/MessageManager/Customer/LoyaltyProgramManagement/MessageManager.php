<?php
declare(strict_types=1);

namespace ZP\LoyaltyProgram\Model\MessageManager\Customer\LoyaltyProgramManagement;


class MessageManager
{
    public const REMOVED = 'removed';
    public const ASSIGNED = 'assigned';
    public const UPDATED = 'updated';
    public const UNABLE = 'unable';
    public const NO_NEED = 'no_need';
    private const REMOVED_MSG = 'Program(s) removed of such customer(s) id(s) : ';
    private const ASSIGNED_MSG = 'Assigned program(s) to customer(s) with id(s) : ';
    private const UPDATED_MSG = 'Updated programs(s) to customer(s) with id(s) : ';
    private const UNABLE_MSG = 'Unable to assign program(s) to customer(s) with id(s) : ';
    private const NO_NEED_MSG = 'No need to assign program(s) to customer(s) with id(s) : ';
    protected string $resultMsg = 'Result of program(s) assign(reassign) to customer(s) is : ' . "\n";
    private const RESULTS = [
        self::REMOVED => [],
        self::ASSIGNED => [],
        self::UPDATED => [],
        self::UNABLE => [],
        self::NO_NEED => []
    ];
    private const RESULT_MSGS = [
        self::REMOVED => self::REMOVED_MSG,
        self::ASSIGNED => self::ASSIGNED_MSG,
        self::UPDATED => self::UPDATED_MSG,
        self::UNABLE => self::UNABLE_MSG,
        self::NO_NEED => self::NO_NEED_MSG
    ];
    protected array $results = [];
    protected array $resultMsgs = [];

    public function __construct()
    {
        $this->results = self::RESULTS;
        $this->resultMsgs = self::RESULT_MSGS;
    }

    public function setResultValue(string $resultType, mixed $value): void
    {
        if (!array_key_exists($resultType, $this->results)) {
            throw new \Exception(
                'Wrong $resultType : \'' . $resultType . '\' came into MessageManager!'
            );
        }

        $this->results[$resultType][$value] = $value;
    }

    public function getResultMessage(?string $resultType = null): string
    {
        $this->prepareResultMessage($resultType);

        return $this->resultMsg;
    }

    protected function prepareResultMessage(?string $resultType): void
    {
        if (!$resultType) {
            foreach ($this->results as $resultKey => $resultArray) {
                $this->prepareResultMessage($resultKey);
            }
        } else {
            if ($this->results[$resultType]) {
                $this->resultMsg .= $this->resultMsgs[$resultType];
                $resultCount = count($this->results[$resultType]);
                $i = 1;
                foreach ($this->results[$resultType] as $customerId) {
                    $this->resultMsg .= $customerId;
                    $this->resultMsg .= $i === $resultCount ? '.' . "\n" : ', ';
                    $i++;
                }
            }
        }
    }

    public function isResultTypeEmpty(string $resultType): bool
    {
        return !$this->results[$resultType];
    }
}
