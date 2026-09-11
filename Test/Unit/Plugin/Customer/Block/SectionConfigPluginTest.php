<?php
/**
 * @package   Kingletas_SectionPolicy
 * @copyright Copyright (c) the Kingletas modules authors
 * @license   OSL-3.0 https://opensource.org/licenses/OSL-3.0
 */

declare(strict_types=1);

namespace Kingletas\SectionPolicy\Test\Unit\Plugin\Customer\Block;

use Kingletas\SectionPolicy\Api\SectionPolicyInterface;
use Kingletas\SectionPolicy\Model\Policy\InvalidationMap;
use Kingletas\SectionPolicy\Plugin\Customer\Block\SectionConfigPlugin;
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
