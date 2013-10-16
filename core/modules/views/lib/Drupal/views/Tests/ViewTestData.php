<?php

/**
 * @file
 * Contains \Drupal\views\Tests\ViewTestData.
 */

namespace Drupal\views\Tests;

use Drupal\Core\Config\FileStorage;

/**
 * Provides tests view data and the base test schema with sample data records.
 *
 * The methods will be used by both views test base classes.
 *
 * @see \Drupal\views\Tests\ViewUnitTestBase.
 * @see \Drupal\views\Tests\ViewTestBase.
 */
class ViewTestData {

  /**
   * Create test views from config.
   *
   * @param string $class
   *   The name of the test class.
   * @param array $modules
   *   The module directories to look in for test views.
   */
  public static function createTestViews($class, array $modules) {
    $views = array();
    while ($class) {
      if (property_exists($class, 'testViews')) {
        $views = array_merge($views, $class::$testViews);
      }
      $class = get_parent_class($class);
    }
    if (!empty($views)) {
      $storage_controller = \Drupal::entityManager()->getStorageController('view');
      $module_handler = \Drupal::moduleHandler();
      foreach ($modules as $module) {
        $config_dir = drupal_get_path('module', $module) . '/test_views';
        if (!is_dir($config_dir) || !$module_handler->moduleExists($module)) {
          continue;
        }

        $file_storage = new FileStorage($config_dir);
        foreach ($file_storage->listAll('views.view.') as $config_name) {
          $id = str_replace('views.view.', '', $config_name);
          if (in_array($id, $views)) {
            $storage_controller
              ->create($file_storage->read($config_name))
              ->save();
          }
        }
      }
    }
  }

  /**
   * Returns the schema definition.
   */
  public static function schemaDefinition() {
    $schema['views_test_data'] = array(
      'description' => 'Basic test table for Views tests.',
      'fields' => array(
        'id' => array(
          'type' => 'serial',
          'unsigned' => TRUE,
          'not null' => TRUE,
        ),
        'name' => array(
          'description' => "A person's name",
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
        ),
        'age' => array(
          'description' => "The person's age",
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0),
        'job' => array(
          'description' => "The person's job",
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
          'default' => 'Undefined',
        ),
        'created' => array(
          'description' => "The creation date of this record",
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
        ),
        'status' => array(
          'description' => "The status of this record",
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
        ),
      ),
      'primary key' => array('id'),
      'unique keys' => array(
        'name' => array('name')
      ),
      'indexes' => array(
        'ages' => array('age'),
      ),
    );
    return $schema;
  }

  /**
   * Returns the views data definition.
   */
  public static function viewsData() {
    // Declaration of the base table.
    $data['views_test_data']['table'] = array(
      'group' => 'Views test',
      'base' => array(
        'field' => 'id',
        'title' => 'Views test data',
        'help' => 'Users who have created accounts on your site.',
      ),
    );

    // Declaration of fields.
    $data['views_test_data']['id'] = array(
      'title' => 'ID',
      'help' => 'The test data ID',
      'field' => array(
        'id' => 'numeric',
      ),
      'argument' => array(
        'id' => 'numeric',
      ),
      'filter' => array(
        'id' => 'numeric',
      ),
      'sort' => array(
        'id' => 'standard',
      ),
    );
    $data['views_test_data']['name'] = array(
      'title' => 'Name',
      'help' => 'The name of the person',
      'field' => array(
        'id' => 'standard',
      ),
      'argument' => array(
        'id' => 'string',
      ),
      'filter' => array(
        'id' => 'string',
      ),
      'sort' => array(
        'id' => 'standard',
      ),
    );
    $data['views_test_data']['age'] = array(
      'title' => 'Age',
      'help' => 'The age of the person',
      'field' => array(
        'id' => 'numeric',
      ),
      'argument' => array(
        'id' => 'numeric',
      ),
      'filter' => array(
        'id' => 'numeric',
      ),
      'sort' => array(
        'id' => 'standard',
      ),
    );
    $data['views_test_data']['job'] = array(
      'title' => 'Job',
      'help' => 'The job of the person',
      'field' => array(
        'id' => 'standard',
      ),
      'argument' => array(
        'id' => 'string',
      ),
      'filter' => array(
        'id' => 'string',
      ),
      'sort' => array(
        'id' => 'standard',
      ),
    );
    $data['views_test_data']['created'] = array(
      'title' => 'Created',
      'help' => 'The creation date of this record',
      'field' => array(
        'id' => 'date',
      ),
      'argument' => array(
        'id' => 'date',
      ),
      'filter' => array(
        'id' => 'date',
      ),
      'sort' => array(
        'id' => 'date',
      ),
    );
    $data['views_test_data']['status'] = array(
      'title' => 'Status',
      'help' => 'The status of this record',
      'field' => array(
        'id' => 'boolean',
      ),
      'filter' => array(
        'id' => 'boolean',
      ),
      'sort' => array(
        'id' => 'standard',
      ),
    );
    return $data;
  }

  /**
   * Returns a very simple test dataset.
   */
  public static function dataSet() {
    return array(
      array(
        'name' => 'John',
        'age' => 25,
        'job' => 'Singer',
        'created' => gmmktime(0, 0, 0, 1, 1, 2000),
        'status' => 1,
      ),
      array(
        'name' => 'George',
        'age' => 27,
        'job' => 'Singer',
        'created' => gmmktime(0, 0, 0, 1, 2, 2000),
        'status' => 0,
      ),
      array(
        'name' => 'Ringo',
        'age' => 28,
        'job' => 'Drummer',
        'created' => gmmktime(6, 30, 30, 1, 1, 2000),
        'status' => 1,
      ),
      array(
        'name' => 'Paul',
        'age' => 26,
        'job' => 'Songwriter',
        'created' => gmmktime(6, 0, 0, 1, 1, 2000),
        'status' => 0,
      ),
      array(
        'name' => 'Meredith',
        'age' => 30,
        'job' => 'Speaker',
        'created' => gmmktime(6, 30, 10, 1, 1, 2000),
        'status' => 1,
      ),
    );
  }

}

