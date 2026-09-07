<?php
/**
 * @package   Commerce_SectionPolicy
 * @copyright Copyright (c) the Commerce modules authors
 * @license   OSL-3.0 https://opensource.org/licenses/OSL-3.0
 */

declare(strict_types=1);

namespace Commerce\SectionPolicy\Api;

use Commerce\SectionPolicy\Model\Policy\InvalidationMap;
use Commerce\SectionPolicy\Model\Policy\RuleVerdict;

/**
 * Decides what each action is allowed to invalidate before the map reaches the browser.
 */
interface SectionPolicyInterface
{
    /**
     * Return the map with every rule applied, or unchanged when the policy is not in force.
     */
    public function apply(InvalidationMap $map, ?int $storeId = null): InvalidationMap;

    /**
     * What the guard decided about every declared rule, applied or not.
     *
     * @return RuleVerdict[]
     */
    public function verdicts(InvalidationMap $map, ?int $storeId = null): array;
}
