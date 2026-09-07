<?php
/**
 * @package   Commerce_SectionPolicy
 * @copyright Copyright (c) the Commerce modules authors
 * @license   OSL-3.0 https://opensource.org/licenses/OSL-3.0
 */

declare(strict_types=1);

namespace Commerce\SectionPolicy\Test\Unit\Model\Policy;

use Commerce\SectionPolicy\Api\SectionInventoryInterface;
use Commerce\SectionPolicy\Api\SectionRuleInterface;
use Commerce\SectionPolicy\Model\Config;
use Commerce\SectionPolicy\Model\Policy\InvalidationMap;
use Commerce\SectionPolicy\Model\Policy\PolicyGuard;
use Commerce\SectionPolicy\Model\Policy\RuleOutcome;
use PHPUnit\Framework\TestCase;

class PolicyGuardTest extends TestCase
{
    private const SECTIONS = ['cart', 'customer', 'messages', 'directory-data', 'wishlist'];
    private const PROTECTED = ['customer', 'cart', 'messages'];
    private const ACTION = 'customer/account/logout';

    public function testAWellFormedRuleOnAWideActionApplies(): void
    {
        $verdict = $this->grade(['directory-data'], true);

        $this->assertSame(RuleOutcome::Applies, $verdict->outcome());
        $this->assertTrue($verdict->applies());
    }

    public function testARuleForAnActionNobodyDeclaresIsAMistake(): void
    {
        $verdict = $this->guard(true)->grade(
            $this->rule(['directory-data'], 'customer/account/logoutt'),
            new InvalidationMap([self::ACTION => ['*']])
        );

        $this->assertTrue($verdict->isMisconfigured());
        $this->assertStringContainsString('no installed module declares', $verdict->reason());
    }

    public function testASectionThatIsNotRegisteredIsAMistakeRatherThanIgnored(): void
    {
        $verdict = $this->grade(['directry-data'], true);

        $this->assertTrue($verdict->isMisconfigured());
        $this->assertStringContainsString('not a registered section', $verdict->reason());
    }

    public function testASectionSayingWhoTheShopperIsIsRefused(): void
    {
        foreach (self::PROTECTED as $section) {
            $verdict = $this->grade([$section], true);

            $this->assertTrue($verdict->isMisconfigured(), $section . ' must be refused');
            $this->assertStringContainsString('never excluded', $verdict->reason());
        }
    }

    public function testARuleExcludingNothingIsDeliberateRatherThanBroken(): void
    {
        $verdict = $this->grade([], true);

        $this->assertSame(RuleOutcome::NotApplied, $verdict->outcome());
        $this->assertFalse($verdict->isMisconfigured());
        $this->assertSame('excludes nothing', $verdict->reason());
    }

    public function testTheSwitchBeingOffStopsTheRuleWithoutCallingItAMistake(): void
    {
        $verdict = $this->grade(['directory-data'], false);

        $this->assertSame(RuleOutcome::NotApplied, $verdict->outcome());
        $this->assertSame('the policy is switched off', $verdict->reason());
    }

    /**
     * A typo has to be reportable before anyone turns the policy on, or it is found the hard way.
     */
    public function testAMistakeIsStillReportedWhileTheSwitchIsOff(): void
    {
        $this->assertTrue($this->grade(['directry-data'], false)->isMisconfigured());
        $this->assertTrue($this->grade(['customer'], false)->isMisconfigured());
    }

    /**
     * @param string[] $excluded
     */
    private function grade(array $excluded, bool $enabled): \Commerce\SectionPolicy\Model\Policy\RuleVerdict
    {
        return $this->guard($enabled)->grade(
            $this->rule($excluded, self::ACTION),
            new InvalidationMap([self::ACTION => ['*']])
        );
    }

    private function guard(bool $enabled): PolicyGuard
    {
        $config = $this->createMock(Config::class);
        $config->method('isEnabled')->willReturn($enabled);

        $inventory = $this->createMock(SectionInventoryInterface::class);
        $inventory->method('has')->willReturnCallback(
            static fn (string $section): bool => in_array($section, self::SECTIONS, true)
        );

        return new PolicyGuard($config, $inventory, self::PROTECTED);
    }

    /**
     * @param string[] $excluded
     */
    private function rule(array $excluded, string $action): SectionRuleInterface
    {
        $rule = $this->createMock(SectionRuleInterface::class);
        $rule->method('action')->willReturn($action);
        $rule->method('excluded')->willReturn($excluded);
        $rule->method('describe')->willReturn('keeps everything except ' . implode(', ', $excluded));

        return $rule;
    }
}
