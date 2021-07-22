<?php

namespace Drupal\Tests\views\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\views\Entity\View;

/**
 * Tests the upgrade path for setting defaults on empty group columns.
 *
 * @see views_post_update_empty_entity_field_group_column()
 *
 * @group Update
 * @group legacy
 */
class EmptyFieldGroupColumnUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.8.0.bare.standard.php.gz',
      __DIR__ . '/../../../fixtures/update/empty-field-group-column.php',
    ];
  }

  /**
   * Tests that empty field group column values are updated properly.
   */
  public function testViewsPostUpdateEmptyFieldGroupColumn() {
    $this->runUpdates();

    // Load and initialize our test view.
    $view = View::load('group_column_post_update');
    $data = $view->toArray();
    // Check that the field is using the expected default value.
    $this->assertSame('target_id', $data['display']['default']['display_options']['fields']['field_image']['group_column']);
    // Ensure existing values are not changed.
    $this->assertSame('entity_id', $data['display']['default']['display_options']['fields']['field_image2']['group_column']);
  }

}
