<?php
/**
 * @package   Commerce_SectionPolicy
 * @copyright Copyright (c) the Commerce modules authors
 * @license   OSL-3.0 https://opensource.org/licenses/OSL-3.0
 */

declare(strict_types=1);

namespace Commerce\SectionPolicy\Api;

use Commerce\SectionPolicy\Model\Policy\InvalidationMap;

/**
 * What one action is allowed to invalidate.
 */
interface SectionRuleInterface
{
    /**
     * The action this rule governs, spelled as `sections.xml` spells it.
     */
    public function action(): string;

    /**
     * Sections this rule takes out of that action's list.
     *
     * @return string[]
     */
    public function excluded(?int $storeId = null): array;

    /**
     * Return the map with this rule applied to its own action.
     */
    public function apply(InvalidationMap $map, ?int $storeId = null): InvalidationMap;

    /**
     * One line an operator can read in the report.
     */
    public function describe(?int $storeId = null): string;
}
