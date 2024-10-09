<?php
declare(strict_types=1);

namespace ZP\LoyaltyProgram\Model\Source\Adminhtml\Program\Form\Fields\Field;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\OptionSourceInterface;
use ZP\LoyaltyProgram\Api\Data\LoyaltyProgramInterface;
use ZP\LoyaltyProgram\Model\LoyaltyProgram;
use ZP\LoyaltyProgram\Model\ResourceModel\LoyaltyProgram\Collection;
use ZP\LoyaltyProgram\Model\ResourceModel\LoyaltyProgram\CollectionFactory;
use ZP\LoyaltyProgram\Setup\Patch\Data\AddBasicPrograms as BasicProgramsConfig;
use ZP\LoyaltyProgram\Api\Model\Validators\Controller\Adminhtml\Program\ValidatorInterface;

abstract class ReferenceProgramOptions implements OptionSourceInterface
{
    public const PLEASE_OPTION = 'please_option';
    public const REMOVE_OPTION = 'remove_option';
    public const EMPTY_OPTION = 'empty_option';

    protected ?int $currentProgramId = null;

    public function __construct(
        private CollectionFactory $collectionFactory,
        private RequestInterface $request,
        protected ValidatorInterface $dataValidator
    ) {}

    /**
     * @return array
     * @throws \Exception
     */
    public function toOptionArray()
    {
        return $this->getData();
    }

    private function getProgramCollection(array $programIds = []): array
    {
        /** @var Collection $collection */
        $collection = $this->collectionFactory->create()->excludeBasicPrograms();
        if ($programIds) {
            $collection->addFieldToFilter(LoyaltyProgram::PROGRAM_ID, ['nin' => $programIds]);
        }

        return $collection->getItems();
    }

    /**
     * @return array
     * @throws \Exception
     */
    protected function getData(): array
    {
        $data = [];
        $programs = $this->getProgramCollection();
        if (!$programs) {
            $data[] = $this->getDefaultOption(self::EMPTY_OPTION);

            return $data;
        }

        if ($this->getProgramId() === null || $this->dataValidator->isBasicProgram($this->currentProgramId)) {
            $data[] = $this->getDefaultOption(self::PLEASE_OPTION);

            return $this->getOptionsData($data, $programs);
        }

        /** @var LoyaltyProgram $currentProgram */
        $currentProgram = $programs[$this->currentProgramId];
        $referenceProgramId = $this->getReferenceProgramId($currentProgram);
        if ($referenceProgramId === null || $this->dataValidator->isBasicProgram($referenceProgramId)) {
            $data[] = $this->getDefaultOption(self::PLEASE_OPTION);

            return $this->getOptionsData($data, $this->getProgramCollection([$this->currentProgramId]));
        }

        /** @var LoyaltyProgram $referenceProgram */
        $referenceProgram = $programs[$referenceProgramId];
        $data[] = ['label' => __($referenceProgram->getProgramName()), 'value' => $referenceProgram->getProgramId()];
        $data[] = $this->getDefaultOption(self::REMOVE_OPTION);

        return $this->getOptionsData($data, $this->getProgramCollection([$this->currentProgramId, $referenceProgramId]));
    }

    protected function getProgramId(): ?int
    {
        $programId = $this->request->getParam(LoyaltyProgramInterface::PROGRAM_ID);
        if ($programId !== null) {
            $this->currentProgramId = (int)$programId;
        }

        return $this->currentProgramId;
    }

    /**
     * @param string $type
     * @return array
     * @throws \Exception
     */
    protected function getDefaultOption(string $type): array
    {
        $label = match ($type) {
            self::REMOVE_OPTION => '-- Remove Program --',
            self::PLEASE_OPTION => '-- Please Select --',
            self::EMPTY_OPTION => '--Nothing To Select',
            default => throw new \Exception('Unknown option type : ' . '\'' . $type . '\'!')
        };

        return ['label' => __($label), 'value' => ''];
    }

    /**
     * @param array $dataToReturn
     * @param array $programsData
     * @return array
     * @throws \Exception
     */
    protected function getOptionsData(array $dataToReturn, array $programsData): array
    {
        /** @var LoyaltyProgram $program */
        foreach ($programsData as $program) {
            $dataToReturn[] = ['label' => __($program->getProgramName()), 'value' => $program->getProgramId()];
        }

        return $dataToReturn;
    }

    /**
     * @param LoyaltyProgram $program
     * @return int|null
     */
    abstract protected function getReferenceProgramId(LoyaltyProgram $program): ?int;
}
