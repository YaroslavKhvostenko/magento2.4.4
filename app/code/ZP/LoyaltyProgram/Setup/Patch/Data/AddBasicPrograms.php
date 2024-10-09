<?php

namespace ZP\LoyaltyProgram\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use ZP\LoyaltyProgram\Api\Data\LoyaltyProgramInterface;
use Magento\Framework\App\ResourceConnection;

class AddBasicPrograms implements DataPatchInterface
{
    public const PROGRAM_MIN = 1;
    public const PROGRAM_MAX = 2;
    public const PROGRAM_MIN_NAME = 'Program Minimum';
    public const PROGRAM_MAX_NAME = 'Program Maximum';

    public function __construct(private ResourceConnection $resourceConnection)
    {}

    /**
     * @return DataPatchInterface
     */
    public function apply(): DataPatchInterface
    {
        $connection = $this->resourceConnection->getConnection();
        $connection->insert(
            LoyaltyProgramInterface::MAIN_TABLE,
            [
                LoyaltyProgramInterface::PROGRAM_ID => self::PROGRAM_MIN,
                LoyaltyProgramInterface::PROGRAM_NAME => self::PROGRAM_MIN_NAME
            ]
        );
        $connection->insert(
            LoyaltyProgramInterface::MAIN_TABLE,
            [
                LoyaltyProgramInterface::PROGRAM_ID => self::PROGRAM_MAX,
                LoyaltyProgramInterface::PROGRAM_NAME => self::PROGRAM_MAX_NAME
            ]
        );

        return $this;
    }

    /**
     * @inheritDoc
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getAliases(): array
    {
        return [];
    }
}
