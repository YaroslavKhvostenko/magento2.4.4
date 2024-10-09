<?php
declare(strict_types=1);

namespace ZP\LoyaltyProgram\Model;

use Magento\Framework\Model\AbstractModel;
use ZP\LoyaltyProgram\Api\Data\LoyaltyProgramInterface;
use ZP\LoyaltyProgram\Model\ResourceModel\LoyaltyProgram as LoyaltyProgramResourceModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Data\Collection\AbstractDb;
use ZP\LoyaltyProgram\Model\Validators\Data\Validator as DataValidator;

class LoyaltyProgram extends AbstractModel implements LoyaltyProgramInterface
{
    public const ACTIVE = 1;
    public const NOT_ACTIVE = 0;
    public const ENTITY = 'LoyaltyProgram';

    public function __construct(
        private DataValidator $dataValidator,
        Context $context,
        Registry $registry,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Initialize resource model
     * @return void
     */
    protected function _construct()
    {
        $this->_init(LoyaltyProgramResourceModel::class);
    }

    /**
     * @return int|null
     * @throws \Exception
     */
    public function getProgramId(): ?int
    {
        return $this->gettingIntegerData(self::PROGRAM_ID);
    }

    /**
     * @param int|string $programId
     * @return LoyaltyProgramInterface
     * @throws \Exception
     */
    public function setProgramId(int|string $programId): LoyaltyProgramInterface
    {
        if (!$this->dataValidator->isDataInteger($programId)) {
            $this->throwExceptionSetData(self::PROGRAM_ID);
        }

        return $this->setData(self::PROGRAM_ID, (int)$programId);
    }


    /**
     * @return string|null
     */
    public function getProgramName(): ?string
    {
        return $this->getData(self::PROGRAM_NAME);
    }

    /**
     * @param string $programName
     * @return LoyaltyProgramInterface
     */
    public function setProgramName(string $programName): LoyaltyProgramInterface
    {
        return $this->setData(self::PROGRAM_NAME, $programName);
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function getIsActive(): bool
    {
        $isActive = $this->getData(self::IS_ACTIVE);
        if ($isActive != '0' && $isActive != '1') {
            $this->throwExceptionGetData(self::IS_ACTIVE);
        }

        return (bool)$isActive;
    }

    /**
     * @param int|string|bool $isActive
     * @return LoyaltyProgramInterface
     * @throws \Exception
     */
    public function setIsActive(int|string|bool $isActive): LoyaltyProgramInterface
    {
        if (is_int($isActive) || is_string($isActive) && ($isActive != '0' && $isActive != '1')) {
            $this->throwExceptionSetData(self::IS_ACTIVE);
        }

        return $this->setData(self::IS_ACTIVE, (bool)$isActive);
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->getData(self::DESCRIPTION);
    }

    /**
     * @param string $description
     * @return LoyaltyProgramInterface
     */
    public function setDescription(string $description): LoyaltyProgramInterface
    {
        return $this->setData(self::DESCRIPTION, $description);
    }

    /**
     * @return string|null
     */
    public function getConditionsSerialized(): ?string
    {
        return $this->getData(self::CONDITIONS_SERIALIZED);
    }

    /**
     * @param string $conditionsSerialized
     * @return LoyaltyProgramInterface
     */
    public function setConditionsSerialized(string $conditionsSerialized): LoyaltyProgramInterface
    {
        return $this->setData(self::CONDITIONS_SERIALIZED, $conditionsSerialized);
    }

    /**
     * @return int|null
     * @throws \Exception
     */
    public function getPreviousProgram(): ?int
    {
        return $this->gettingIntegerData(self::PREVIOUS_PROGRAM);
    }

    /**
     * @param int|string|null $previousProgram
     * @return LoyaltyProgramInterface
     * @throws \Exception
     */
    public function setPreviousProgram(int|string|null $previousProgram): LoyaltyProgramInterface
    {
        return $this->settingIntegerData(self::PREVIOUS_PROGRAM, $previousProgram);
    }

    /**
     * @return int|null
     * @throws \Exception
     */
    public function getNextProgram(): ?int
    {
        return $this->gettingIntegerData(self::NEXT_PROGRAM);
    }

    /**
     * @param int|string|null $nextProgram
     * @return LoyaltyProgramInterface
     * @throws \Exception
     */
    public function setNextProgram(int|string|null $nextProgram): LoyaltyProgramInterface
    {
        return $this->settingIntegerData(self::NEXT_PROGRAM, $nextProgram);
    }

    /**
     * @return string|null
     */
    public function getCreatedAt(): ?string
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * @param string|null $createdAt
     * @return LoyaltyProgramInterface
     */
    public function setCreatedAt(?string $createdAt = null): LoyaltyProgramInterface
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * @return string|null
     */
    public function getUpdatedAt(): ?string
    {
        return $this->getData(self::UPDATED_AT);
    }

    /**
     * @param string|null $updatedAt
     * @return LoyaltyProgramInterface
     */
    public function setUpdatedAt(?string $updatedAt = null): LoyaltyProgramInterface
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }

    /**
     * @return int|null
     * @throws \Exception
     */
    public function getWebsiteId(): ?int
    {
        return $this->gettingIntegerData(self::WEBSITE_ID);
    }

    /**
     * @param int|string|null $websiteId
     * @return LoyaltyProgramInterface
     * @throws \Exception
     */
    public function setWebsiteId(int|string|null $websiteId): LoyaltyProgramInterface
    {
        return $this->settingIntegerData(self::WEBSITE_ID, $websiteId);
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getCustomerGroupIds(): array
    {
        return $this->dataValidator->validateMultiselectFieldIntData(
            $this->getData(self::CUSTOMER_GROUP_IDS),
            self::CUSTOMER_GROUP_IDS,
            self::ENTITY
        );
    }

    /**
     * @param null|int|string|array $customerGroupIds
     * @return LoyaltyProgramInterface
     * @throws \Exception
     */
    public function setCustomerGroupIds(null|int|string|array $customerGroupIds): LoyaltyProgramInterface
    {
        $customerGroupIds = $this->dataValidator->validateMultiselectFieldIntData(
            $customerGroupIds,
            self::CUSTOMER_GROUP_IDS,
            self::ENTITY
        );

        $customerGroupIds = $customerGroupIds ? implode(',', $customerGroupIds) : null;

        return $this->setData(self::CUSTOMER_GROUP_IDS, $customerGroupIds);
    }

    /**
     * @return int|null
     * @throws \Exception
     */
    public function getOrderSubtotal(): ?int
    {
        return $this->gettingIntegerData(self::ORDER_SUBTOTAL);
    }

    /**
     * @param int|string|null $orderSubtotal
     * @return LoyaltyProgramInterface
     * @throws \Exception
     */
    public function setOrderSubtotal(int|string|null $orderSubtotal): LoyaltyProgramInterface
    {
        return $this->settingIntegerData(self::ORDER_SUBTOTAL, $orderSubtotal);
    }

    /**
     * @param string $fieldName
     * @throws \Exception
     */
    private function throwExceptionGetData(string $fieldName): void
    {
        $this->throwDataException($fieldName, 'returns');
    }

    /**
     * @param string $fieldName
     * @throws \Exception
     */
    private function throwExceptionSetData(string $fieldName): void
    {
        $this->throwDataException($fieldName, 'received');
    }

    /**
     * @param string $fieldName
     * @param string $dataAction
     * @throws \Exception
     */
    private function throwDataException(string $fieldName, string $dataAction): void
    {
        throw new \Exception(
            'Loyalty Program Model \'' . $fieldName . '\' field ' . $dataAction . ' wrong value!'
        );
    }

    /**
     * @param string $fieldName
     * @return int|null
     * @throws \Exception
     */
    private function gettingIntegerData(string $fieldName): ?int
    {
        $int = $this->getData($fieldName);
        if ($int !== null) {
            if ($this->dataValidator->isDataInteger($int)) {
                $int = (int)$int;
            } else {
                $this->throwExceptionGetData($fieldName);
            }
        }

        return $int;
    }

    /**
     * @param string $fieldName
     * @param int|string|null $int
     * @return LoyaltyProgramInterface
     * @throws \Exception
     */
    private function settingIntegerData(string $fieldName, int|string|null $int): LoyaltyProgramInterface
    {
        if ($int !== null) {
            if (!$this->dataValidator->isDataInteger($int)) {
                $this->throwExceptionSetData($fieldName);
            }

            $int = (int)$int;
        }

        return $this->setData($fieldName, $int);
    }
}
