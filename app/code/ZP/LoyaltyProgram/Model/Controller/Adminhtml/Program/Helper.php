<?php
declare(strict_types=1);

namespace ZP\LoyaltyProgram\Model\Controller\Adminhtml\Program;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use ZP\LoyaltyProgram\Model\LoyaltyProgram;
use ZP\LoyaltyProgram\Setup\Patch\Data\AddBasicPrograms as BasicProgramsConfig;

class Helper
{
    private AdapterInterface $connection;
    private ?int $activeProgramsCount = null;
    private ?int $existProgramsCount = null;

    public function __construct(ResourceConnection $resourceConnection)
    {
        $this->connection = $resourceConnection->getConnection();
    }

    /**
     * @param string|null $filterField
     * @return int
     * @throws \Exception
     */
    private function selectProgramsCount(string $filterField = null): int
    {
        $select = $this->connection->select()
            ->from(
                LoyaltyProgram::MAIN_TABLE,
                'COUNT(' . LoyaltyProgram::PROGRAM_ID . ')'
            )->where(
                LoyaltyProgram::PROGRAM_ID,
                [BasicProgramsConfig::PROGRAM_MIN, BasicProgramsConfig::PROGRAM_MAX],
                'nin'
            );

        if ($filterField !== null) {
            if ($filterField === LoyaltyProgram::IS_ACTIVE) {
                $select->where(LoyaltyProgram::IS_ACTIVE . ' = ' . LoyaltyProgram::ACTIVE);
            } else {
                $this->throwException($filterField);
            }
        }


        $result = $this->connection->fetchOne($select);
        if ($result === false || $result === null) {
            $this->throwException();
        }

        return (int)$result;
    }

    /**
     * @return int
     * @throws \Exception
     */
    public function getActiveProgramsCount(): int
    {
        if ($this->activeProgramsCount === null) {
            $this->activeProgramsCount = $this->selectProgramsCount(LoyaltyProgram::IS_ACTIVE);
        }

        return $this->activeProgramsCount;
    }

    /**
     * @return int
     * @throws \Exception
     */
    public function getExistProgramsCount(): int
    {
        if ($this->existProgramsCount === null) {
            $this->existProgramsCount = $this->selectProgramsCount();
        }

        return $this->existProgramsCount;
    }

    /**
     * @param string|null $fieldName
     * @throws \Exception
     */
    private function throwException(?string $fieldName = null): void
    {
        $msg = 'Sql Problem during COUNT \'' . LoyaltyProgram::PROGRAM_ID . '\'' .
            ' from \'' . LoyaltyProgram::MAIN_TABLE . '\' table!';
        if ($fieldName !== null) {
            $msg .= ' Field : \'' . $fieldName . '\' not exist in \'' . LoyaltyProgram::MAIN_TABLE . '\' table' .
                ' or not available to use in filter!';
        }

        throw new \Exception($msg);
    }
}
