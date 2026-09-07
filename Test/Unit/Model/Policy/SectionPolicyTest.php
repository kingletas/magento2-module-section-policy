<?php
/**
 * @package   Commerce_SectionPolicy
 * @copyright Copyright (c) the Commerce modules authors
 * @license   OSL-3.0 https://opensource.org/licenses/OSL-3.0
 */

declare(strict_types=1);

namespace Commerce\SectionPolicy\Test\Unit\Model\Policy;

use Commerce\SectionPolicy\Api\SectionRuleInterface;
use Commerce\SectionPolicy\Model\Policy\InvalidationMap;
use Commerce\SectionPolicy\Model\Policy\PolicyGuard;
use Commerce\SectionPolicy\Model\Policy\RuleOutcome;
use Commerce\SectionPolicy\Model\Policy\RuleVerdict;
use Commerce\SectionPolicy\Model\Policy\SectionPolicy;
use PHPUnit\Framework\TestCase;

class SectionPolicyTest extends TestCase
{
    private const ACTION = 'customer/account/logout';

    public function testWithNoRulesTheMapIsHandedBackUntouched(): void
    {
        $map = new InvalidationMap([self::ACTION => ['*']]);

        $this->assertSame($map->toArray(), (new SectionPolicy($this->guard([]), []))->apply($map)->toArray());
    }

    public function testOnlyTheRulesTheGuardAllowsAreApplied(): void
    {
        $allowed = $this->rule('allowed', ['cart']);
        $refused = $this->rule('refused', ['customer']);

        $policy = new SectionPolicy(
            $this->guard([
                'allowed' => RuleOutcome::Applies,
                'refused' => RuleOutcome::Misconfigured,
            ]),
            [$allowed, $refused]
        );

        $this->assertSame(['cart'], $policy->apply(new InvalidationMap([self::ACTION => ['*']]))
            ->sectionsFor(self::ACTION));
    }

    public function testEachRuleSeesWhatThePreviousOneLeftBehind(): void
    {
        $first = $this->rule('first', ['cart', 'customer', 'wishlist']);
        $second = $this->rule('second', ['cart', 'customer']);

        $policy = new SectionPolicy(
            $this->guard(['first' => RuleOutcome::Applies, 'second' => RuleOutcome::Applies]),
            [$first, $second]
        );

        $this->assertSame(['cart', 'customer'], $policy->apply(new InvalidationMap([self::ACTION => ['*']]))
            ->sectionsFor(self::ACTION));
    }

    public function testItReportsAVerdictForEveryRuleIncludingTheOnesItDidNotApply(): void
    {
        $policy = new SectionPolicy(
            $this->guard(['a' => RuleOutcome::Applies, 'b' => RuleOutcome::Misconfigured]),
            [$this->rule('a', ['cart']), $this->rule('b', ['customer'])]
        );

        $verdicts = $policy->verdicts(new InvalidationMap([self::ACTION => ['*']]));

        $this->assertCount(2, $verdicts);
        $this->assertTrue($verdicts[0]->applies());
        $this->assertTrue($verdicts[1]->isMisconfigured());
    }

    /**
     * @param array<string, RuleOutcome> $outcomes Keyed by the rule's action.
     */
    private function guard(array $outcomes): PolicyGuard
    {
        $guard = $this->createMock(PolicyGuard::class);
        $guard->method('grade')->willReturnCallback(
            static fn (SectionRuleInterface $rule): RuleVerdict => new RuleVerdict(
                $rule->action(),
                $outcomes[$rule->action()] ?? RuleOutcome::NotApplied,
                'graded in the test'
            )
        );

        return $guard;
    }

    /**
     * A rule that simply replaces the action with the sections it was given.
     *
     * @param string[] $result
     */
    private function rule(string $action, array $result): SectionRuleInterface
    {
        $rule = $this->createMock(SectionRuleInterface::class);
        $rule->method('action')->willReturn($action);
        $rule->method('apply')->willReturnCallback(
            static fn (InvalidationMap $map): InvalidationMap => $map->withSectionsFor(self::ACTION, $result)
        );

        return $rule;
    }
}
