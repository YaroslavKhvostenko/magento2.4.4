<?php
declare(strict_types=1);

namespace ZP\LoyaltyProgram\Model\ResourceModel\LoyaltyProgram\Grid;

use ZP\LoyaltyProgram\Model\LoyaltyProgram;
use ZP\LoyaltyProgram\Model\ResourceModel\LoyaltyProgram\Collection as LoyaltyProgramCollection;
use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\Api\Search\AggregationInterface;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\View\Element\UiComponent\DataProvider\Document;
use Magento\Framework\Api\SearchCriteriaInterface;
use ZP\LoyaltyProgram\Setup\Patch\Data\AddBasicPrograms as BasicProgramsConfig;


class Collection extends LoyaltyProgramCollection implements SearchResultInterface
{
    /**
     * @var AggregationInterface
     */
    protected AggregationInterface $aggregations;

    private bool $customersInProgramFilterStatus = false;

    private array $customersInProgramFilter = [];

    /**
     * Collection constructor.
     * @param \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\EntityManager\MetadataPool $metadataPool
     * @param $mainTable
     * @param $eventPrefix
     * @param $eventObject
     * @param $resourceModel
     * @param string $model
     * @param null $connection
     * @param \Magento\Framework\Model\ResourceModel\Db\AbstractDb|null $resource
     */
    public function __construct(
        EntityFactoryInterface $entityFactory,
        LoggerInterface $logger,
        FetchStrategyInterface $fetchStrategy,
        ManagerInterface $eventManager,
        StoreManagerInterface $storeManager,
        MetadataPool $metadataPool,
        $mainTable,
        $eventPrefix,
        $eventObject,
        $resourceModel,
        $model = Document::class,
        $connection = null,
        AbstractDb $resource = null
    ) {
        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $connection,
            $resource
        );
        $this->_eventPrefix = $eventPrefix;
        $this->_eventObject = $eventObject;
        $this->_init($model, $resourceModel);
        $this->setMainTable($mainTable);
    }

    /**
     * @return AggregationInterface
     */
    public function getAggregations()
    {
        return $this->aggregations;
    }

    /**
     * @param AggregationInterface $aggregations
     * @return $this
     */
    public function setAggregations($aggregations)
    {
        $this->aggregations = $aggregations;
        return $this;
    }

    /**
     * Get search criteria.
     *
     * @return \Magento\Framework\Api\SearchCriteriaInterface|null
     */
    public function getSearchCriteria()
    {
        return null;
    }

    /**
     * Set search criteria.
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return $this
     */
    public function setSearchCriteria(SearchCriteriaInterface $searchCriteria = null)
    {
        return $this;
    }

    /**
     * Get total count.
     *
     * @return int
     */
    public function getTotalCount()
    {
        return $this->getSize();
    }

    /**
     * Set total count.
     *
     * @param int $totalCount
     * @return $this
     */
    public function setTotalCount($totalCount)
    {
        return $this;
    }

    /**
     * Set items list.
     *
     * @param \Magento\Framework\Api\ExtensibleDataInterface[] $items
     * @return $this
     */
    public function setItems(array $items = null)
    {
        return $this;
    }

    protected function _beforeLoad(): self
    {
        $this->addFieldToFilter(
            LoyaltyProgram::PROGRAM_ID,
            [
                'nin' => [BasicProgramsConfig::PROGRAM_MIN, BasicProgramsConfig::PROGRAM_MAX]
            ]
        );


        $this->getSelect()->joinLeft(
            'zp_loyalty_program_customer',
            'main_table.entity_id= ' . 'zp_loyalty_program_customer.program_id',
            ['number_of_customers_in_program' => 'COUNT(zp_loyalty_program_customer.customer_id)']
        );
        $this->getSelect()->group('main_table.entity_id');
        if ($this->customersInProgramFilterStatus) {
            if (count($this->customersInProgramFilter) === 2) {
                $havingSql = 'BETWEEN ' . array_shift($this->customersInProgramFilter) .
                    ' AND ' . array_shift($this->customersInProgramFilter);
            } else {
                $operator = array_key_first($this->customersInProgramFilter);
                $value = $this->customersInProgramFilter[$operator];
                $havingSql = $operator . ' ' . $value;
            }

            $this->getSelect()->having('COUNT(zp_loyalty_program_customer.customer_id) ' . $havingSql);
        }

        return parent::_beforeLoad();
    }

    public function addFieldToFilter($field, $condition = null)
    {
        if ($field === 'number_of_customers_in_program') {
            $this->customersInProgramFilterStatus = true;
            $conditionOperator = array_key_first($condition);
            if ($conditionOperator === 'gteq') {
                $this->customersInProgramFilter['>='] = $condition[$conditionOperator];
            } else {
                $this->customersInProgramFilter['<='] = $condition[$conditionOperator];
            }

            return $this;
        }

        return parent::addFieldToFilter($field, $condition);
    }

}
