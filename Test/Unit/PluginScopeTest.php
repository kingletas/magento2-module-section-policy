<?php
/**
 * @package   Commerce_SectionPolicy
 * @copyright Copyright (c) the Commerce modules authors
 * @license   OSL-3.0 https://opensource.org/licenses/OSL-3.0
 */

declare(strict_types=1);

namespace Commerce\SectionPolicy\Test\Unit;

use PHPUnit\Framework\TestCase;
use SimpleXMLElement;

/**
 * The map reaches the browser through one storefront block, so the plugin is declared for that area.
 */
class PluginScopeTest extends TestCase
{
    private const INTERCEPTED = 'Magento\Customer\Block\SectionConfig';
    private const PLUGIN = 'Commerce\SectionPolicy\Plugin\Customer\Block\SectionConfigPlugin';

    public function testThePluginIsDeclaredForTheStorefront(): void
    {
        $this->assertSame(
            self::PLUGIN,
            $this->pluginOn('etc/frontend/di.xml'),
            'etc/frontend/di.xml must plug ' . self::INTERCEPTED . '.'
        );
    }

    /**
     * Declaring it twice would run the policy twice over the same map.
     */
    public function testTheGlobalScopeDeclaresNoPluginOnTheBlock(): void
    {
        $this->assertNull($this->pluginOn('etc/di.xml'));
    }

    private function pluginOn(string $relativePath): ?string
    {
        $xml = simplexml_load_file(dirname(__DIR__, 2) . '/' . $relativePath);

        $this->assertInstanceOf(SimpleXMLElement::class, $xml, $relativePath . ' did not parse.');

        foreach ($xml->type as $type) {
            if ((string) $type['name'] !== self::INTERCEPTED) {
                continue;
            }

            foreach ($type->plugin as $plugin) {
                return (string) $plugin['type'];
            }
        }

        return null;
    }
}
