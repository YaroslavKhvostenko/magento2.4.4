<?php
declare(strict_types=1);

namespace ZP\LoyaltyProgram\Plugin\WebApi\Controller\Rest;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Webapi\Controller\Rest\InputParamsResolver;
use Magento\Customer\Api\CustomerRepositoryInterface;
use ZP\LoyaltyProgram\Api\LoyaltyProgramManagementInterface;

class InputParamsResolverPlugin
{
    public function __construct(private CustomerRepositoryInterface $customerRepository)
    {}

    public function afterResolve(InputParamsResolver $subject, array $data): array
    {
        $route = $subject->getRoute();
        if (
            $this->isCorrectServiceClassAndMethod($route->getServiceClass(), $route->getServiceMethod())) {
            /** @var CustomerInterface $customer */
            $customer = array_shift($data);
            $customer = $this->customerRepository->getById($customer->getId());
            $data[] = $customer;
        }

        return $data;
    }

    private function isCorrectServiceClassAndMethod(string $serviceClass, string $serviceMethod): bool
    {
        return $serviceClass === LoyaltyProgramManagementInterface::SERVICE_CLASS &&
            $serviceMethod === LoyaltyProgramManagementInterface::ASSIGN_LOYALTY_PROGRAM_SERVICE_METHOD;
    }
}
