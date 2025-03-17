<?php

declare(strict_types=1);

namespace Drupal\Tests\dblog\Kernel;

use Drupal\Core\Database\Database;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\dblog\Functional\FakeLogEntries;

/**
 * Generate events and verify dblog entries.
 *
 * @group dblog
 */
class DbLogTest extends KernelTestBase {

  use FakeLogEntries;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['dblog', 'system'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installSchema('dblog', ['watchdog']);
    $this->installConfig(['system']);
  }

  /**
   * Tests that cron correctly applies the database log row limit.
   */
  public function testDbLogCron(): void {
    $row_limit = 100;
    // Generate additional log entries.
    $this->generateLogEntries($row_limit + 10);
    // Verify that the database log row count exceeds the row limit.
    $count = Database::getConnection()->select('watchdog')->countQuery()->execute()->fetchField();
    $this->assertGreaterThan($row_limit, $count, "Dblog row count of $count exceeds row limit of $row_limit");

    // Get the number of enabled modules. Cron adds a log entry for each module.
    $implementation_count = 0;
    \Drupal::moduleHandler()->invokeAllWith(
      'cron',
      function (callable $hook, string $module) use (&$implementation_count) {
        $implementation_count++;
      }
    );

    $cron_detailed_count = $this->runCron();
    $expected_count = $implementation_count + 2;
    $this->assertEquals($expected_count, $cron_detailed_count, "Cron added $cron_detailed_count of $expected_count new log entries");

    // Test disabling of detailed cron logging.
    $this->config('system.cron')->set('logging', FALSE)->save();
    $cron_count = $this->runCron();
    $this->assertEquals(1, $cron_count, "Cron added $cron_count of 1 new log entries");
  }

  /**
   * Tests that only valid placeholders are stored in the variables column.
   */
  public function testInvalidPlaceholders(): void {
    \Drupal::logger('my_module')
      ->warning('Hello @string @array @object', [
        '@string' => '',
        '@array' => [],
        '@object' => new \stdClass(),
      ]);
    $variables = \Drupal::database()
      ->select('watchdog', 'w')
      ->fields('w', ['variables'])
      ->orderBy('wid', 'DESC')
      ->range(0, 1)
      ->execute()
      ->fetchField();
    $this->assertSame(serialize(['@string' => '']), $variables);
  }

  /**
   * Runs cron and returns number of new log entries.
   *
   * @return int
   *   Number of new watchdog entries.
   */
  private function runCron(): int {
    $connection = Database::getConnection();
    // Get last ID to compare against; log entries get deleted, so we can't
    // reliably add the number of newly created log entries to the current count
    // to measure number of log entries created by cron.
    $query = $connection->select('watchdog');
    $query->addExpression('MAX([wid])');
    $last_id = $query->execute()->fetchField();

    // Run a cron job.
    $this->container->get('cron')->run();

    // Get last ID after cron was run.
    $query = $connection->select('watchdog');
    $query->addExpression('MAX([wid])');
    $current_id = $query->execute()->fetchField();

    return $current_id - $last_id;
  }

}
