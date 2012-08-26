<?php

/**
 * @file
 * Definition of Drupal\views\Tests\ViewsSchemaTestBase.
 */

namespace Drupal\views\Tests;

/**
 * Provides access to the test module schema and Views data.
 */
abstract class ViewsSchemaTestBase extends ViewsSqlTest {

  protected function setUp() {
    parent::setUp();

    // Define the schema and views data variable before enabling the test module.
    variable_set('views_test_schema', $this->schemaDefinition());
    variable_set('views_test_views_data', $this->viewsData());

    module_enable(array('views_test'));
    $this->resetAll();

    // Load the test dataset.
    $data_set = $this->dataSet();
    $query = db_insert('views_test')
      ->fields(array_keys($data_set[0]));
    foreach ($data_set as $record) {
      $query->values($record);
    }
    $query->execute();
    $this->checkPermissions(array(), TRUE);
  }

}
