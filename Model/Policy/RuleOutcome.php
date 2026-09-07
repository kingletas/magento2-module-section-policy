<?php
/**
 * @package   Commerce_SectionPolicy
 * @copyright Copyright (c) the Commerce modules authors
 * @license   OSL-3.0 https://opensource.org/licenses/OSL-3.0
 */

declare(strict_types=1);

namespace Commerce\SectionPolicy\Model\Policy;

/**
 * What the guard decided about one rule, and how much it matters.
 */
enum RuleOutcome: string
{
    /**
     * The rule may narrow its action.
     */
    case Applies = 'applies';

    /**
     * A deliberate state - switched off, or a rule that excludes nothing yet.
     */
    case NotApplied = 'not_applied';

    /**
     * A wiring mistake nobody chose, so it is reported whether or not anyone asked for a gate.
     */
    case Misconfigured = 'misconfigured';
}
