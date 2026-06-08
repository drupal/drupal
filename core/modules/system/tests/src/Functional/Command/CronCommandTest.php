<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\Command;

use Drupal\system\Command\CronCommand;
use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Tests cron runs as a console command.
 */
#[Group('system')]
#[RunTestsInSeparateProcesses]
#[CoversClass(CronCommand::class)]
class CronCommandTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'common_test',
    'common_test_cron_helper',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests cron runs.
   */
  public function testCronRun(): void {
    $command = new CronCommand($this->container->get('cron'));
    $tester = new CommandTester($command);
    $code = $tester->execute([]);
    $this->assertStringContainsString('Cron ran successfully.', $tester->getDisplay());
    $this->assertEquals(Command::SUCCESS, $code);
  }

  /**
   * Make sure exceptions thrown on hook_cron() don't affect other modules.
   */
  public function testCronExceptions(): void {
    \Drupal::state()->delete('common_test.cron');
    $command = new CronCommand($this->container->get('cron'));
    $tester = new CommandTester($command);
    $code = $tester->execute([]);
    $this->assertStringContainsString('Cron ran successfully.', $tester->getDisplay());
    $this->assertEquals(Command::SUCCESS, $code);
  }

}
