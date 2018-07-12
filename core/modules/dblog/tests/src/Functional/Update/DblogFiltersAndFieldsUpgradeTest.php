<?php

namespace Drupal\Tests\dblog\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\views\Views;
use Drupal\Core\Serialization\Yaml;

/**
 * Tests the upgrade path for views field and filter handlers.
 *
 * @see dblog_update_8400()
 *
 * @group Update
 * @group legacy
 */
class DblogFiltersAndFieldsUpgradeTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.bare.standard.php.gz',
      __DIR__ . '/../../../fixtures/update/dblog-2851293.php',
    ];
  }

  /**
   * Tests that field and filter handlers are updated properly.
   */
  public function testDblogUpgradePath() {

    $this->runUpdates();

    $view = Views::getView('dblog_2851293');
    $data = $view->storage->toArray();
    $fields = $data['display']['default']['display_options']['fields'];

    // The 'wid' and 'uid' fields should use the standard plugin now.
    $this->assertEqual('standard', $fields['wid']['plugin_id']);
    $this->assertEqual('standard', $fields['uid']['plugin_id']);

    $filters = $data['display']['default']['display_options']['filters'];
    // The 'type' filter should use the dblog_types plugin now.
    $this->assertEqual('dblog_types', $filters['type']['plugin_id']);

    // Now that the view had been converted, try the same approach but using
    // dblog_view_presave()
    $config_factory = \Drupal::configFactory();
    $config_view = $config_factory->getEditable('views.view.dblog_2851293');
    $config_view->setData(Yaml::decode(file_get_contents('core/modules/dblog/tests/modules/dblog_test_views/test_views/views.view.dblog_2851293.yml')));
    $config_view->save();

    // Make sure we have a not upgraded view.
    $view = Views::getView('dblog_2851293');
    $data = $view->storage->toArray();
    $fields = $data['display']['default']['display_options']['fields'];
    $filters = $data['display']['default']['display_options']['filters'];

    $this->assertEqual('numeric', $fields['wid']['plugin_id']);
    $this->assertEqual('numeric', $fields['uid']['plugin_id']);
    $this->assertEqual('in_operator', $filters['type']['plugin_id']);

    // Now save the view. This trigger dblog_view_presave().
    $view->save();

    // Finally check the same convertion proccess ran.
    $data = $view->storage->toArray();
    $fields = $data['display']['default']['display_options']['fields'];
    $filters = $data['display']['default']['display_options']['filters'];

    $this->assertEqual('standard', $fields['wid']['plugin_id']);
    $this->assertEqual('standard', $fields['uid']['plugin_id']);
    $this->assertEqual('dblog_types', $filters['type']['plugin_id']);
  }

}
