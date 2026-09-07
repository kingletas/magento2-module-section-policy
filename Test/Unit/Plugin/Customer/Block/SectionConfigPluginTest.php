<?php
/**
 * @package   Commerce_SectionPolicy
 * @copyright Copyright (c) the Commerce modules authors
 * @license   OSL-3.0 https://opensource.org/licenses/OSL-3.0
 */

declare(strict_types=1);

namespace Commerce\SectionPolicy\Test\Unit\Plugin\Customer\Block;

use Commerce\SectionPolicy\Api\SectionPolicyInterface;
use Commerce\SectionPolicy\Model\Policy\InvalidationMap;
use Commerce\SectionPolicy\Plugin\Customer\Block\SectionConfigPlugin;
use Magento\Customer\Block\SectionConfig;
use PHPUnit\Framework\TestCase;

class SectionConfigPluginTest extends TestCase
{
    public function testTheMapReachesThePolicyAndTheResultReachesThePage(): void
    {
        $policy = $this->createMock(SectionPolicyInterface::class);
        $policy->method('apply')->willReturn(new InvalidationMap(['customer/account/logout' => ['cart']]));

        $result = (new SectionConfigPlugin($policy))->afterGetSections(
            $this->createMock(SectionConfig::class),
            ['customer/account/logout' => ['*']]
        );

        $this->assertSame(['customer/account/logout' => ['cart']], $result);
    }

    public function testAPolicyThatChangesNothingLeavesTheMapAsItWas(): void
    {
        $sections = ['customer/account/logout' => ['*'], 'checkout/cart/add' => ['cart']];

        $policy = $this->createMock(SectionPolicyInterface::class);
        $policy->method('apply')->willReturnCallback(
            static fn (InvalidationMap $map): InvalidationMap => $map
        );

        $result = (new SectionConfigPlugin($policy))->afterGetSections(
            $this->createMock(SectionConfig::class),
            $sections
        );

        $this->assertSame($sections, $result);
    }
}
