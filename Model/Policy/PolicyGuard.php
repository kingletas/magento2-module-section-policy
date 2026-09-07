<?php
/**
 * @package   Commerce_SectionPolicy
 * @copyright Copyright (c) the Commerce modules authors
 * @license   OSL-3.0 https://opensource.org/licenses/OSL-3.0
 */

declare(strict_types=1);

namespace Commerce\SectionPolicy\Model\Policy;

use Commerce\SectionPolicy\Api\SectionInventoryInterface;
use Commerce\SectionPolicy\Api\SectionRuleInterface;
use Commerce\SectionPolicy\Model\Config;

/**
 * Whether a rule may narrow its action, checked before anything is narrowed.
 */
class PolicyGuard
{
    /**
     * @param string[] $protectedSections Sections that carry who the shopper is.
     */
    public function __construct(
        private readonly Config $config,
        private readonly SectionInventoryInterface $inventory,
        private readonly array $protectedSections = []
    ) {
    }

    /**
     * Grade one rule against the map it would change.
     */
    public function grade(SectionRuleInterface $rule, InvalidationMap $map, ?int $storeId = null): RuleVerdict
    {
        $action = $rule->action();

        if (!$map->has($action)) {
            return $this->refuse($action, sprintf('no installed module declares the action "%s"', $action));
        }

        $excluded = $rule->excluded($storeId);

        foreach ($excluded as $section) {
            if (!$this->inventory->has($section)) {
                return $this->refuse($action, sprintf('"%s" is not a registered section', $section));
            }

            if (in_array($section, $this->protectedSections, true)) {
                return $this->refuse($action, sprintf('"%s" says who the shopper is and is never excluded', $section));
            }
        }

        if ($excluded === []) {
            return new RuleVerdict($action, RuleOutcome::NotApplied, 'excludes nothing');
        }

        if (!$this->config->isEnabled($storeId)) {
            return new RuleVerdict($action, RuleOutcome::NotApplied, 'the policy is switched off');
        }

        return new RuleVerdict($action, RuleOutcome::Applies, $rule->describe($storeId));
    }

    /**
     * A mistake nobody chose, reported whether or not the policy is switched on.
     */
    private function refuse(string $action, string $reason): RuleVerdict
    {
        return new RuleVerdict($action, RuleOutcome::Misconfigured, $reason);
    }
}
