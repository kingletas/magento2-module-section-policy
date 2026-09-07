<?php
/**
 * @package   Commerce_SectionPolicy
 * @copyright Copyright (c) the Commerce modules authors
 * @license   OSL-3.0 https://opensource.org/licenses/OSL-3.0
 */

declare(strict_types=1);

namespace Commerce\SectionPolicy\Console\Command;

use Commerce\SectionPolicy\Api\SectionInventoryInterface;
use Commerce\SectionPolicy\Api\SectionPolicyInterface;
use Commerce\SectionPolicy\Model\Area\FrontendScope;
use Commerce\SectionPolicy\Model\Config;
use Commerce\SectionPolicy\Model\Inventory\PayloadSampler;
use Commerce\SectionPolicy\Model\Inventory\SectionMeasurement;
use Commerce\SectionPolicy\Model\Policy\InvalidationMap;
use Commerce\SectionPolicy\Model\Policy\PolicyReport;
use Commerce\SectionPolicy\Model\Policy\RuleVerdict;
use Magento\Framework\Config\DataInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Answers "what does each action throw away, and what does throwing it away cost".
 */
class ReportCommand extends Command
{
    private const OPTION_STORE = 'store';
    private const OPTION_MEASURE = 'measure';
    private const OPTION_ALL = 'all';
    private const OPTION_REQUIRE_ENABLED = 'require-enabled';

    public function __construct(
        private readonly SectionPolicyInterface $policy,
        private readonly SectionInventoryInterface $inventory,
        private readonly PayloadSampler $sampler,
        private readonly FrontendScope $frontend,
        private readonly DataInterface $sectionConfig,
        private readonly Config $config,
        ?string $name = null
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->setDescription(
            'Show what each action invalidates in customer data, and fail on a rule that cannot work.'
        )
            ->addOption(self::OPTION_STORE, 's', InputOption::VALUE_REQUIRED, 'Store id to read settings for.')
            ->addOption(
                self::OPTION_MEASURE,
                'm',
                InputOption::VALUE_NONE,
                'Also produce every section once and report its size. Runs the section sources.'
            )
            ->addOption(self::OPTION_ALL, 'a', InputOption::VALUE_NONE, 'List every action, not only the wide ones.')
            ->addOption(
                self::OPTION_REQUIRE_ENABLED,
                null,
                InputOption::VALUE_NONE,
                'Treat a policy that is not in force as a failure, for use as a CI gate.'
            );

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $storeId = $this->intOption($input, self::OPTION_STORE);

        $report = $this->frontend->run(fn (): PolicyReport => $this->collect($storeId));

        $this->printActions($output, $report, (bool) $input->getOption(self::OPTION_ALL));
        $this->printRules($output, $report->verdicts(), $storeId);

        if ($input->getOption(self::OPTION_MEASURE)) {
            $this->printPayload($output);
        }

        return $this->verdict($output, $input, $report, $storeId);
    }

    private function collect(?int $storeId): PolicyReport
    {
        /** @var array<string, string[]> $sections */
        $sections = $this->sectionConfig->get('sections') ?? [];
        $map = new InvalidationMap($sections);

        return new PolicyReport(
            $map,
            $this->policy->apply($map, $storeId),
            $this->policy->verdicts($map, $storeId),
            $this->inventory->names()
        );
    }

    private function printActions(OutputInterface $output, PolicyReport $report, bool $listAll): void
    {
        $map = $report->declared();
        $applied = $report->applied();
        $actions = $listAll ? $map->actions() : $map->wildcardActions();
        $total = count($report->sections());

        $output->writeln('');
        $output->writeln(sprintf(
            '  <info>%d</info> action(s) declared, <info>%d</info> of them invalidate every section, '
            . '<info>%d</info> section(s) registered',
            count($map->actions()),
            count($map->wildcardActions()),
            $total
        ));
        $output->writeln('');

        if ($actions === []) {
            $output->writeln('  <comment>No action invalidates everything. Nothing here needs narrowing.</comment>');
            $output->writeln('');

            return;
        }

        $table = new Table($output);
        $table->setHeaders(['Action', 'Invalidates now', 'After policy']);

        foreach ($actions as $action) {
            $before = $map->invalidatesEverything($action) ? $total : count($map->sectionsFor($action));
            $after = $applied->invalidatesEverything($action) ? $total : count($applied->sectionsFor($action));

            $table->addRow([
                $action,
                $this->describeCount($before, $map->invalidatesEverything($action)),
                $before === $after ? '<comment>unchanged</comment>' : sprintf('<info>%d section(s)</info>', $after),
            ]);
        }

        $table->render();
        $output->writeln('');
    }

    /**
     * @param RuleVerdict[] $verdicts
     */
    private function printRules(OutputInterface $output, array $verdicts, ?int $storeId): void
    {
        if ($verdicts === []) {
            $output->writeln('  <comment>No rules are declared. See the rules argument in di.xml.</comment>');
            $output->writeln('');

            return;
        }

        $table = new Table($output);
        $table->setHeaders(['Rule', 'Outcome', 'Why']);

        foreach ($verdicts as $verdict) {
            $table->addRow([
                $verdict->action(),
                $this->styleOutcome($verdict),
                $verdict->reason(),
            ]);
        }

        $table->render();
        $output->writeln('');
        $output->writeln(sprintf(
            '  policy is <info>%s</info>',
            $this->config->isEnabled($storeId) ? 'in force' : 'switched off'
        ));
        $output->writeln('');
    }

    private function printPayload(OutputInterface $output): void
    {
        /** @var SectionMeasurement[] $measurements */
        $measurements = $this->frontend->run(fn (): array => $this->sampler->sample());
        $total = $this->sampler->total($measurements);

        $table = new Table($output);
        $table->setHeaders(['Section', 'Bytes', 'Share']);

        foreach ($measurements as $measurement) {
            $table->addRow([
                $measurement->section(),
                $measurement->measured() ? (string) $measurement->bytes() : '<comment>not measurable here</comment>',
                $this->share($measurement, $total),
            ]);
        }

        $table->render();
        $output->writeln('');
        $output->writeln(sprintf('  <info>%d</info> bytes for an anonymous visitor, every section', $total));
        $output->writeln('');
    }

    private function verdict(
        OutputInterface $output,
        InputInterface $input,
        PolicyReport $report,
        ?int $storeId
    ): int {
        if ($report->inventoryIsEmpty()) {
            $output->writeln('  <error>No sections are registered, which cannot be true on a working store.</error>');

            return Command::FAILURE;
        }

        $broken = $report->broken();

        if ($broken !== []) {
            $output->writeln(sprintf('  <error>%d rule(s) cannot be applied as written.</error>', count($broken)));

            return Command::FAILURE;
        }

        if ($input->getOption(self::OPTION_REQUIRE_ENABLED) && !$this->config->isEnabled($storeId)) {
            $output->writeln('  <error>The policy is switched off and --require-enabled was given.</error>');

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function describeCount(int $count, bool $wildcard): string
    {
        return $wildcard
            ? sprintf('<error>every section (%d)</error>', $count)
            : sprintf('%d section(s)', $count);
    }

    private function styleOutcome(RuleVerdict $verdict): string
    {
        if ($verdict->isMisconfigured()) {
            return '<error>' . $verdict->outcome()->value . '</error>';
        }

        return $verdict->applies()
            ? '<info>' . $verdict->outcome()->value . '</info>'
            : '<comment>' . $verdict->outcome()->value . '</comment>';
    }

    private function share(SectionMeasurement $measurement, int $total): string
    {
        if (!$measurement->measured() || $total === 0) {
            return '';
        }

        return sprintf('%d%%', (int) round(($measurement->bytes() ?? 0) * 100 / $total));
    }

    private function intOption(InputInterface $input, string $name): ?int
    {
        $value = $input->getOption($name);

        return is_numeric($value) ? (int) $value : null;
    }
}
