<?php
declare(strict_types=1);

namespace ZP\LoyaltyProgram\Model;

use ZP\LoyaltyProgram\Api\LoyaltyProgramRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use ZP\LoyaltyProgram\Api\Data\LoyaltyProgramInterface;
use ZP\LoyaltyProgram\Api\Data\LoyaltyProgramInterfaceFactory;
use ZP\LoyaltyProgram\Api\Data\LoyaltyProgramSearchResultsInterface;
use ZP\LoyaltyProgram\Model\ResourceModel\LoyaltyProgram as LoyaltyProgramResource;
use ZP\LoyaltyProgram\Model\ResourceModel\LoyaltyProgram\Collection;
use ZP\LoyaltyProgram\Model\ResourceModel\LoyaltyProgram\CollectionFactory as LoyaltyProgramCollectionFactory;
use ZP\LoyaltyProgram\Api\Data\LoyaltyProgramSearchResultsInterfaceFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;

class LoyaltyProgramRepository implements LoyaltyProgramRepositoryInterface
{
    public function __construct(
        private LoyaltyProgramResource $loyaltyProgramResource,
        private LoyaltyProgramInterfaceFactory $loyaltyProgramFactory,
        private LoyaltyProgramCollectionFactory $loyaltyProgramCollectionFactory,
        private LoyaltyProgramSearchResultsInterfaceFactory $loyaltyProgramSearchResultsFactory,
        private CollectionProcessorInterface $collectionProcessor
    ) {}

    /**
     * @param LoyaltyProgramInterface $loyaltyProgram
     * @return LoyaltyProgramInterface
     * @throws CouldNotSaveException
     */
    public function save(LoyaltyProgramInterface $loyaltyProgram): LoyaltyProgramInterface
    {
        try {
            $this->loyaltyProgramResource->save($loyaltyProgram);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()));
        }

        return $loyaltyProgram;
    }

    /**
     * @param int $programId
     * @return LoyaltyProgramInterface
     * @throws NoSuchEntityException
     * @throws \Exception
     */
    public function get(int $programId): LoyaltyProgramInterface
    {
        $loyaltyProgram = $this->loyaltyProgramFactory->create();
        $this->loyaltyProgramResource->load($loyaltyProgram, $programId, LoyaltyProgramInterface::PROGRAM_ID);
        if (!$loyaltyProgram->getProgramId()) {
            throw new NoSuchEntityException(__(
                "LoyaltyProgram entity with the \"program_id\" doesn't exist.", $programId
            ));
        }

        return $loyaltyProgram;
    }

    /**
     * @param LoyaltyProgramInterface $loyaltyProgram
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(LoyaltyProgramInterface $loyaltyProgram): bool
    {
        try {
            $this->loyaltyProgramResource->delete($loyaltyProgram);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__($exception->getMessage()));
        }

        return true;
    }

    /**
     * @param int $programId
     * @return bool
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     */
    public function deleteById(int $programId): bool
    {
        return $this->delete($this->get($programId));
    }

    /**
     * @param SearchCriteriaInterface $searchCriteria
     * @return LoyaltyProgramSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): LoyaltyProgramSearchResultsInterface
    {
        /** @var Collection $collection */
        $collection = $this->loyaltyProgramCollectionFactory->create();

        /** @var LoyaltyProgramSearchResultsInterface $searchResult */
        $searchResult = $this->loyaltyProgramSearchResultsFactory->create();
        $searchResult->setSearchCriteria($searchCriteria);
        $this->collectionProcessor->process($searchCriteria, $collection);
        $searchResult->setItems($collection->getItems());
        $searchResult->setTotalCount($collection->getSize());

        return $searchResult;
    }
}
