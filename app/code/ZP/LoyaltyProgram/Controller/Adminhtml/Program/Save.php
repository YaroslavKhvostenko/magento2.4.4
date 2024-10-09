<?php
declare(strict_types=1);

namespace ZP\LoyaltyProgram\Controller\Adminhtml\Program;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;
use ZP\LoyaltyProgram\Api\LoyaltyProgramRepositoryInterface;
use ZP\LoyaltyProgram\Model\Controller\Adminhtml\Program\RequestHelper;
use ZP\LoyaltyProgram\Model\LoyaltyProgram;
use ZP\LoyaltyProgram\Model\LoyaltyProgramFactory;
use Magento\Framework\Exception\LocalizedException;
use ZP\LoyaltyProgram\Controller\Adminhtml\Program\AbstractControllers\HttpPostActionInterface\SaveAndDelete\Controller;
use Magento\Framework\Controller\ResultInterface;
use ZP\LoyaltyProgram\Setup\Patch\Data\AddBasicPrograms as BasicProgramsConfig;
use ZP\LoyaltyProgram\Model\Controller\Adminhtml\Program\CustomerProgramManagement;
use ZP\LoyaltyProgram\Model\Configs\Program\Form\Config as ProgramFormConfig;
use ZP\LoyaltyProgram\Model\Controller\Adminhtml\Program\Helper;
use ZP\LoyaltyProgram\Api\Model\Validators\Controller\Adminhtml\Program\ValidatorInterface;

class Save extends Controller
{
    public const BASIC_PROGRAM_ERR = ' EDIT ';

    private bool $isActiveStatusUpdatedToDisable = false;

    public function __construct(
        Context $context,
        LoggerInterface $logger,
        LoyaltyProgramRepositoryInterface $programRepository,
        CustomerProgramManagement $customerProgramManagement,
        Helper $helper,
        ValidatorInterface $dataValidator,
        RequestHelper $requestHelper,
        private LoyaltyProgramFactory $programFactory,
        private ProgramFormConfig $programFormConfig,
    ) {
        parent::__construct(
            $context,
            $logger,
            $programRepository,
            $customerProgramManagement,
            $helper,
            $dataValidator,
            $requestHelper
        );
    }

    /**
     * @return ResultInterface
     * @throws \Exception
     */
    public function execute(): ResultInterface
    {
        try {
            /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
            $resultRedirect = $this->resultRedirectFactory->create();
            $data = $this->getRequest()->getPostValue();
            $programId = $this->requestHelper->getProgramIdFromRequest($this->getRequest());
            $programId = $this->compareProgramIds($programId, $data);
            $this->validateProgramId($programId);
            $this->isBasicProgram();
            $this->checkPostData($data);
            if ($this->programId !== null) {
                $program = $this->programRepository->get($this->programId);
                $programBeforeSave = $this->programRepository->get($this->programId);
            } else {
                $program = $this->programFactory->create();
                $programBeforeSave = null;
            }

            /** @var LoyaltyProgram $program */
            /** @var LoyaltyProgram|null $programBeforeSave */
            $this->prepareDataToSave($data);
            $program->setData($data);
            if ($programBeforeSave && $this->isSameProgramsData($program->getData(), $programBeforeSave->getData())) {
                $this->messageManager->addNoticeMessage(__('You did\'t change any data!'));
            } else {
                if ($this->isActiveStatusUpdatedToDisable()) {
                    $this->beforeSave();
                }

                $this->programRepository->save($program);

                if ($this->isActiveStatusUpdatedToDisable()) {
                    $this->afterSave();
                }

                $this->programName = $program->getProgramName();

                $this->addMessages();
            }

            if ($this->programId === null) {
                $this->programId = (int)$program->getId();
            }

            if ($this->getRequest()->getParam('back')) {
                return $resultRedirect->setPath('*/*/edit', [LoyaltyProgram::PROGRAM_ID => $this->programId]);
            }
        } catch (NoSuchEntityException $exception) {
            $this->messageManager->addNoticeMessage(__('This Program no longer exists!'));
            $this->logger->notice(__($exception->getMessage()));
        } catch (LocalizedException $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
            $this->logger->notice(__($exception->getMessage()));
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage(__('Sorry, something went wrong while trying to save program!'));
            $this->logger->error(__($exception->getMessage()));
        }

        return $resultRedirect->setPath('*/*/');
    }

    /**
     * @param string|null $requestProgramId
     * @param array $data
     * @return string|null
     * @throws \Exception
     */
    private function compareProgramIds(?string $requestProgramId, array &$data): ?string
    {
        $postProgramId = $data[LoyaltyProgram::PROGRAM_ID] ?? null;

        if ($requestProgramId !== $postProgramId) {
            throw new \Exception('Different program ids from request and post data!');
        } else {
            $programId = $requestProgramId;
        }

        return $programId;
    }

    protected function nullProgramIdReaction(): void
    {
        // do nothing!!!
    }

    /**
     * @param array $data
     * @throws \Exception
     */
    private function checkPostData(array &$data): void
    {
        $exceptionArray = [];
        foreach ($data as $key => $value) {
            if ($value === '') {
                $exceptionArray[] = $key;
            }
        }

        if (count($data) === count($exceptionArray)) {
            throw new \Exception(
                'Its impossible Loyalty Program incoming data to be empty, check where data has been lost!'
            );
        }
        unset($exceptionArray);

        $this->checkNotNullableFieldsData($data, $this->programFormConfig->getNotNullableFields());
        $this->checkStringFieldsData($data, $this->programFormConfig->getFormStringFields());
        $this->checkIntegerFieldsData($data, $this->programFormConfig->getFormIntegerFields($this->programId));

        foreach ($data as $field => $value) {
            if ($value === '') {
                $data[$field] = false;
            }
        }
    }

    /**
     * @param array $data
     * @param array $notNullableFields
     * @throws \Exception
     */
    private function checkNotNullableFieldsData(array &$data, array $notNullableFields): void
    {
        $emptyDataFields = [];
        foreach ($notNullableFields as $field) {
            if (array_key_exists($field, $data)) {
                if (!$this->programFormConfig->isNotNullableSelectTypeField($field)) {
                    if ($data[$field] !== '') {
                        continue;
                    }
                } else {
                    $selectType = $this->programFormConfig->getFieldSelectType($field);
                    if ($selectType === ProgramFormConfig::MULTISELECT && is_array($data[$field])) {
                        continue;
                    } elseif ($selectType === ProgramFormConfig::SELECT && $data[$field] !== '') {
                        continue;
                    }
                }
            }

            $emptyDataFields[] = $field;
        }

        if ($emptyDataFields) {
            throw new \Exception(
                'Data of this Loyalty Program form fields have been lost : ' .
                implode(',', $emptyDataFields) . ' !'
            );
        }
    }

    /**
     * @param array $data
     * @param array $stringFields
     * @throws \Exception
     */
    public function checkStringFieldsData(array &$data, array $stringFields): void
    {
        $wrongDataFields = [];
        foreach ($stringFields as $field) {
            if (array_key_exists($field, $data)) {
                if ($data[$field] === '' || $this->isDataString($data[$field])) {
                    continue;
                } else {
                    $wrongDataFields[] = $field;
                }
            } else {
                $this->logger->notice("'$field'" . ' field has been lost with data!');
            }
        }

        if ($wrongDataFields) {
            throw new \Exception(
                'Data of this Loyalty Program form fields : ' . implode(',', $wrongDataFields) .
                ' must be string! Not numeric!'
            );
        }
    }

    private function isDataString(string $data): bool
    {
        preg_match('/[A-Za-z]/', $data, $matches);

        return (bool)$matches;
    }

    /**
     * @param array $data
     * @param array $integerFields
     * @throws \Exception
     */
    public function checkIntegerFieldsData(array &$data, array $integerFields): void
    {
        $wrongDataFields = [];

        foreach ($integerFields as $field) {
            if (!$this->isFieldValid($field, $data, $wrongDataFields)) {
                $wrongDataFields[] = $field;
            }
        }

        if ($wrongDataFields) {
            throw new \Exception(
                'Data of this Loyalty Program form fields : ' . implode(',', $wrongDataFields) .
                ' must be an integer in string like \'1\'.' .
                ' But in case of multiselect form field it must be an array values of integers in string, ' .
                'or if it is allowed to be empty it must be empty string like \'\'!'

            );
        }
    }

    /**
     * @param string $field
     * @param array $data
     * @param array $wrongDataFields
     * @return bool
     * @throws \Exception
     */
    private function isFieldValid(string $field, array &$data, array &$wrongDataFields): bool
    {
        if (!array_key_exists($field, $data)) {
            $this->logger->notice("'$field' has been lost with data!");
            return false;
        }

        if ($this->programFormConfig->isSelectingTypeField($field)) {
            return $this->validateSelectField($field, $data[$field], $wrongDataFields);
        }

        return $data[$field] === '' || $this->dataValidator->isDataInteger($data[$field]);
    }

    /**
     * @param string $field
     * @param string|array $value
     * @param array $wrongDataFields
     * @return bool
     * @throws \Exception
     */
    private function validateSelectField(string $field, string|array $value, array &$wrongDataFields): bool
    {
        if ($value === '') {
            return true;
        }

        $selectType = $this->programFormConfig->getFieldSelectType($field);
        if ($selectType === ProgramFormConfig::SELECT) {
            return $this->dataValidator->isDataInteger($value);
        }

        if (is_array($value)) {
            return $this->validateMultiSelectField($field, $value, $wrongDataFields);
        }

        return false;
    }

    private function validateMultiSelectField(string $field, array $values, array &$wrongDataFields): bool
    {
        $wrongMultiSelectData = "Multiselect Field '$field' : ";
        $initialString = $wrongMultiSelectData;

        foreach ($values as $key => $value) {
            if (!$this->dataValidator->isDataInteger($value)) {
                $wrongMultiSelectData .= "$key(key) => '$value'(value), ";
            }
        }

        if ($wrongMultiSelectData !== $initialString) {
            $wrongDataFields[] = rtrim($wrongMultiSelectData, ', ') . '.';
        }

        return true;
    }

    private function prepareDataToSave(array &$data): void
    {

        $this->processProgramData(
            $data,
            LoyaltyProgram::IS_PROGRAM_MINIMUM,
            BasicProgramsConfig::PROGRAM_MIN,
            LoyaltyProgram::PREVIOUS_PROGRAM
        );
        $this->processProgramData(
            $data,
            LoyaltyProgram::IS_PROGRAM_MAXIMUM,
            BasicProgramsConfig::PROGRAM_MAX,
            LoyaltyProgram::NEXT_PROGRAM
        );

        foreach ($data as $field => $value) {
            if ($this->programFormConfig->isFieldMultiselectType($field)) {
                $this->processField($data, $field, true);
            } else {
                $this->processField($data, $field);
            }
        }
    }

    private function processProgramData(
        array &$data,
        string $programKey,
        int $programConfig,
        string $programNextOrPrevious
    ): void {
        if (isset($data[$programKey])) {
            if ($data[$programKey] === '1') {
                $data[$programNextOrPrevious] = (string)$programConfig;
            }

            unset($data[$programKey]);
        }
    }

    private function processField(array &$data, string $field, bool $implode = false): void
    {
        if ($data[$field] === false) {
            $data[$field] = null;
        } elseif ($implode) {
            $data[$field] = implode(',', $data[$field]);
        }
    }

    private function isSameProgramsData(array $updatedData, array $programData): bool
    {
        $result = [];
        foreach ($updatedData as $field => $value) {
            if (array_key_exists($field, $programData)) {
                $result[$field] = $value === $programData[$field];
            } elseif ($field === ProgramFormConfig::FORM_KEY) {
                continue;
            } else {
                throw new \Exception(
                    'Unknown fieldName ' . '\'' . $field . '\' ' .
                    'while trying to compare updating and existing program data!'
                );
            }
        }

        if (in_array(false, $result, true)) {
            $this->setIsActiveStatusUpdatedToDisable(
                (bool)$programData[LoyaltyProgram::IS_ACTIVE],
                (bool)$updatedData[LoyaltyProgram::IS_ACTIVE]
            );
        }

        return !in_array(false, $result, true);
    }

    private function setIsActiveStatusUpdatedToDisable(bool $isCurrentStatusTrue, bool $updatedStatus): void
    {
        $this->isActiveStatusUpdatedToDisable = $isCurrentStatusTrue && $isCurrentStatusTrue !== $updatedStatus;
    }

    private function isActiveStatusUpdatedToDisable(): bool
    {
        return $this->isActiveStatusUpdatedToDisable;
    }

    private function beforeSave(): void
    {
        $this->beforeAction();
    }

    /**
     * @throws \Exception
     */
    private function afterSave(): void
    {
        $this->afterAction();
    }

    protected function addMessages(): void
    {
        $action = $this->programId !== null ? ' updated ' : ' crated new ';
        $this->messageManager->addSuccessMessage(
            __('You have successfully' . $action . '\'' . $this->programName . '\' Program!')
        );
    }
}
