<?php
declare(strict_types=1);

namespace ZP\LoyaltyProgram\Model\Controller\Adminhtml\Program;

use Magento\SalesRule\Model\Rule;
use ZP\LoyaltyProgram\Model\Prepare\Data\DataPreparer;
use ZP\LoyaltyProgram\Plugin\SalesRule\Model\Rule\DataProviderPlugin as SalesRuleLoyaltyProgramsConfig;
use Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory as RuleCollectionFactory;
use Magento\SalesRule\Model\ResourceModel\Rule\Collection as RuleCollection;

class SalesRuleProgramsManagement
{
    private RuleCollection $ruleCollection;
    private int $rulesCount = 0;
    private array $programIdsToDelete = [];

    public function __construct(
        private DataPreparer $prepareData,
        private RuleCollectionFactory $ruleCollectionFactory
    ) {}

    /**
     * @param array $programIds
     */
    public function collectRules(array $programIds): void
    {
        $this->programIdsToDelete = $this->prepareData->arrayValuesToInteger($programIds);
        $this->ruleCollection = $this->getRuleCollection();
        $this->rulesCount = $this->ruleCollection->getSize();
    }

    public function deleteProgramsFromSalesRules(): void
    {
        /**@var Rule $rule */
        foreach ($this->ruleCollection as $rule) {
            $ruleProgramIds = $this->getRuleProgramIds($rule);
            if ($ruleProgramIds) {
                $counter = 0;
                foreach ($this->programIdsToDelete as $programId) {
                    if (array_key_exists($programId, $ruleProgramIds)) {
                        unset($ruleProgramIds[$programId]);
                        $counter++;
                    }
                }

                $ruleProgramIds = $ruleProgramIds ? implode(',', $ruleProgramIds) : null;
                $rule->setData(SalesRuleLoyaltyProgramsConfig::LOYALTY_PROGRAM_IDS, $ruleProgramIds);
            }
        }

        $this->ruleCollection->save();
    }

    public function getRulesCount(): int
    {
        return $this->rulesCount;
    }

    /**
     * @return RuleCollection
     */
    private function getRuleCollection(): RuleCollection
    {
        $condition = [];
        foreach ($this->programIdsToDelete as $programId) {
            $condition[] = ['like' => '%'.$programId.'%'];
        }

        return $this->ruleCollectionFactory->create()
            ->addFieldToFilter(
                SalesRuleLoyaltyProgramsConfig::LOYALTY_PROGRAM_IDS,
                $condition
            );
    }

    private function getRuleProgramIds(Rule $rule): array
    {
        $programIds = (array)$rule->getData(SalesRuleLoyaltyProgramsConfig::LOYALTY_PROGRAM_IDS);

        if ($programIds) {
            return $this->prepareData->makeArrayKeysLikeValues(
                $this->prepareData->arrayValuesToInteger(
                    $this->prepareData->explodeArray($programIds)
                )
            );
        }

        return $programIds;
    }
}
