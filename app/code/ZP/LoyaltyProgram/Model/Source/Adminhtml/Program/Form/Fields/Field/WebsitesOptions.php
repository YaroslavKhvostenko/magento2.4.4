<?php
declare(strict_types=1);

namespace ZP\LoyaltyProgram\Model\Source\Adminhtml\Program\Form\Fields\Field;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Website;

class WebsitesOptions implements OptionSourceInterface
{
    public function __construct(private StoreManagerInterface $storeManager)
    {}

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return $this->getData();
    }

    protected function getData(): array
    {
        $data[] = ['label' => __('-- Please Select --'), 'value' => ''];
        /** @var Website $website */
        foreach ($this->storeManager->getWebsites() as $website) {
            $data[] = ['label' => __($website->getName()), 'value' => $website->getId()];
        }


        return $data;
    }
}
