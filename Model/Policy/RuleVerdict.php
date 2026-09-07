<?php
/**
 * @package   Commerce_SectionPolicy
 * @copyright Copyright (c) the Commerce modules authors
 * @license   OSL-3.0 https://opensource.org/licenses/OSL-3.0
 */

declare(strict_types=1);

namespace Commerce\SectionPolicy\Model\Policy;

/**
 * Whether one rule may narrow its action, and why.
 */
class RuleVerdict
{
    public function __construct(
        private readonly string $action,
        private readonly RuleOutcome $outcome,
        private readonly string $reason
    ) {
    }

    public function action(): string
    {
        return $this->action;
    }

    public function outcome(): RuleOutcome
    {
        return $this->outcome;
    }

    public function applies(): bool
    {
        return $this->outcome === RuleOutcome::Applies;
    }

    public function isMisconfigured(): bool
    {
        return $this->outcome === RuleOutcome::Misconfigured;
    }

    /**
     * A sentence an operator can act on, whichever way the decision went.
     */
    public function reason(): string
    {
        return $this->reason;
    }
}
