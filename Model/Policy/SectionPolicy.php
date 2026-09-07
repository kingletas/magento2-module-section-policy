<?php
/**
 * @package   Commerce_SectionPolicy
 * @copyright Copyright (c) the Commerce modules authors
 * @license   OSL-3.0 https://opensource.org/licenses/OSL-3.0
 */

declare(strict_types=1);

namespace Commerce\SectionPolicy\Model\Policy;

use Commerce\SectionPolicy\Api\SectionPolicyInterface;
use Commerce\SectionPolicy\Api\SectionRuleInterface;

/**
 * Applies every rule the guard allows, and reports on the ones it does not.
 */
class SectionPolicy implements SectionPolicyInterface
{
    /**
     * @param SectionRuleInterface[] $rules
     */
    public function __construct(
        private readonly PolicyGuard $guard,
        private readonly array $rules = []
    ) {
    }

    public function apply(InvalidationMap $map, ?int $storeId = null): InvalidationMap
    {
        foreach ($this->rules as $rule) {
            if ($this->guard->grade($rule, $map, $storeId)->applies()) {
                $map = $rule->apply($map, $storeId);
            }
        }

        return $map;
    }

    /**
     * @return RuleVerdict[]
     */
    public function verdicts(InvalidationMap $map, ?int $storeId = null): array
    {
        return array_map(
            fn (SectionRuleInterface $rule): RuleVerdict => $this->guard->grade($rule, $map, $storeId),
            array_values($this->rules)
        );
    }
}
