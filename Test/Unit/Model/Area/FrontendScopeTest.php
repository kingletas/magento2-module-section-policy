<?php
/**
 * @package   Kingletas_SectionPolicy
 * @copyright Copyright (c) the Kingletas modules authors
 * @license   OSL-3.0 https://opensource.org/licenses/OSL-3.0
 */

declare(strict_types=1);

namespace Kingletas\SectionPolicy\Test\Unit\Model\Area;

use Kingletas\SectionPolicy\Model\Area\FrontendScope;
use Magento\Framework\App\Area;
use Magento\Framework\App\AreaList;
use Magento\Framework\App\State;
use Magento\Framework\Config\ScopeInterface;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class FrontendScopeTest extends TestCase
{
    /**
     * The area code alone leaves the storefront's own wiring unloaded, which is what makes an
     * empty section list look like a valid answer.
     */
    public function testTheStorefrontConfigurationIsLoadedBeforeTheCallbackRuns(): void
    {
        $area = $this->createMock(Area::class);
        $area->expects($this->once())->method('load')->with(Area::PART_CONFIG);

        $areaList = $this->createMock(AreaList::class);
        $areaList->method('getArea')->with(Area::AREA_FRONTEND)->willReturn($area);

        $this->assertSame('ran', $this->scope($areaList)->run(static fn (): string => 'ran'));
    }

    public function testTheConfigScopeIsPutBackAfterwards(): void
    {
        $scope = $this->createMock(ScopeInterface::class);
        $scope->method('getCurrentScope')->willReturn('adminhtml');
        $scope->expects($this->exactly(2))
            ->method('setCurrentScope')
            ->willReturnCallback(static function (string $code): void {
                static $calls = 0;
                $expected = ++$calls === 1 ? Area::AREA_FRONTEND : 'adminhtml';

                self::assertSame($expected, $code);
            });

        $this->scope(null, $scope)->run(static fn (): string => 'ran');
    }

    public function testTheScopeIsPutBackEvenWhenTheCallbackThrows(): void
    {
        $scope = $this->createMock(ScopeInterface::class);
        $scope->method('getCurrentScope')->willReturn('adminhtml');
        $scope->expects($this->exactly(2))->method('setCurrentScope');

        $this->expectException(RuntimeException::class);

        $this->scope(null, $scope)->run(static function (): void {
            throw new RuntimeException('the callback failed');
        });
    }

    private function scope(?AreaList $areaList = null, ?ScopeInterface $scope = null): FrontendScope
    {
        if ($areaList === null) {
            $areaList = $this->createMock(AreaList::class);
            $areaList->method('getArea')->willReturn($this->createMock(Area::class));
        }

        $state = $this->createMock(State::class);
        $state->method('emulateAreaCode')->willReturnCallback(
            static fn (string $area, callable $callback): mixed => $callback()
        );

        return new FrontendScope($areaList, $state, $scope ?? $this->createMock(ScopeInterface::class));
    }
}
