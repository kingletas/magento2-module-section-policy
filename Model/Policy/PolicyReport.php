<?php
/**
 * @package   Commerce_SectionPolicy
 * @copyright Copyright (c) the Commerce modules authors
 * @license   OSL-3.0 https://opensource.org/licenses/OSL-3.0
 */

declare(strict_types=1);

namespace Commerce\SectionPolicy\Model\Policy;

/**
 * Everything the report needs, gathered once inside the storefront's wiring.
 */
class PolicyReport
{
    /**
     * @param RuleVerdict[] $verdicts
     * @param string[] $sections
     */
    public function __construct(
        private readonly InvalidationMap $declared,
        private readonly InvalidationMap $applied,
        private readonly array $verdicts,
        private readonly array $sections
    ) {
    }

    /**
     * The map as the installed modules declare it.
     */
    public function declared(): InvalidationMap
    {
        return $this->declared;
    }

    /**
     * The map as the browser will receive it.
     */
    public function applied(): InvalidationMap
    {
        return $this->applied;
    }

    /**
     * @return RuleVerdict[]
     */
    public function verdicts(): array
    {
        return $this->verdicts;
    }

    /**
     * @return string[]
     */
    public function sections(): array
    {
        return $this->sections;
    }

    /**
     * Rules that cannot be applied as written, which is what fails the run.
     *
     * @return RuleVerdict[]
     */
    public function broken(): array
    {
        return array_values(array_filter(
            $this->verdicts,
            static fn (RuleVerdict $verdict): bool => $verdict->isMisconfigured()
        ));
    }

    /**
     * No sections at all means the storefront wiring never loaded, not that the store has none.
     */
    public function inventoryIsEmpty(): bool
    {
        return $this->sections === [];
    }
}
