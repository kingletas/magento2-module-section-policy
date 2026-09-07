<?php
/**
 * @package   Commerce_SectionPolicy
 * @copyright Copyright (c) the Commerce modules authors
 * @license   OSL-3.0 https://opensource.org/licenses/OSL-3.0
 */

declare(strict_types=1);

namespace Commerce\SectionPolicy\Test\Unit\Console\Command;

use Commerce\SectionPolicy\Api\SectionInventoryInterface;
use Commerce\SectionPolicy\Api\SectionPolicyInterface;
use Commerce\SectionPolicy\Console\Command\ReportCommand;
use Commerce\SectionPolicy\Model\Area\FrontendScope;
use Commerce\SectionPolicy\Model\Config;
use Commerce\SectionPolicy\Model\Inventory\PayloadSampler;
use Commerce\SectionPolicy\Model\Inventory\SectionMeasurement;
use Commerce\SectionPolicy\Model\Policy\InvalidationMap;
use Commerce\SectionPolicy\Model\Policy\RuleOutcome;
use Commerce\SectionPolicy\Model\Policy\RuleVerdict;
use Magento\Framework\Config\DataInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class ReportCommandTest extends TestCase
{
    private const SECTIONS = ['cart', 'customer', 'directory-data'];
    private const MAP = [
        'customer/account/logout' => ['*'],
        'checkout/cart/add' => ['cart'],
    ];

    public function testACleanStoreReportsTheWideActionsAndSucceeds(): void
    {
        $tester = $this->report([new RuleVerdict('customer/account/logout', RuleOutcome::Applies, 'keeps most')]);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertStringContainsString('customer/account/logout', $tester->getDisplay());
        $this->assertStringContainsString('every section', $tester->getDisplay());
    }

    public function testOnlyWideActionsAreListedUnlessAllIsAsked(): void
    {
        $this->assertStringNotContainsString('checkout/cart/add', $this->report([])->getDisplay());
        $this->assertStringContainsString('checkout/cart/add', $this->report([], ['--all' => true])->getDisplay());
    }

    public function testARuleThatCannotBeAppliedFailsTheRun(): void
    {
        $tester = $this->report([
            new RuleVerdict('customer/account/logout', RuleOutcome::Misconfigured, 'not a registered section'),
        ]);

        $this->assertSame(Command::FAILURE, $tester->getStatusCode());
        $this->assertStringContainsString('cannot be applied as written', $tester->getDisplay());
    }

    /**
     * Zero sections means the storefront wiring never loaded, and reporting that as clean is the
     * one failure a checker must not have.
     */
    public function testAnEmptyInventoryFailsRatherThanReadingAsClean(): void
    {
        $tester = $this->report([], [], []);

        $this->assertSame(Command::FAILURE, $tester->getStatusCode());
        $this->assertStringContainsString('No sections are registered', $tester->getDisplay());
    }

    public function testTheSwitchBeingOffOnlyFailsWhenTheGateWasAskedFor(): void
    {
        $this->assertSame(Command::SUCCESS, $this->report([], [], self::SECTIONS, false)->getStatusCode());
        $this->assertSame(
            Command::FAILURE,
            $this->report([], ['--require-enabled' => true], self::SECTIONS, false)->getStatusCode()
        );
    }

    public function testMeasuringIsOptOutByDefaultAndPrintsSharesWhenAsked(): void
    {
        $this->assertStringNotContainsString('anonymous visitor', $this->report([])->getDisplay());

        $display = $this->report([], ['--measure' => true])->getDisplay();

        $this->assertStringContainsString('directory-data', $display);
        $this->assertStringContainsString('anonymous visitor', $display);
    }

    /**
     * @param RuleVerdict[] $verdicts
     * @param array<string, mixed> $input
     * @param string[] $sections
     */
    private function report(
        array $verdicts,
        array $input = [],
        array $sections = self::SECTIONS,
        bool $enabled = true
    ): CommandTester {
        $policy = $this->createMock(SectionPolicyInterface::class);
        $policy->method('apply')->willReturnCallback(
            static fn (InvalidationMap $map): InvalidationMap => $map
        );
        $policy->method('verdicts')->willReturn($verdicts);

        $inventory = $this->createMock(SectionInventoryInterface::class);
        $inventory->method('names')->willReturn($sections);

        $sampler = $this->createMock(PayloadSampler::class);
        $sampler->method('sample')->willReturn([new SectionMeasurement('directory-data', 59506)]);
        $sampler->method('total')->willReturn(59506);

        $frontend = $this->createMock(FrontendScope::class);
        $frontend->method('run')->willReturnCallback(static fn (callable $callback): mixed => $callback());

        $sectionConfig = $this->createMock(DataInterface::class);
        $sectionConfig->method('get')->willReturn(self::MAP);

        $config = $this->createMock(Config::class);
        $config->method('isEnabled')->willReturn($enabled);

        $tester = new CommandTester(
            new ReportCommand($policy, $inventory, $sampler, $frontend, $sectionConfig, $config, 'test:report')
        );
        $tester->execute($input);

        return $tester;
    }
}
