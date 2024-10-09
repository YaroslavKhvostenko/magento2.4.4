<?php
declare(strict_types=1);

namespace ZP\LoyaltyProgram\Model\Configs\Program\Form;

use ZP\LoyaltyProgram\Model\LoyaltyProgram;

class Config
{
    public const SELECT = 'select';
    public const MULTISELECT = 'multiselect';
    public const FORM_KEY = 'form_key';
    public const SELECT_TYPE_FIELDS = [
        LoyaltyProgram::WEBSITE_ID,
        LoyaltyProgram::PREVIOUS_PROGRAM,
        LoyaltyProgram::NEXT_PROGRAM
    ];
    public const MULTISELECT_TYPE_FIELDS = [
        LoyaltyProgram::CUSTOMER_GROUP_IDS
    ];
    public const FORM_INT_FIELDS = [
        LoyaltyProgram::PROGRAM_ID,
        LoyaltyProgram::IS_ACTIVE,
        LoyaltyProgram::IS_PROGRAM_MINIMUM,
        LoyaltyProgram::PREVIOUS_PROGRAM,
        LoyaltyProgram::IS_PROGRAM_MAXIMUM,
        LoyaltyProgram::NEXT_PROGRAM,
        LoyaltyProgram::WEBSITE_ID,
        LoyaltyProgram::CUSTOMER_GROUP_IDS,
        LoyaltyProgram::ORDER_SUBTOTAL
    ];
    public const FORM_STRING_FIELDS = [
        LoyaltyProgram::PROGRAM_NAME,
        LoyaltyProgram::DESCRIPTION
    ];
    public const NOT_NULLABLE_FIELDS = [
        LoyaltyProgram::PROGRAM_NAME,
        LoyaltyProgram::IS_ACTIVE,
        LoyaltyProgram::IS_PROGRAM_MINIMUM,
        LoyaltyProgram::IS_PROGRAM_MAXIMUM,
        LoyaltyProgram::WEBSITE_ID,
        LoyaltyProgram::CUSTOMER_GROUP_IDS,
        LoyaltyProgram::ORDER_SUBTOTAL
    ];

    /**
     * @param string $fieldName
     * @return string
     * @throws \Exception
     */
    public function getFieldSelectType(string $fieldName): string
    {
        if (in_array($fieldName, self::SELECT_TYPE_FIELDS)) {
            return self::SELECT;
        } elseif (in_array($fieldName, self::MULTISELECT_TYPE_FIELDS)) {
            return self::MULTISELECT;
        }

        throw new \Exception(
            'Unknown field name : \'' . $fieldName . '\', while trying to return field form select type!
            ');
    }

    public function getFormIntegerFields(?int $programId): array
    {
        return $programId === null ?
            array_diff(self::FORM_INT_FIELDS, [LoyaltyProgram::PROGRAM_ID]) : self::FORM_INT_FIELDS;
    }

    public function getNotNullableFields(): array
    {
        return self::NOT_NULLABLE_FIELDS;
    }

    public function getFormStringFields(): array
    {
        return self::FORM_STRING_FIELDS;
    }

    public function isNotNullableSelectTypeField(string $fieldName): bool
    {
        return in_array($fieldName, self::NOT_NULLABLE_FIELDS) &&
            (in_array($fieldName, self::SELECT_TYPE_FIELDS) || in_array($fieldName, self::MULTISELECT_TYPE_FIELDS));
    }

    public function isFieldSelectType(string $fieldName): bool
    {
        return in_array($fieldName, self::SELECT_TYPE_FIELDS);
    }

    public function isSelectingTypeField(string $fieldName): bool
    {
         return $this->isFieldSelectType($fieldName) || $this->isFieldMultiselectType($fieldName);
    }

    public function isFieldMultiselectType(string $fieldName): bool
    {
        return in_array($fieldName, self::MULTISELECT_TYPE_FIELDS);
    }
}
