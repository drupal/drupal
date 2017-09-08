<?php

namespace Drupal\Tests\views\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\views\Entity\View;

/**
 * Tests the upgrade path for views field handlers.
 *
 * @see views_post_update_cleanup_duplicate_views_data()
 *
 * @group Update
 */
class FieldHandlersUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.bare.standard.php.gz',
      __DIR__ . '/../../../fixtures/update/duplicate-field-handler.php',
    ];
  }

  /**
   * Tests that field handlers are updated properly.
   */
  public function testViewsUpdate8004() {
    $this->runUpdates();

    // Load and initialize our test view.
    $view = View::load('test_duplicate_field_handlers');
    $data = $view->toArray();
    // Check that the field is using the expected base table.
    $this->assertEqual('node_field_data', $data['display']['default']['display_options']['fields']['nid']['table']);
    $this->assertEqual('node_field_data', $data['display']['default']['display_options']['filters']['type']['table']);
    $this->assertEqual('node_field_data', $data['display']['default']['display_options']['sorts']['vid']['table']);
    $this->assertEqual('node_field_data', $data['display']['default']['display_options']['arguments']['nid']['table']);
  }

}
