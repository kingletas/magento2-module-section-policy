<?php
/**
 * @package   Commerce_SectionPolicy
 * @copyright Copyright (c) the Commerce modules authors
 * @license   OSL-3.0 https://opensource.org/licenses/OSL-3.0
 */

declare(strict_types=1);

namespace Commerce\SectionPolicy\Model\Inventory;

use Commerce\SectionPolicy\Api\SectionInventoryInterface;
use Magento\Customer\CustomerData\SectionPoolInterface;
use Magento\Customer\CustomerData\SectionPoolInterfaceFactory;
use Magento\Framework\Serialize\SerializerInterface;
use Throwable;

/**
 * Measures what each section adds to the response, one section at a time.
 */
class PayloadSampler
{
    /**
     * The pool is bound only in the storefront's `di.xml`, so asking for one in a command's
     * constructor cannot be resolved and takes every `bin/magento` call down with it.
     */
    private ?SectionPoolInterface $sectionPool = null;

    public function __construct(
        private readonly SectionPoolInterfaceFactory $sectionPoolFactory,
        private readonly SectionInventoryInterface $inventory,
        private readonly SerializerInterface $serializer
    ) {
    }

    /**
     * A section that cannot be produced here is reported as unmeasured rather than failing the run.
     *
     * @return SectionMeasurement[]
     */
    public function sample(): array
    {
        $measurements = [];

        foreach ($this->inventory->names() as $section) {
            $measurements[] = $this->measure($section);
        }

        usort(
            $measurements,
            static fn (SectionMeasurement $a, SectionMeasurement $b): int => ($b->bytes() ?? -1) <=> ($a->bytes() ?? -1)
        );

        return $measurements;
    }

    /**
     * Total of every section that could be measured.
     *
     * @param SectionMeasurement[] $measurements
     */
    public function total(array $measurements): int
    {
        return array_sum(array_map(
            static fn (SectionMeasurement $measurement): int => $measurement->bytes() ?? 0,
            $measurements
        ));
    }

    private function measure(string $section): SectionMeasurement
    {
        try {
            $data = $this->pool()->getSectionsData([$section]);
        } catch (Throwable $error) {
            return new SectionMeasurement($section, null, $error->getMessage());
        }

        return new SectionMeasurement($section, strlen($this->serializer->serialize($data[$section] ?? [])));
    }

    private function pool(): SectionPoolInterface
    {
        return $this->sectionPool ??= $this->sectionPoolFactory->create();
    }
}
