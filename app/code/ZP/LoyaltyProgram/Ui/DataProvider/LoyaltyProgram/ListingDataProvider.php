<?php
declare(strict_types=1);

namespace ZP\LoyaltyProgram\Ui\DataProvider\LoyaltyProgram;

use Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider;
use Magento\Framework\Api\Filter;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\Reporting;
use ZP\LoyaltyProgram\Model\LoyaltyProgram;

class ListingDataProvider extends DataProvider
{
    public const MAIN_TABLE_ALIAS = 'main_table';

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param Reporting $reporting
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param RequestInterface $request
     * @param FilterBuilder $filterBuilder
     * @param array $meta
     * @param array $data
     * @param array $additionalFilterPool
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        Reporting $reporting,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        RequestInterface $request,
        FilterBuilder $filterBuilder,
        array $meta = [],
        array $data = [],
        private array $additionalFilterPool = []
    ) {
        parent::__construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $reporting,
            $searchCriteriaBuilder,
            $request,
            $filterBuilder,
            $meta,
            $data
        );
    }

    /**
     * @param Filter $filter
     * @return mixed|void
     */
    public function addFilter(Filter $filter)
    {
        if (!empty($this->additionalFilterPool[$filter->getField()])) {
            $this->additionalFilterPool[$filter->getField()]->addFilter($this->searchCriteriaBuilder, $filter);
        } else {
            parent::addFilter($filter);
        }
    }

    public function getPrimaryFieldName(): string
    {
        $field = parent::getPrimaryFieldName();
        if ($field === LoyaltyProgram::PROGRAM_ID) {
            $field = self::MAIN_TABLE_ALIAS. '.' . $field;
        }

        return $field;
    }
}
