<?php
/**
 * @package   Commerce_SectionPolicy
 * @copyright Copyright (c) the Commerce modules authors
 * @license   OSL-3.0 https://opensource.org/licenses/OSL-3.0
 */

declare(strict_types=1);

namespace Commerce\SectionPolicy\Model\Inventory;

/**
 * What one section cost to produce, for an anonymous visitor.
 */
class SectionMeasurement
{
    public function __construct(
        private readonly string $section,
        private readonly ?int $bytes,
        private readonly string $note = ''
    ) {
    }

    public function section(): string
    {
        return $this->section;
    }

    /**
     * Serialized size, or null when the section could not be produced outside a real request.
     */
    public function bytes(): ?int
    {
        return $this->bytes;
    }

    public function measured(): bool
    {
        return $this->bytes !== null;
    }

    /**
     * Why there is no number, when there is no number.
     */
    public function note(): string
    {
        return $this->note;
    }
}
