<?php

namespace Drupal\Tests\views\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\views\Views;

/**
 * Tests the upgrade path for views multi-value base field data.
 *
 * @see views_update_8500()
 *
 * @group legacy
 */
class EntityViewsMultiValueBaseFieldDataUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.bare.standard.php.gz',
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.views-entity-views-data-2846614.php',
    ];
  }

  /**
   * Tests multi-value base field views data is updated correctly.
   */
  public function testUpdateMultiValueBaseFields() {
    $this->runUpdates();

    $view = Views::getView('test_user_multi_value');
    $display = $view->storage->get('display');

    // Check each handler type present in the configuration to make sure the
    // field got updated correctly.
    foreach (['fields', 'filters', 'arguments'] as $type) {
      $handler_config = $display['default']['display_options'][$type]['roles'];

      // The ID should remain unchanged. Otherwise the update handler could
      // overwrite a separate handler config.
      $this->assertEqual('roles', $handler_config['id']);
      // The field should be updated from 'roles' to the correct column name.
      $this->assertEqual('roles_target_id', $handler_config['field']);
      // Check the table is still correct.
      $this->assertEqual('user__roles', $handler_config['table']);

      // The plugin ID should be updated as well.
      $this->assertEqual($type === 'arguments' ? 'user__roles_rid' : 'user_roles', $handler_config['plugin_id']);
    }
  }

}
