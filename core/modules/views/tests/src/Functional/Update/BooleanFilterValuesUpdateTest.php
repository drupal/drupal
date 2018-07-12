<?php

namespace Drupal\Tests\views\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\views\Entity\View;

/**
 * Tests the upgrade path for boolean field values.
 *
 * @see views_post_update_boolean_filter_values()
 *
 * @group Update
 * @group legacy
 */
class BooleanFilterValuesUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.bare.standard.php.gz',
      __DIR__ . '/../../../fixtures/update/boolean-filter-values.php',
    ];
  }

  /**
   * Tests that boolean filter values are updated properly.
   */
  public function testViewsPostUpdateBooleanFilterValues() {
    $this->runUpdates();

    // Load and initialize our test view.
    $view = View::load('test_boolean_filter_values');
    $data = $view->toArray();
    // Check that the field is using the expected string value.
    $this->assertIdentical('1', $data['display']['default']['display_options']['filters']['status']['value']);
  }

}
