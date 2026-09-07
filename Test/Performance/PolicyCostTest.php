<?php
/**
 * @package   Commerce_SectionPolicy
 * @copyright Copyright (c) the Commerce modules authors
 * @license   OSL-3.0 https://opensource.org/licenses/OSL-3.0
 */

declare(strict_types=1);

namespace Commerce\SectionPolicy\Test\Performance;

use Commerce\Foundation\Test\Support\BudgetAssertions;
use Commerce\Foundation\Test\Support\CountingScopeConfig;
use Commerce\SectionPolicy\Api\SectionInventoryInterface;
use Commerce\SectionPolicy\Model\Config;
use Commerce\SectionPolicy\Model\Policy\InvalidationMap;
use Commerce\SectionPolicy\Model\Policy\PolicyGuard;
use Commerce\SectionPolicy\Model\Policy\Rule\ExcludedSections;
use Commerce\SectionPolicy\Model\Policy\SectionPolicy;
use Commerce\SectionPolicy\Plugin\Customer\Block\SectionConfigPlugin;
use Magento\Customer\Block\SectionConfig;
use PHPUnit\Framework\TestCase;

/**
 * What this module costs a page render, given that it runs on every one of them.
 */
class PolicyCostTest extends TestCase
{
    use BudgetAssertions;

    private const SECTION = 'commerce_sectionpolicy';
    private const PATH = 'policy/never_invalidated';

    /**
     * A store with many declared actions must not cost more per action.
     */
    public function testTheCostDoesNotGrowWithTheNumberOfActions(): void
    {
        $this->assertConstantCost(
            'config reads while narrowing a map',
            fn (int $actions): int => $this->render($actions)
        );
    }

    /**
     * A store with many registered sections must not cost more per section.
     */
    public function testTheCostDoesNotGrowWithTheNumberOfSections(): void
    {
        $this->assertConstantCost(
            'config reads while narrowing a map over many sections',
            fn (int $sections): int => $this->render(10, $sections)
        );
    }

    /**
     * The switch and the list, once for the single shipped rule.
     */
    public function testOneRuleIsDecidedByTwoConfigReads(): void
    {
        $this->assertCostAtMost('config reads for one rule', 2, $this->render(10));
    }

    private function render(int $actions, int $sections = 19): int
    {
        $scopeConfig = new CountingScopeConfig([
            self::SECTION . '/policy/enabled' => '1',
            self::SECTION . '/' . self::PATH => 'directory-data',
        ]);

        $config = new Config($scopeConfig, self::SECTION);
        $inventory = $this->inventory($sections);

        $policy = new SectionPolicy(
            new PolicyGuard($config, $inventory, ['customer', 'cart', 'messages']),
            [new ExcludedSections($config, $inventory, 'customer/account/logout', self::PATH)]
        );

        (new SectionConfigPlugin($policy))->afterGetSections(
            $this->createMock(SectionConfig::class),
            $this->map($actions)
        );

        return $scopeConfig->reads();
    }

    /**
     * @return array<string, string[]>
     */
    private function map(int $actions): array
    {
        $map = ['customer/account/logout' => ['*']];

        for ($i = 0; $i < $actions; $i++) {
            $map['module/controller/action' . $i] = ['cart'];
        }

        return $map;
    }

    private function inventory(int $sections): SectionInventoryInterface
    {
        $names = ['directory-data', 'customer', 'cart', 'messages'];

        for ($i = count($names); $i < $sections; $i++) {
            $names[] = 'section' . $i;
        }

        $inventory = $this->createMock(SectionInventoryInterface::class);
        $inventory->method('names')->willReturn($names);
        $inventory->method('has')->willReturnCallback(
            static fn (string $section): bool => in_array($section, $names, true)
        );

        return $inventory;
    }
}
