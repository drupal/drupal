<?php

namespace Drupal\Tests\dblog\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Ensures that update hook that creates the watchdog view ran sucessfully.
 *
 * @group Update
 * @group legacy
 */
class DblogRecentLogsUsingViewsUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.bare.standard.php.gz',
    ];
  }

  /**
   * Ensures that update hook is run for dblog module.
   */
  public function testUpdate() {
    // Make sure the watchog view doesn't exist before the updates.
    $view = \Drupal::entityTypeManager()->getStorage('view')->load('watchdog');
    $this->assertNull($view);

    $this->runUpdates();

    // Ensure the watchdog view is present after run updates.
    $view = \Drupal::entityTypeManager()->getStorage('view')->load('watchdog');
    $displays = $view->get('display');

    $this->assertIdentical($displays['page']['display_options']['path'], 'admin/reports/dblog', 'Recent logs message view exists.');
  }

}
