<?php
/**
 * @package   Kingletas_SectionPolicy
 * @copyright Copyright (c) the Kingletas modules authors
 * @license   OSL-3.0 https://opensource.org/licenses/OSL-3.0
 */

declare(strict_types=1);

namespace Kingletas\SectionPolicy\Test\Unit\Model\Inventory;

use Kingletas\SectionPolicy\Model\Inventory\SectionMeasurement;
use PHPUnit\Framework\TestCase;

class SectionMeasurementTest extends TestCase
{
    public function testAMeasuredSectionCarriesItsSize(): void
    {
        $measurement = new SectionMeasurement('directory-data', 59506);

        $this->assertSame('directory-data', $measurement->section());
        $this->assertSame(59506, $measurement->bytes());
        $this->assertTrue($measurement->measured());
        $this->assertSame('', $measurement->note());
    }

    public function testASectionThatCouldNotBeProducedSaysSoRatherThanReportingZero(): void
    {
        $measurement = new SectionMeasurement('captcha', null, 'needs a request');

        $this->assertNull($measurement->bytes());
        $this->assertFalse($measurement->measured());
        $this->assertSame('needs a request', $measurement->note());
    }

    public function testAnEmptySectionIsMeasuredRatherThanUnmeasurable(): void
    {
        $this->assertTrue((new SectionMeasurement('customer', 0))->measured());
    }
}
