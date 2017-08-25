<?php

namespace Drupal\Tests\system\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Ensures that the automated cron module is installed on update.
 *
 * @group Update
 */
class AutomatedCronUpdateWithAutomatedCronTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../tests/fixtures/update/drupal-8.bare.standard.php.gz',
    ];
  }

  /**
   * Ensures that automated cron module isn installed and the config migrated.
   */
  public function testUpdate() {
    $this->runUpdates();

    $module_data = \Drupal::config('core.extension')->get('module');
    $this->assertTrue(isset($module_data['automated_cron']), 'The automated cron module was installed.');
  }

}
