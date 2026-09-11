<?php
/**
 * @package   Kingletas_SectionPolicy
 * @copyright Copyright (c) the Kingletas modules authors
 * @license   OSL-3.0 https://opensource.org/licenses/OSL-3.0
 */

declare(strict_types=1);

namespace Kingletas\SectionPolicy\Test\Unit;

use PHPUnit\Framework\TestCase;
use SimpleXMLElement;

/**
 * What this module does to a store that installs it and changes nothing.
 */
class ShippedDefaultsTest extends TestCase
{
    /**
     * The one that matters: installing must not silently change what a shopper is shown.
     */
    public function testThePolicyIsOffOutOfTheBox(): void
    {
        $this->assertSame('0', $this->default('policy/enabled'));
    }

    public function testNoSectionIsExcludedOutOfTheBox(): void
    {
        $this->assertSame('', $this->default('policy/never_invalidated'));
    }

    /**
     * Every rule reads the same list, so one setting governs all four shipped actions.
     */
    public function testEveryShippedRuleReadsTheSameConfiguredList(): void
    {
        $paths = [];

        foreach ($this->di()->virtualType as $virtualType) {
            foreach ($virtualType->arguments->argument as $argument) {
                if ((string) $argument['name'] === 'configPath') {
                    $paths[] = (string) $argument;
                }
            }
        }

        $this->assertNotSame([], $paths, 'No rule is wired in etc/di.xml.');
        $this->assertSame(['policy/never_invalidated'], array_values(array_unique($paths)));
    }

    /**
     * A section that says who the shopper is must be refused even when somebody names it.
     */
    public function testTheSectionsThatSayWhoTheShopperIsAreProtected(): void
    {
        $protected = [];

        foreach ($this->di()->type as $type) {
            if ((string) $type['name'] !== 'Kingletas\SectionPolicy\Model\Policy\PolicyGuard') {
                continue;
            }

            foreach ($type->arguments->argument->item as $item) {
                $protected[] = (string) $item;
            }
        }

        $this->assertSame(['customer', 'cart', 'messages'], $protected);
    }

    private function default(string $path): string
    {
        [$group, $field] = explode('/', $path);

        return (string) $this->config()->default->children()[0]->{$group}->{$field};
    }

    private function config(): SimpleXMLElement
    {
        return $this->load('etc/config.xml');
    }

    private function di(): SimpleXMLElement
    {
        return $this->load('etc/di.xml');
    }

    private function load(string $relativePath): SimpleXMLElement
    {
        $xml = simplexml_load_file(dirname(__DIR__, 2) . '/' . $relativePath);

        $this->assertInstanceOf(SimpleXMLElement::class, $xml, $relativePath . ' did not parse.');

        return $xml;
    }
}
