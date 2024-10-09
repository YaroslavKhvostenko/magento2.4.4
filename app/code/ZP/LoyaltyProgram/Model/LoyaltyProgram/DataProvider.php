<?php
declare(strict_types=1);

namespace ZP\LoyaltyProgram\Model\LoyaltyProgram;

use Magento\Ui\DataProvider\Modifier\PoolInterface;
use Magento\Ui\DataProvider\ModifierPoolDataProvider;
use ZP\LoyaltyProgram\Model\LoyaltyProgram;
use ZP\LoyaltyProgram\Model\Prepare\Data\DataPreparer;
use ZP\LoyaltyProgram\Model\ResourceModel\LoyaltyProgram\CollectionFactory;
use ZP\LoyaltyProgram\Setup\Patch\Data\AddBasicPrograms as BasicProgramsConfig;

class DataProvider extends ModifierPoolDataProvider
{
    private ?array $loadedData = null;

    /**
     * DataProvider constructor.
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param DataPreparer $prepareData
     * @param array $meta
     * @param array $data
     * @param PoolInterface|null $pool
     */
    public function __construct(
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        private CollectionFactory $collectionFactory,
        private DataPreparer $prepareData,
        array $meta = [],
        array $data = [],
        PoolInterface $pool = null
    ) {
        $this->collection = $collectionFactory->create();
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data, $pool);
    }

    /**
     * @return array|null
     * @throws \Exception
     */
    public function getData(): ?array
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }

        /** @var LoyaltyProgram $item */
        $item = $this->collection->getFirstItem();
        $this->setProgramMinMaxStatus($item, LoyaltyProgram::PREVIOUS_PROGRAM);
        $this->setProgramMinMaxStatus($item, LoyaltyProgram::NEXT_PROGRAM);

        if ($item->getWebsiteId() === null) {
            $item->setData(LoyaltyProgram::WEBSITE_ID, '');
        }

        $customerGroupIds = $this->prepareData->arrayValuesToString($item->getCustomerGroupIds());
        $item->setData(LoyaltyProgram::CUSTOMER_GROUP_IDS, $customerGroupIds);
        $this->loadedData[$item->getId()] = $item->getData();

        return $this->loadedData;
    }

    /**
     * @param LoyaltyProgram $item
     * @param string $referenceDirection
     * @throws \Exception
     */
    private function setProgramMinMaxStatus(LoyaltyProgram $item, string $referenceDirection): void
    {
        $referenceProgramId = '';
        $additionalFieldToForm = '';
        $basicProgramId = -1;
        switch ($referenceDirection) {
            case LoyaltyProgram::NEXT_PROGRAM :
                $referenceProgramId = $item->getNextProgram();
                $additionalFieldToForm = LoyaltyProgram::IS_PROGRAM_MAXIMUM;
                $basicProgramId = BasicProgramsConfig::PROGRAM_MAX;
                break;
            case LoyaltyProgram::PREVIOUS_PROGRAM :
                $referenceProgramId = $item->getPreviousProgram();
                $additionalFieldToForm = LoyaltyProgram::IS_PROGRAM_MINIMUM;
                $basicProgramId = BasicProgramsConfig::PROGRAM_MIN;
                break;
            default :
                return;
        }

        if ($referenceProgramId === null || $referenceProgramId !== $basicProgramId) {
            $item->setData($additionalFieldToForm, '0');
        } else {
            $item->setData($additionalFieldToForm, '1');
        }
    }
}
