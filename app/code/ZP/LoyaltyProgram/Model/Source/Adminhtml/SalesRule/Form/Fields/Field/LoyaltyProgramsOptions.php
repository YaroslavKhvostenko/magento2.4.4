<?php
declare(strict_types=1);

namespace ZP\LoyaltyProgram\Model\Source\Adminhtml\SalesRule\Form\Fields\Field;

use Magento\Framework\Data\OptionSourceInterface;
use ZP\LoyaltyProgram\Model\ResourceModel\LoyaltyProgram\CollectionFactory as LoyaltyProgramCollectionFactory;
use ZP\LoyaltyProgram\Model\ResourceModel\LoyaltyProgram\Collection as LoyaltyProgramCollection;
use ZP\LoyaltyProgram\Model\LoyaltyProgram;

class LoyaltyProgramsOptions implements OptionSourceInterface
{
    public function __construct(private LoyaltyProgramCollectionFactory $loyaltyProgramCollectionFactory)
    {}

    /**
     * @return array
     * @throws \Exception
     */
    public function toOptionArray()
    {
        return $this->getData();
    }

    /**
     * @return array
     * @throws \Exception
     */
    protected function getData(): array
    {
        $data = [];

        /** @var LoyaltyProgramCollection $collection */
        $collection = $this->loyaltyProgramCollectionFactory->create()->excludeBasicPrograms();

        /** @var LoyaltyProgram $loyaltyProgram */
        foreach ($collection->getItems() as $loyaltyProgram) {
            $data[] = ['label' => __($loyaltyProgram->getProgramName()), 'value' => $loyaltyProgram->getProgramId()];
        }

        return $data;
    }
}
