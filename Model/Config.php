<?php
/**
 * @package   Commerce_SectionPolicy
 * @copyright Copyright (c) the Commerce modules authors
 * @license   OSL-3.0 https://opensource.org/licenses/OSL-3.0
 */

declare(strict_types=1);

namespace Commerce\SectionPolicy\Model;

use Commerce\Foundation\Model\Config\ModuleConfig;

/**
 * Typed access to this module's settings.
 */
class Config extends ModuleConfig
{
    /**
     * Whether the declared rules are allowed to narrow what an action invalidates.
     */
    public function isEnabled(?int $storeId = null): bool
    {
        return $this->isSetFlag('policy/enabled', $storeId);
    }

    /**
     * Sections a rule may name, read from wherever that rule was wired to look.
     *
     * @return string[]
     */
    public function getExcluded(string $configPath, ?int $storeId = null): array
    {
        return $this->getList($configPath, $storeId);
    }
}
