<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\Tests\UpdatePathTestTrait;

/**
 * Tests update of system.cron:logging.
 *
 * @group system
 * @covers \system_post_update_set_cron_logging_setting_to_boolean
 */
class CronLoggingUpdateTest extends UpdatePathTestBase {

  use UpdatePathTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      DRUPAL_ROOT . '/core/modules/system/tests/fixtures/update/drupal-9.4.0.bare.standard.php.gz',
    ];
  }

  /**
   * Tests update of system.cron:logging.
   */
  public function testUpdate(): void {
    $logging_before = $this->config('system.cron')->get('logging');
    $this->assertIsNotBool($logging_before);

    $this->runUpdates();

    $logging_after = $this->config('system.cron')->get('logging');
    $this->assertIsBool($logging_after);
  }

}
