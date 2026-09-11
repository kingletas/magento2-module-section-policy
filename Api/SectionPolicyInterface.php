<?php
/**
 * @package   Kingletas_SectionPolicy
 * @copyright Copyright (c) the Kingletas modules authors
 * @license   OSL-3.0 https://opensource.org/licenses/OSL-3.0
 */

declare(strict_types=1);

namespace Kingletas\SectionPolicy\Api;

use Kingletas\SectionPolicy\Model\Policy\InvalidationMap;
use Kingletas\SectionPolicy\Model\Policy\RuleVerdict;

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
