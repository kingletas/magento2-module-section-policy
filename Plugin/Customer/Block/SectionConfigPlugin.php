<?php
/**
 * @package   Kingletas_SectionPolicy
 * @copyright Copyright (c) the Kingletas modules authors
 * @license   OSL-3.0 https://opensource.org/licenses/OSL-3.0
 */

declare(strict_types=1);

namespace Kingletas\SectionPolicy\Plugin\Customer\Block;

use Kingletas\SectionPolicy\Api\SectionPolicyInterface;
use Kingletas\SectionPolicy\Model\Policy\InvalidationMap;
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
