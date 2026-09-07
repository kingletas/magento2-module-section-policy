<?php
/**
 * @package   Commerce_SectionPolicy
 * @copyright Copyright (c) the Commerce modules authors
 * @license   OSL-3.0 https://opensource.org/licenses/OSL-3.0
 */

declare(strict_types=1);

namespace Commerce\SectionPolicy\Test\Unit\Model\Inventory;

use Commerce\SectionPolicy\Api\SectionInventoryInterface;
use Commerce\SectionPolicy\Model\Inventory\PayloadSampler;
use Magento\Customer\CustomerData\SectionPoolInterface;
use Magento\Customer\CustomerData\SectionPoolInterfaceFactory;
use Magento\Framework\Serialize\Serializer\Json;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class PayloadSamplerTest extends TestCase
{
    public function testEverySectionIsMeasuredOnce(): void
    {
        $measurements = $this->sampler(['cart' => ['a' => 1], 'customer' => []])->sample();

        $this->assertSame(['cart', 'customer'], array_map(
            static fn ($measurement): string => $measurement->section(),
            $measurements
        ));
    }

    public function testTheBiggestSectionIsReportedFirst(): void
    {
        $measurements = $this->sampler([
            'customer' => [],
            'directory-data' => ['US' => ['name' => 'United States']],
            'cart' => ['count' => 0],
        ])->sample();

        $this->assertSame('directory-data', $measurements[0]->section());
    }

    /**
     * One section that cannot be produced must not take the other eighteen down with it.
     */
    public function testASectionThatThrowsIsReportedAndTheRestAreStillMeasured(): void
    {
        $sampler = $this->sampler(['cart' => ['count' => 0], 'captcha' => new RuntimeException('needs a request')]);

        $measurements = $sampler->sample();
        $failed = array_values(array_filter($measurements, static fn ($m): bool => !$m->measured()));

        $this->assertCount(2, $measurements);
        $this->assertCount(1, $failed);
        $this->assertSame('captcha', $failed[0]->section());
        $this->assertSame('needs a request', $failed[0]->note());
    }

    public function testTheTotalCountsOnlyWhatCouldBeMeasured(): void
    {
        $sampler = $this->sampler(['cart' => ['count' => 0], 'captcha' => new RuntimeException('no')]);
        $measurements = $sampler->sample();

        $this->assertSame(strlen('{"count":0}'), $sampler->total($measurements));
    }

    /**
     * The pool is bound only in the storefront, so building one in a constructor cannot be
     * resolved by a command and takes every `bin/magento` call with it.
     */
    public function testThePoolIsNotBuiltUntilASectionIsMeasured(): void
    {
        $factory = $this->createMock(SectionPoolInterfaceFactory::class);
        $factory->expects($this->never())->method('create');

        new PayloadSampler($factory, $this->createMock(SectionInventoryInterface::class), new Json());
    }

    /**
     * @param array<string, mixed> $sections Section data, or an exception the pool should throw for it.
     */
    private function sampler(array $sections): PayloadSampler
    {
        $pool = $this->createMock(SectionPoolInterface::class);
        $pool->method('getSectionsData')->willReturnCallback(
            static function (?array $names) use ($sections): array {
                $name = (string) ($names[0] ?? '');
                $value = $sections[$name] ?? [];

                if ($value instanceof \Throwable) {
                    throw $value;
                }

                return [$name => $value];
            }
        );

        $inventory = $this->createMock(SectionInventoryInterface::class);
        $inventory->method('names')->willReturn(array_keys($sections));

        $factory = $this->createMock(SectionPoolInterfaceFactory::class);
        $factory->method('create')->willReturn($pool);

        return new PayloadSampler($factory, $inventory, new Json());
    }
}
