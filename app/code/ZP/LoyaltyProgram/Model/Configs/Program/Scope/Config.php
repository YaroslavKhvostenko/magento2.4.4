<?php
declare(strict_types=1);

namespace ZP\LoyaltyProgram\Model\Configs\Program\Scope;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{
    private const XML_PATH_ENABLED = 'loyalty_program/general/is_enabled';
    private const XML_PATH_APPLY_SUBTOTAL_CHANGES_AFTER_INVOICE = 'loyalty_program/general/apply_subtotal_changes_after_invoice';

    public function __construct(
        private ScopeConfigInterface $scopeConfig,
    ) {}

    /**
     * @param string|int|null $websiteId
     * @return bool
     */
    public function isEnabled(null|string|int $websiteId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLED,
            ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );
    }

    /**
     * @param string|int|null $websiteId
     * @return bool
     */
    public function isApplySubtotalChangesAfterInvoice(null|string|int $websiteId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_APPLY_SUBTOTAL_CHANGES_AFTER_INVOICE,
            ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );
    }
}
