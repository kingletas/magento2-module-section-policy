<?php
/**
 * @package   Kingletas_SectionPolicy
 * @copyright Copyright (c) the Kingletas modules authors
 * @license   OSL-3.0 https://opensource.org/licenses/OSL-3.0
 */

declare(strict_types=1);

namespace Kingletas\SectionPolicy\Test\Unit\Model\Policy;

use Kingletas\SectionPolicy\Model\Policy\RuleOutcome;
use PHPUnit\Framework\TestCase;

class RuleOutcomeTest extends TestCase
{
    /**
     * The values are printed in the report, so they are a contract rather than an implementation detail.
     */
    public function testEachOutcomeKeepsTheNameTheReportPrints(): void
    {
        $this->assertSame('applies', RuleOutcome::Applies->value);
        $this->assertSame('not_applied', RuleOutcome::NotApplied->value);
        $this->assertSame('misconfigured', RuleOutcome::Misconfigured->value);
    }

    /**
     * A fourth outcome would need a decision about whether it fails a run, so adding one is deliberate.
     */
    public function testThereAreExactlyThreeOutcomes(): void
    {
        $this->assertCount(3, RuleOutcome::cases());
    }

    public function testAnOutcomeCanBeReadBackFromItsPrintedName(): void
    {
        $this->assertSame(RuleOutcome::Misconfigured, RuleOutcome::from('misconfigured'));
        $this->assertNull(RuleOutcome::tryFrom('nonsense'));
    }
}
