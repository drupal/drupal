<?php

namespace Drupal\Tests\dblog\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\views\Views;

/**
 * Test the upgrade path of changing the emtpy text area for watchdog view.
 *
 * @see dblog_update_8600()
 *
 * @group Update
 * @group legacy
 */
class DblogNoLogsAvailableUpgradeTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.4.0.bare.standard.php.gz',
    ];
  }

  /**
   * Tests that no logs available text is now using a custom area.
   */
  public function testDblogUpgradePath() {

    $this->runUpdates();

    $view = Views::getView('watchdog');
    $data = $view->storage->toArray();
    $area = $data['display']['default']['display_options']['empty']['area'];

    $this->assertEqual('text_custom', $area['plugin_id']);
    $this->assertEqual('area_text_custom', $area['field']);
    $this->assertEqual('No log messages available.', $area['content']);
  }

}
