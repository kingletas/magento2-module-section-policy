<?php
/**
 * @package   Commerce_SectionPolicy
 * @copyright Copyright (c) the Commerce modules authors
 * @license   OSL-3.0 https://opensource.org/licenses/OSL-3.0
 */

declare(strict_types=1);

namespace Commerce\SectionPolicy\Model\Policy;

/**
 * The action-to-sections map exactly as the browser will receive it.
 */
class InvalidationMap
{
    /**
     * The marker `sections.xml` uses for "everything", which the browser expands to every section.
     */
    public const WILDCARD = '*';

    /**
     * @param array<string, string[]> $actions
     */
    public function __construct(private readonly array $actions = [])
    {
    }

    /**
     * @return string[]
     */
    public function actions(): array
    {
        return array_keys($this->actions);
    }

    public function has(string $action): bool
    {
        return isset($this->actions[$action]);
    }

    /**
     * @return string[]
     */
    public function sectionsFor(string $action): array
    {
        return $this->actions[$action] ?? [];
    }

    /**
     * Whether this action tells the browser to throw every section away.
     */
    public function invalidatesEverything(string $action): bool
    {
        return in_array(self::WILDCARD, $this->sectionsFor($action), true);
    }

    /**
     * The actions that invalidate every section, which are the ones worth reading.
     *
     * @return string[]
     */
    public function wildcardActions(): array
    {
        return array_values(array_filter(
            $this->actions(),
            fn (string $action): bool => $this->invalidatesEverything($action)
        ));
    }

    /**
     * @param string[] $sections
     */
    public function withSectionsFor(string $action, array $sections): self
    {
        $actions = $this->actions;
        $actions[$action] = array_values(array_unique($sections));

        return new self($actions);
    }

    /**
     * @return array<string, string[]>
     */
    public function toArray(): array
    {
        return $this->actions;
    }
}
