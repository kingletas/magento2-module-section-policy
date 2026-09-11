<?php
/**
 * @package   Kingletas_SectionPolicy
 * @copyright Copyright (c) the Kingletas modules authors
 * @license   OSL-3.0 https://opensource.org/licenses/OSL-3.0
 */

declare(strict_types=1);

namespace Kingletas\SectionPolicy\Test\Unit\Model\Policy;

use Kingletas\SectionPolicy\Model\Policy\RuleOutcome;
use Kingletas\SectionPolicy\Model\Policy\RuleVerdict;
use PHPUnit\Framework\TestCase;

class RuleVerdictTest extends TestCase
{
    public function testOnlyAnApplyingVerdictApplies(): void
    {
        $verdict = new RuleVerdict('customer/account/logout', RuleOutcome::Applies, 'keeps everything except x');

        $this->assertTrue($verdict->applies());
        $this->assertFalse($verdict->isMisconfigured());
        $this->assertSame('customer/account/logout', $verdict->action());
        $this->assertSame('keeps everything except x', $verdict->reason());
    }

    public function testAMisconfiguredVerdictIsNeitherAppliedNorSilent(): void
    {
        $verdict = new RuleVerdict('a/b/c', RuleOutcome::Misconfigured, 'not a registered section');

        $this->assertFalse($verdict->applies());
        $this->assertTrue($verdict->isMisconfigured());
    }

    public function testADeliberateNonApplicationIsNotAMistake(): void
    {
        $verdict = new RuleVerdict('a/b/c', RuleOutcome::NotApplied, 'excludes nothing');

        $this->assertFalse($verdict->applies());
        $this->assertFalse($verdict->isMisconfigured());
    }
}
