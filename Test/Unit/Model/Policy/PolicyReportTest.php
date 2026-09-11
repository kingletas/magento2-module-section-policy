<?php
/**
 * @package   Kingletas_SectionPolicy
 * @copyright Copyright (c) the Kingletas modules authors
 * @license   OSL-3.0 https://opensource.org/licenses/OSL-3.0
 */

declare(strict_types=1);

namespace Kingletas\SectionPolicy\Test\Unit\Model\Policy;

use Kingletas\SectionPolicy\Model\Policy\InvalidationMap;
use Kingletas\SectionPolicy\Model\Policy\PolicyReport;
use Kingletas\SectionPolicy\Model\Policy\RuleOutcome;
use Kingletas\SectionPolicy\Model\Policy\RuleVerdict;
use PHPUnit\Framework\TestCase;

class PolicyReportTest extends TestCase
{
    public function testItKeepsTheDeclaredAndTheAppliedMapApart(): void
    {
        $declared = new InvalidationMap(['a/b/c' => ['*']]);
        $applied = new InvalidationMap(['a/b/c' => ['cart']]);

        $report = new PolicyReport($declared, $applied, [], ['cart']);

        $this->assertSame(['*'], $report->declared()->sectionsFor('a/b/c'));
        $this->assertSame(['cart'], $report->applied()->sectionsFor('a/b/c'));
    }

    public function testOnlyTheMisconfiguredVerdictsCountAsBroken(): void
    {
        $report = $this->report([
            new RuleVerdict('a', RuleOutcome::Applies, 'fine'),
            new RuleVerdict('b', RuleOutcome::NotApplied, 'excludes nothing'),
            new RuleVerdict('c', RuleOutcome::Misconfigured, 'not a registered section'),
        ]);

        $broken = $report->broken();

        $this->assertCount(1, $broken);
        $this->assertSame('c', $broken[0]->action());
    }

    public function testNoVerdictsMeansNothingIsBroken(): void
    {
        $this->assertSame([], $this->report([])->broken());
    }

    /**
     * An empty inventory is the storefront wiring never having loaded, and it must be visible.
     */
    public function testAnEmptyInventoryIsReportedAsEmpty(): void
    {
        $map = new InvalidationMap([]);

        $this->assertTrue((new PolicyReport($map, $map, [], []))->inventoryIsEmpty());
        $this->assertFalse((new PolicyReport($map, $map, [], ['cart']))->inventoryIsEmpty());
    }

    /**
     * @param RuleVerdict[] $verdicts
     */
    private function report(array $verdicts): PolicyReport
    {
        $map = new InvalidationMap(['a/b/c' => ['*']]);

        return new PolicyReport($map, $map, $verdicts, ['cart']);
    }
}
