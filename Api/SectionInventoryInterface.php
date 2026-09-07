<?php
/**
 * @package   Commerce_SectionPolicy
 * @copyright Copyright (c) the Commerce modules authors
 * @license   OSL-3.0 https://opensource.org/licenses/OSL-3.0
 */

declare(strict_types=1);

namespace Commerce\SectionPolicy\Api;

/**
 * Every customer-data section the installation has registered.
 */
interface SectionInventoryInterface
{
    /**
     * Section names, in the order the browser would have received them.
     *
     * @return string[]
     */
    public function names(): array;

    /**
     * Whether a section is registered, which is what makes a typo in a rule reportable.
     */
    public function has(string $section): bool;
}
