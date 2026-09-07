<?php
/**
 * @package   Commerce_SectionPolicy
 * @copyright Copyright (c) the Commerce modules authors
 * @license   OSL-3.0 https://opensource.org/licenses/OSL-3.0
 */

declare(strict_types=1);

namespace Commerce\SectionPolicy\Test\Unit\Model;

use Commerce\Foundation\Test\Support\CountingScopeConfig;
use Commerce\SectionPolicy\Model\Config;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    private const SECTION = 'commerce_sectionpolicy';

    public function testTheSwitchIsOffWhenNothingIsStored(): void
    {
        $this->assertFalse($this->config([])->isEnabled());
    }

    public function testTheSwitchReadsItsOwnSection(): void
    {
        $config = $this->config([self::SECTION . '/policy/enabled' => '1']);

        $this->assertTrue($config->isEnabled());
    }

    public function testTheExclusionListIsEmptyWhenNothingIsStored(): void
    {
        $this->assertSame([], $this->config([])->getExcluded('policy/never_invalidated'));
    }

    public function testTheExclusionListIsSplitAndTrimmed(): void
    {
        $config = $this->config([
            self::SECTION . '/policy/never_invalidated' => 'directory-data, wishlist ,',
        ]);

        $this->assertSame(['directory-data', 'wishlist'], $config->getExcluded('policy/never_invalidated'));
    }

    /**
     * @param array<string, mixed> $values
     */
    private function config(array $values): Config
    {
        return new Config(new CountingScopeConfig($values), self::SECTION);
    }
}
