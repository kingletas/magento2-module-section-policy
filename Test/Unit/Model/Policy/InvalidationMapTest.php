<?php
/**
 * @package   Kingletas_SectionPolicy
 * @copyright Copyright (c) the Kingletas modules authors
 * @license   OSL-3.0 https://opensource.org/licenses/OSL-3.0
 */

declare(strict_types=1);

namespace Kingletas\SectionPolicy\Test\Unit\Model\Policy;

use Kingletas\SectionPolicy\Model\Policy\InvalidationMap;
use PHPUnit\Framework\TestCase;

class InvalidationMapTest extends TestCase
{
    public function testItReportsTheActionsItWasGiven(): void
    {
        $map = new InvalidationMap(['customer/account/logout' => ['*'], 'checkout/cart/add' => ['cart']]);

        $this->assertSame(['customer/account/logout', 'checkout/cart/add'], $map->actions());
    }

    public function testAnUndeclaredActionInvalidatesNothing(): void
    {
        $map = new InvalidationMap(['checkout/cart/add' => ['cart']]);

        $this->assertFalse($map->has('customer/account/logout'));
        $this->assertSame([], $map->sectionsFor('customer/account/logout'));
        $this->assertFalse($map->invalidatesEverything('customer/account/logout'));
    }

    public function testTheWildcardIsWhatMakesAnActionWide(): void
    {
        $map = new InvalidationMap([
            'customer/account/logout' => ['*'],
            'checkout/cart/add' => ['cart'],
            'stores/store/switch' => ['*', 'persistent'],
        ]);

        $this->assertSame(['customer/account/logout', 'stores/store/switch'], $map->wildcardActions());
    }

    public function testReplacingAnActionLeavesEveryOtherActionAlone(): void
    {
        $map = new InvalidationMap(['customer/account/logout' => ['*'], 'checkout/cart/add' => ['cart']]);

        $narrowed = $map->withSectionsFor('customer/account/logout', ['cart', 'customer']);

        $this->assertSame(['cart', 'customer'], $narrowed->sectionsFor('customer/account/logout'));
        $this->assertSame(['cart'], $narrowed->sectionsFor('checkout/cart/add'));
    }

    public function testTheOriginalIsNotChangedByReplacingAnAction(): void
    {
        $map = new InvalidationMap(['customer/account/logout' => ['*']]);

        $map->withSectionsFor('customer/account/logout', ['cart']);

        $this->assertSame(['*'], $map->sectionsFor('customer/account/logout'));
    }

    public function testARepeatedSectionIsStoredOnce(): void
    {
        $map = (new InvalidationMap(['a/b/c' => ['*']]))->withSectionsFor('a/b/c', ['cart', 'cart', 'customer']);

        $this->assertSame(['cart', 'customer'], $map->sectionsFor('a/b/c'));
    }

    public function testItHandsBackTheShapeTheBrowserExpects(): void
    {
        $actions = ['customer/account/logout' => ['*']];

        $this->assertSame($actions, (new InvalidationMap($actions))->toArray());
    }
}
