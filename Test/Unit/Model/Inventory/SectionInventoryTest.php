<?php
/**
 * @package   Kingletas_SectionPolicy
 * @copyright Copyright (c) the Kingletas modules authors
 * @license   OSL-3.0 https://opensource.org/licenses/OSL-3.0
 */

declare(strict_types=1);

namespace Kingletas\SectionPolicy\Test\Unit\Model\Inventory;

use Kingletas\SectionPolicy\Model\Inventory\SectionInventory;
use Magento\Customer\Block\SectionNamesProvider;
use Magento\Customer\Block\SectionNamesProviderFactory;
use PHPUnit\Framework\TestCase;

class SectionInventoryTest extends TestCase
{
    public function testItReportsWhatTheStorefrontRegistered(): void
    {
        $this->assertSame(['cart', 'customer'], $this->inventory(['cart', 'customer'])->names());
    }

    public function testAGappedListIsHandedBackAsAPlainList(): void
    {
        $this->assertSame(['cart', 'customer'], $this->inventory([3 => 'cart', 7 => 'customer'])->names());
    }

    public function testItKnowsWhetherASectionExists(): void
    {
        $inventory = $this->inventory(['cart', 'customer']);

        $this->assertTrue($inventory->has('cart'));
        $this->assertFalse($inventory->has('directry-data'));
    }

    /**
     * An empty inventory is what a command outside the storefront wiring sees, and it must not read as valid.
     */
    public function testAnEmptyInventoryIsReportedAsEmptyRatherThanGuessed(): void
    {
        $this->assertSame([], $this->inventory([])->names());
        $this->assertFalse($this->inventory([])->has('cart'));
    }

    /**
     * Building the provider in a constructor would bind it to whatever area happened to be
     * current, and outside the storefront that is an empty list.
     */
    public function testTheProviderIsNotBuiltUntilTheInventoryIsAsked(): void
    {
        $factory = $this->createMock(SectionNamesProviderFactory::class);
        $factory->expects($this->never())->method('create');

        new SectionInventory($factory);
    }

    public function testTheProviderIsBuiltOnceHoweverOftenItIsAsked(): void
    {
        $provider = $this->createMock(SectionNamesProvider::class);
        $provider->method('getSectionNames')->willReturn(['cart']);

        $factory = $this->createMock(SectionNamesProviderFactory::class);
        $factory->expects($this->once())->method('create')->willReturn($provider);

        $inventory = new SectionInventory($factory);
        $inventory->names();
        $inventory->has('cart');
    }

    /**
     * @param array<int, string> $names
     */
    private function inventory(array $names): SectionInventory
    {
        $provider = $this->createMock(SectionNamesProvider::class);
        $provider->method('getSectionNames')->willReturn($names);

        $factory = $this->createMock(SectionNamesProviderFactory::class);
        $factory->method('create')->willReturn($provider);

        return new SectionInventory($factory);
    }
}
