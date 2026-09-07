<?php
/**
 * @package   Commerce_SectionPolicy
 * @copyright Copyright (c) the Commerce modules authors
 * @license   OSL-3.0 https://opensource.org/licenses/OSL-3.0
 */

declare(strict_types=1);

namespace Commerce\SectionPolicy\Test\Unit\Model\Policy\Rule;

use Commerce\SectionPolicy\Api\SectionInventoryInterface;
use Commerce\SectionPolicy\Model\Config;
use Commerce\SectionPolicy\Model\Policy\InvalidationMap;
use Commerce\SectionPolicy\Model\Policy\Rule\ExcludedSections;
use PHPUnit\Framework\TestCase;

class ExcludedSectionsTest extends TestCase
{
    private const SECTIONS = ['cart', 'customer', 'messages', 'directory-data', 'wishlist'];

    public function testTheWildcardBecomesEverySectionExceptTheExcludedOne(): void
    {
        $map = new InvalidationMap(['customer/account/logout' => ['*']]);

        $result = $this->rule(['directory-data'])->apply($map);

        $this->assertSame(
            ['cart', 'customer', 'messages', 'wishlist'],
            $result->sectionsFor('customer/account/logout')
        );
    }

    public function testItSubtractsFromAnActionThatAlreadyNamesItsSections(): void
    {
        $map = new InvalidationMap(['customer/account/logout' => ['cart', 'directory-data']]);

        $result = $this->rule(['directory-data'])->apply($map);

        $this->assertSame(['cart'], $result->sectionsFor('customer/account/logout'));
    }

    public function testExcludingNothingLeavesTheWildcardExactlyAsItWas(): void
    {
        $map = new InvalidationMap(['customer/account/logout' => ['*']]);

        $this->assertSame(['*'], $this->rule([])->apply($map)->sectionsFor('customer/account/logout'));
    }

    public function testAnActionNoModuleDeclaresIsLeftAlone(): void
    {
        $map = new InvalidationMap(['checkout/cart/add' => ['cart']]);

        $result = $this->rule(['directory-data'])->apply($map);

        $this->assertSame($map->toArray(), $result->toArray());
    }

    public function testItNeverAddsASectionTheActionDidNotHave(): void
    {
        $map = new InvalidationMap(['customer/account/logout' => ['cart']]);

        $result = $this->rule(['directory-data'])->apply($map);

        $this->assertSame(['cart'], $result->sectionsFor('customer/account/logout'));
    }

    public function testWiredAndConfiguredExclusionsAreBothHonoured(): void
    {
        $rule = new ExcludedSections(
            $this->config(['messages']),
            $this->inventory(),
            'customer/account/logout',
            'policy/never_invalidated',
            ['directory-data']
        );

        $this->assertSame(['directory-data', 'messages'], $rule->excluded());
    }

    public function testASectionNamedTwiceIsCountedOnce(): void
    {
        $rule = new ExcludedSections(
            $this->config(['directory-data']),
            $this->inventory(),
            'customer/account/logout',
            'policy/never_invalidated',
            ['directory-data']
        );

        $this->assertSame(['directory-data'], $rule->excluded());
    }

    public function testItDescribesItselfForTheReport(): void
    {
        $this->assertSame('keeps everything except directory-data', $this->rule(['directory-data'])->describe());
        $this->assertSame('excludes nothing', $this->rule([])->describe());
    }

    /**
     * @param string[] $excluded
     */
    private function rule(array $excluded): ExcludedSections
    {
        return new ExcludedSections(
            $this->config($excluded),
            $this->inventory(),
            'customer/account/logout',
            'policy/never_invalidated'
        );
    }

    /**
     * @param string[] $excluded
     */
    private function config(array $excluded): Config
    {
        $config = $this->createMock(Config::class);
        $config->method('getExcluded')->willReturn($excluded);

        return $config;
    }

    private function inventory(): SectionInventoryInterface
    {
        $inventory = $this->createMock(SectionInventoryInterface::class);
        $inventory->method('names')->willReturn(self::SECTIONS);
        $inventory->method('has')->willReturnCallback(
            static fn (string $section): bool => in_array($section, self::SECTIONS, true)
        );

        return $inventory;
    }
}
