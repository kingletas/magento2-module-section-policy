<?php
/**
 * @package   Commerce_SectionPolicy
 * @copyright Copyright (c) the Commerce modules authors
 * @license   OSL-3.0 https://opensource.org/licenses/OSL-3.0
 */

declare(strict_types=1);

namespace Commerce\SectionPolicy\Plugin\Customer\Block;

use Commerce\SectionPolicy\Api\SectionPolicyInterface;
use Commerce\SectionPolicy\Model\Policy\InvalidationMap;
use Magento\Customer\Block\SectionConfig;

/**
 * Narrows the map on its way into the page, which is the only place the browser reads it from.
 *
 * @SuppressWarnings("PHPMD.UnusedFormalParameter")
 */
class SectionConfigPlugin
{
    public function __construct(private readonly SectionPolicyInterface $policy)
    {
    }

    /**
     * @param array<string, string[]> $result
     * @return array<string, string[]>
     */
    public function afterGetSections(SectionConfig $subject, array $result): array
    {
        return $this->policy->apply(new InvalidationMap($result))->toArray();
    }
}
