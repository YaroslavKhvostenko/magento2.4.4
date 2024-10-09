<?php

namespace ZP\LoyaltyProgram\Api;

use Magento\Quote\Model\Quote;
use ZP\LoyaltyProgram\Api\Data\LoyaltyProgramInterface;
use Magento\Customer\Api\Data\CustomerInterface;

interface LoyaltyProgramManagementInterface
{
    public const SERVICE_CLASS = 'ZP\LoyaltyProgram\Api\LoyaltyProgramManagementInterface';
    public const ASSIGN_LOYALTY_PROGRAM_SERVICE_METHOD = 'assignLoyaltyProgram';

    /**
     * @param CustomerInterface $customer
     * @return LoyaltyProgramInterface|null
     * @throws \Exception
     */
    public function assignLoyaltyProgram(CustomerInterface $customer): ?LoyaltyProgramInterface;

    /**
     * @param CustomerInterface|Quote $entity
     * @param int $websiteId
     * @param int $customerGroupId
     * @return bool
     * @throws \Exception
     */
    public function checkProgramConditions(
        CustomerInterface|Quote $entity,
        int $websiteId,
        int $customerGroupId
    ): bool;

    /**
     * @return LoyaltyProgramInterface|null
     */
    public function returnCustomerProgram(): ?LoyaltyProgramInterface;
}
