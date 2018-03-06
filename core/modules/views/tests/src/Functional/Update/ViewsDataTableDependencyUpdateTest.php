<?php

namespace Drupal\Tests\views\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\views\Views;

/**
 * Tests the upgrade path for views data table provider dependencies.
 *
 * @see views_post_update_views_data_table_dependencies()
 *
 * @group Update
 */
class ViewsDataTableDependencyUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.4.0.bare.standard.php.gz',
      // This creates a view called test_table_dependency_update which has no
      // dependencies.
      __DIR__ . '/../../../fixtures/update/views-data-table-dependency.php',
    ];
  }

  /**
   * Tests that dependencies are correct after update.
   */
  public function testPostUpdate() {
    $this->runUpdates();

    // Load and initialize our test view.
    $view = Views::getView('test_table_dependency_update');
    $this->assertEquals(['module' => ['views_test_data']], $view->getDependencies());
  }

}
