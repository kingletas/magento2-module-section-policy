<?php
/**
 * @package   Kingletas_SectionPolicy
 * @copyright Copyright (c) the Kingletas modules authors
 * @license   OSL-3.0 https://opensource.org/licenses/OSL-3.0
 */

declare(strict_types=1);

namespace Kingletas\SectionPolicy\Model\Policy;

use Kingletas\SectionPolicy\Api\SectionPolicyInterface;
use Kingletas\SectionPolicy\Api\SectionRuleInterface;

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
