<?php
declare(strict_types=1);

namespace ZP\LoyaltyProgram\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

interface LoyaltyProgramSearchResultsInterface extends SearchResultsInterface
{
    /**
     * Get posts
     *
     * @return LoyaltyProgramInterface[]
     */
    public function getItems();

    /**
     * Set posts
     *
     * @param LoyaltyProgramInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
