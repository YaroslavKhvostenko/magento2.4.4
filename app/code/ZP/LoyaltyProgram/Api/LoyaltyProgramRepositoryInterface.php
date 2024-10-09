<?php

namespace ZP\LoyaltyProgram\Api;

use ZP\LoyaltyProgram\Api\Data\LoyaltyProgramInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotDeleteException;
use ZP\LoyaltyProgram\Api\Data\LoyaltyProgramSearchResultsInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

interface LoyaltyProgramRepositoryInterface
{
    /**
     * @param LoyaltyProgramInterface $loyaltyProgram
     * @return LoyaltyProgramInterface
     * @throws CouldNotSaveException
     */
    public function save(LoyaltyProgramInterface $loyaltyProgram): LoyaltyProgramInterface;

    /**
     * @param int $programId
     * @return LoyaltyProgramInterface
     * @throws NoSuchEntityException
     * @throws \Exception
     */
    public function get(int $programId): LoyaltyProgramInterface;

    /**
     * @param LoyaltyProgramInterface $loyaltyProgram
     * @return bool true on success
     * @throws CouldNotDeleteException
     */
    public function delete(LoyaltyProgramInterface $loyaltyProgram): bool;

    /**
     * @param int $programId
     * @return bool true on success
     * @throws NoSuchEntityException
     * @throws CouldNotDeleteException
     */
    public function deleteById(int $programId): bool;

    /**
     * @param SearchCriteriaInterface $searchCriteria
     * @return LoyaltyProgramSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): LoyaltyProgramSearchResultsInterface;
}
