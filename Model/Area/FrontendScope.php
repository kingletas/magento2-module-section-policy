<?php
/**
 * @package   Commerce_SectionPolicy
 * @copyright Copyright (c) the Commerce modules authors
 * @license   OSL-3.0 https://opensource.org/licenses/OSL-3.0
 */

declare(strict_types=1);

namespace Commerce\SectionPolicy\Model\Area;

use Magento\Framework\App\Area;
use Magento\Framework\App\AreaList;
use Magento\Framework\App\State;
use Magento\Framework\Config\ScopeInterface;

/**
 * Runs a callback with the storefront's own wiring in place.
 */
class FrontendScope
{
    public function __construct(
        private readonly AreaList $areaList,
        private readonly State $state,
        private readonly ScopeInterface $scope
    ) {
    }

    /**
     * The area code alone is not enough - the section list is wired in the storefront's `di.xml`, so a
     * command that only switches the code reads an empty inventory and reports it as a clean result.
     *
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    public function run(callable $callback): mixed
    {
        $this->areaList->getArea(Area::AREA_FRONTEND)->load(Area::PART_CONFIG);

        $previous = $this->scope->getCurrentScope();

        try {
            return $this->state->emulateAreaCode(Area::AREA_FRONTEND, function () use ($callback): mixed {
                $this->scope->setCurrentScope(Area::AREA_FRONTEND);

                return $callback();
            });
        } finally {
            $this->scope->setCurrentScope($previous);
        }
    }
}
