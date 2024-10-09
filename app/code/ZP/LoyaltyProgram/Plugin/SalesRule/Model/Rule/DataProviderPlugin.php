<?php
declare(strict_types=1);

namespace ZP\LoyaltyProgram\Plugin\SalesRule\Model\Rule;

use Magento\SalesRule\Model\Rule\DataProvider as SalesRuleDataProvider;
use ZP\LoyaltyProgram\Model\Prepare\Data\DataPreparer;
use ZP\LoyaltyProgram\Model\Validators\Data\Validator;

class DataProviderPlugin
{
    public const LOYALTY_PROGRAM_IDS = 'loyalty_program_ids';
    public const IS_LOYALTY_RULE = 'is_loyalty_rule';

    public function __construct(private Validator $dataValidator, private DataPreparer $prepareData)
    {}

    public function afterGetData(SalesRuleDataProvider $subject, ?array $loadedData)
    {
        if ($loadedData) {
            $saleRuleId = array_key_first($loadedData);
            $isLoyaltyRule = (bool)$loadedData[$saleRuleId][self::IS_LOYALTY_RULE];
            if ($isLoyaltyRule) {
                $ruleProgramIds = $loadedData[$saleRuleId][self::LOYALTY_PROGRAM_IDS];
                $ruleProgramIds = $this->dataValidator->validateMultiselectFieldIntData(
                    $ruleProgramIds, self::LOYALTY_PROGRAM_IDS, 'SalesRule'
                );
                $ruleProgramIds = $ruleProgramIds ? $this->prepareData->arrayValuesToString($ruleProgramIds) : null;
            } else {
                $ruleProgramIds = null;
            }

            $loadedData[$saleRuleId][self::LOYALTY_PROGRAM_IDS] = $ruleProgramIds;
        }

        return $loadedData;
    }
}
