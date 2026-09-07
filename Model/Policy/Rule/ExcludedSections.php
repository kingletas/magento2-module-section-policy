<?php
/**
 * @package   Commerce_SectionPolicy
 * @copyright Copyright (c) the Commerce modules authors
 * @license   OSL-3.0 https://opensource.org/licenses/OSL-3.0
 */

declare(strict_types=1);

namespace Commerce\SectionPolicy\Model\Policy\Rule;

use Commerce\SectionPolicy\Api\SectionInventoryInterface;
use Commerce\SectionPolicy\Api\SectionRuleInterface;
use Commerce\SectionPolicy\Model\Config;
use Commerce\SectionPolicy\Model\Policy\InvalidationMap;

/**
 * Takes named sections out of one action's list, and never adds any.
 */
class ExcludedSections implements SectionRuleInterface
{
    /**
     * @param string[] $sections Exclusions fixed at wiring time, merged with whatever the config holds.
     */
    public function __construct(
        private readonly Config $config,
        private readonly SectionInventoryInterface $inventory,
        private readonly string $action,
        private readonly string $configPath = '',
        private readonly array $sections = []
    ) {
    }

    /**
     * Read once per store, because this runs on every page render and is asked for three times each.
     *
     * @var array<string, string[]>
     */
    private array $resolved = [];

    public function action(): string
    {
        return $this->action;
    }

    /**
     * @return string[]
     */
    public function excluded(?int $storeId = null): array
    {
        $key = (string) $storeId;

        if (!isset($this->resolved[$key])) {
            $configured = $this->configPath === ''
                ? []
                : $this->config->getExcluded($this->configPath, $storeId);

            $this->resolved[$key] = array_values(array_unique(array_merge($this->sections, $configured)));
        }

        return $this->resolved[$key];
    }

    /**
     * Expand the wildcard to the sections that exist, then subtract.
     */
    public function apply(InvalidationMap $map, ?int $storeId = null): InvalidationMap
    {
        $excluded = $this->excluded($storeId);

        if ($excluded === [] || !$map->has($this->action)) {
            return $map;
        }

        $current = $map->invalidatesEverything($this->action)
            ? $this->inventory->names()
            : $map->sectionsFor($this->action);

        return $map->withSectionsFor($this->action, array_values(array_diff($current, $excluded)));
    }

    public function describe(?int $storeId = null): string
    {
        $excluded = $this->excluded($storeId);

        if ($excluded === []) {
            return 'excludes nothing';
        }

        return 'keeps everything except ' . implode(', ', $excluded);
    }
}
