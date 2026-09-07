<?php
/**
 * @package   Commerce_SectionPolicy
 * @copyright Copyright (c) the Commerce modules authors
 * @license   OSL-3.0 https://opensource.org/licenses/OSL-3.0
 */

declare(strict_types=1);

namespace Commerce\SectionPolicy\Model\Inventory;

use Commerce\SectionPolicy\Api\SectionInventoryInterface;
use Magento\Customer\Block\SectionNamesProvider;
use Magento\Customer\Block\SectionNamesProviderFactory;

/**
 * The registered sections, read from the same place the browser reads them.
 */
class SectionInventory implements SectionInventoryInterface
{
    /**
     * The section list is wired in the storefront's `di.xml`, so one built anywhere else is empty.
     * Building it on first use keeps that decision with the caller that established the area.
     */
    private ?SectionNamesProvider $sectionNames = null;

    public function __construct(private readonly SectionNamesProviderFactory $sectionNamesFactory)
    {
    }

    /**
     * @return string[]
     */
    public function names(): array
    {
        return array_values(array_map('strval', $this->provider()->getSectionNames()));
    }

    public function has(string $section): bool
    {
        return in_array($section, $this->names(), true);
    }

    private function provider(): SectionNamesProvider
    {
        return $this->sectionNames ??= $this->sectionNamesFactory->create();
    }
}
