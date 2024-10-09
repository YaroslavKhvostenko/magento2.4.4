<?php
declare(strict_types=1);

namespace ZP\LoyaltyProgram\Model\ResourceModel;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use ZP\LoyaltyProgram\Api\Data\LoyaltyProgramInterface;
use ZP\LoyaltyProgram\Api\Model\Validators\Controller\Adminhtml\Program\ValidatorInterface;
use Magento\Framework\Model\ResourceModel\Db\Context;

class LoyaltyProgram extends AbstractDb
{
    public function __construct(
        Context $context,
        private ValidatorInterface $dataValidator,
        $connectionName = null
    ) {
        parent::__construct($context, $connectionName);
    }

    protected function _construct()
    {
        $this->_init(LoyaltyProgramInterface::MAIN_TABLE, LoyaltyProgramInterface::PROGRAM_ID);
    }

    /**
     * @param AbstractModel $object
     * @return LoyaltyProgram
     * @throws \Exception
     */
    protected function _beforeDelete(AbstractModel $object)
    {
        $programId = $object->getData(LoyaltyProgramInterface::PROGRAM_ID);
        if ($programId !== null) {
            $programId = $this->dataValidator->validateProgramId($programId);

            if ($this->dataValidator->isBasicProgram($programId)) {
                throw new \Exception(
                    'You are trying to delete Basic Loyalty Programs! It is forbidden!'
                );
            }
        }

        return parent::_beforeDelete($object);
    }
}
