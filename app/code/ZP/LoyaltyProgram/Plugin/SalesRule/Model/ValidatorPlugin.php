<?php
declare(strict_types=1);

namespace ZP\LoyaltyProgram\Plugin\SalesRule\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\SalesRule\Model\Rule;
use Magento\SalesRule\Model\Validator;
use Magento\Store\Model\StoreManagerInterface;
use ZP\LoyaltyProgram\Model\Configs\Program\Scope\Config as ProgramScopeConfig;
use Magento\SalesRule\Model\ResourceModel\Rule\Collection as RuleCollection;
use ZP\LoyaltyProgram\Model\LoyaltyProgram;
use ZP\LoyaltyProgram\Plugin\SalesRule\Model\Rule\DataProviderPlugin as RuleConfig;
use ZP\LoyaltyProgram\Model\ResourceModel\LoyaltyProgram\CollectionFactory as ProgramsCollectionFactory;
use Magento\SalesRule\Model\ResourceModel\Rule as RuleResource;
use ZP\LoyaltyProgram\Model\Validators\Data\Validator as DataValidator;
use ZP\LoyaltyProgram\Model\Prepare\Data\DataPreparer;

class ValidatorPlugin
{
    private bool $ruleNeedToUpdate;

    public function __construct(
        private StoreManagerInterface $storeManager,
        private ProgramScopeConfig $programScopeConfig,
        private ProgramsCollectionFactory $programsCollectionFactory,
        private RuleResource $ruleResource,
        private DataValidator $dataValidator,
        private DataPreparer $prepareData
    ) {}

    /**
     * @param Validator $subject
     * @param RuleCollection $ruleCollection
     * @return RuleCollection
     * @throws LocalizedException
     */
    public function afterGetRules(Validator $subject, RuleCollection $ruleCollection)
    {
       if ($ruleCollection->getSize()) {
           $this->processRules($ruleCollection);
       }

        return $ruleCollection;
    }

    /**
     * @param RuleCollection $collection
     * @throws LocalizedException
     * @throws \Exception
     */
    private function processRules(RuleCollection $collection): void
    {
        if($this->isProgramEnabled()) {
            /** @var Rule $rule */
            foreach ($collection->getItems() as $rule) {
                $this->processRule($rule);
            }
        } else {
            /**
             * @var int $ruleId
             * @var Rule $rule
             */
            foreach ($collection->getItems() as $ruleId => $rule) {
                if ($rule->getData(RuleConfig::IS_LOYALTY_RULE)) {
                    $collection->removeItemByKey($ruleId);
                }
            }
        }
    }

    /**
     * @return bool
     * @throws LocalizedException
     */
    private function isProgramEnabled(): bool
    {
        return $this->programScopeConfig->isEnabled((int)$this->storeManager->getWebsite()->getId());
    }

    /**
     * @param Rule $rule
     * @throws \Exception
     */
    private function processRule(Rule $rule): void
    {
        if ($rule->getData(RuleConfig::IS_LOYALTY_RULE)) {
            $this->ruleNeedToUpdate = false;
            $ruleProgramIds = $rule->getData(RuleConfig::LOYALTY_PROGRAM_IDS);
            $ruleProgramIds = $this->dataValidator->validateMultiselectFieldIntData(
                $ruleProgramIds, RuleConfig::LOYALTY_PROGRAM_IDS, 'SalesRule'
            );

            if ($ruleProgramIds) {
                $ruleProgramIds = $this->checkLoyaltyPrograms($this->prepareData->makeArrayKeysLikeValues($ruleProgramIds));
                if ($this->ruleNeedToUpdate) {
                    $rule->setData(
                        RuleConfig::LOYALTY_PROGRAM_IDS,
                        $ruleProgramIds ? implode(',', $ruleProgramIds) : null
                    );

                    $this->ruleResource->save($rule);
                }
            }
        }
    }

    private function checkLoyaltyPrograms(array $ruleProgramIds): array
    {
        $programsCollection = $this->programsCollectionFactory->create();
        $programsCollection->addFieldToFilter(LoyaltyProgram::PROGRAM_ID, ['in' => $ruleProgramIds]);

        $validProgramIds = array_map(function($program) {
            return (int)$program->getId();
        }, $programsCollection->getItems());

        if (count($validProgramIds) !== count($ruleProgramIds)) {
            $this->ruleNeedToUpdate = true;
        }

        return array_values(array_intersect($ruleProgramIds, $validProgramIds));
    }
}
