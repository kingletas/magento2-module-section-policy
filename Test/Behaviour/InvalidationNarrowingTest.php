<?php
/**
 * @package   Commerce_SectionPolicy
 * @copyright Copyright (c) the Commerce modules authors
 * @license   OSL-3.0 https://opensource.org/licenses/OSL-3.0
 */

declare(strict_types=1);

namespace Commerce\SectionPolicy\Test\Behaviour;

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
 * What the browser is handed, from the map a stock storefront emits through to the narrowed one.
 */
class InvalidationNarrowingTest extends TestCase
{
    private const SECTION = 'commerce_sectionpolicy';
    private const PATH = 'policy/never_invalidated';

    /**
     * The sections a 2.4 storefront registers, in the order the block reports them.
     *
     * @var string[]
     */
    private const REGISTERED = [
        'messages', 'customer', 'compare-products', 'last-ordered-items', 'cart', 'directory-data',
        'captcha', 'instant-purchase', 'loggedAsCustomer', 'multiplewishlist', 'paypal-billing-agreement',
        'paypal-buyer-country', 'persistent', 'product_data_storage', 'recently_compared_product',
        'recently_viewed_product', 'review', 'wishlist', 'payments',
    ];

    /**
     * The actions that ship with the invalidate-everything marker, beside two that name their sections.
     *
     * @var array<string, string[]>
     */
    private const STOCK_MAP = [
        'customer/account/loginpost' => ['*'],
        'customer/account/logout' => ['*'],
        'customer/account/createpost' => ['*'],
        'customer/account/editpost' => ['*'],
        'stores/store/switch' => ['*'],
        'directory/currency/switch' => ['*'],
        'checkout/cart/add' => ['cart'],
        'checkout/sidebar/removeitem' => ['cart'],
    ];

    /**
     * The whole point of the module, stated once: logging in stops refetching the country list.
     */
    public function testLoggingInNoLongerRefetchesTheCountryList(): void
    {
        $narrowed = $this->render('directory-data');

        $sections = $narrowed['customer/account/loginpost'];

        $this->assertNotContains('directory-data', $sections);
        $this->assertContains('cart', $sections);
        $this->assertContains('customer', $sections);
        $this->assertCount(count(self::REGISTERED) - 1, $sections);
    }

    public function testAnActionWithNoRuleKeepsInvalidatingEverything(): void
    {
        $narrowed = $this->render('directory-data');

        $this->assertSame(['*'], $narrowed['stores/store/switch']);
        $this->assertSame(['*'], $narrowed['directory/currency/switch']);
    }

    public function testAnActionThatAlreadyNamedItsSectionsIsUntouched(): void
    {
        $narrowed = $this->render('directory-data');

        $this->assertSame(['cart'], $narrowed['checkout/cart/add']);
        $this->assertSame(['cart'], $narrowed['checkout/sidebar/removeitem']);
    }

    /**
     * A section nobody named keeps being invalidated, so an extension's new section is never dropped.
     */
    public function testEverySectionExceptTheNamedOneSurvives(): void
    {
        $narrowed = $this->render('directory-data');

        foreach (self::REGISTERED as $section) {
            if ($section === 'directory-data') {
                continue;
            }

            $this->assertContains($section, $narrowed['customer/account/logout'], $section . ' was dropped');
        }
    }

    public function testInstallingItAndNamingNothingChangesNothingAtAll(): void
    {
        $this->assertSame(self::STOCK_MAP, $this->render('', true));
    }

    public function testTheSwitchBeingOffChangesNothingAtAll(): void
    {
        $this->assertSame(self::STOCK_MAP, $this->render('directory-data', false));
    }

    /**
     * A protected section is refused rather than applied, so the map comes back untouched.
     */
    public function testNamingASectionThatSaysWhoTheShopperIsChangesNothing(): void
    {
        $this->assertSame(self::STOCK_MAP, $this->render('customer'));
    }

    public function testATypoChangesNothingRatherThanNarrowingByAccident(): void
    {
        $this->assertSame(self::STOCK_MAP, $this->render('directry-data'));
    }

    /**
     * @return array<string, string[]>
     */
    private function render(string $excluded, bool $enabled = true): array
    {
        $config = new Config(
            new CountingScopeConfig([
                self::SECTION . '/policy/enabled' => $enabled ? '1' : '0',
                self::SECTION . '/' . self::PATH => $excluded,
            ]),
            self::SECTION
        );

        $inventory = $this->inventory();
        $guard = new PolicyGuard($config, $inventory, ['customer', 'cart', 'messages']);

        $rules = [];

        foreach (['loginpost', 'logout', 'createpost', 'editpost'] as $action) {
            $rules[] = new ExcludedSections($config, $inventory, 'customer/account/' . $action, self::PATH);
        }

        $plugin = new SectionConfigPlugin(new SectionPolicy($guard, $rules));

        return $plugin->afterGetSections($this->createMock(SectionConfig::class), self::STOCK_MAP);
    }

    private function inventory(): SectionInventoryInterface
    {
        $inventory = $this->createMock(SectionInventoryInterface::class);
        $inventory->method('names')->willReturn(self::REGISTERED);
        $inventory->method('has')->willReturnCallback(
            static fn (string $section): bool => in_array($section, self::REGISTERED, true)
        );

        return $inventory;
    }
}
