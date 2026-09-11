<?php
/**
 * @package   Kingletas_SectionPolicy
 * @copyright Copyright (c) the Kingletas modules authors
 * @license   OSL-3.0 https://opensource.org/licenses/OSL-3.0
 */

declare(strict_types=1);

namespace Kingletas\SectionPolicy\Model;

use Kingletas\Foundation\Model\Config\ModuleConfig;

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
