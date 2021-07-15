<?php

namespace Drupal\views\Tests;

use Drupal\Core\Config\FileStorage;

/**
 * Provides tests view data and the base test schema with sample data records.
 *
 * The methods will be used by both views test base classes.
 *
 * @see \Drupal\Tests\views\Kernel\ViewsKernelTestBase.
 * @see \Drupal\views\Tests\ViewTestBase.
 */
class ViewTestData {

  /**
   * Create test views from config.
   *
   * @param string $class
   *   The name of the test class. Installs the listed test views *in order*.
   * @param array $modules
   *   The module directories to look in for test views.
   */
  public static function createTestViews($class, array $modules) {
    $views = [];
    while ($class) {
      if (property_exists($class, 'testViews')) {
        $views = array_merge($views, $class::$testViews);
      }
      $class = get_parent_class($class);
    }
    if (!empty($views)) {
      $storage = \Drupal::entityTypeManager()->getStorage('view');
      $module_handler = \Drupal::moduleHandler();
      foreach ($modules as $module) {
        $config_dir = \Drupal::service('extension.list.module')->getPath($module) . '/test_views';
        if (!is_dir($config_dir) || !$module_handler->moduleExists($module)) {
          continue;
        }

        $file_storage = new FileStorage($config_dir);
        $available_views = $file_storage->listAll('views.view.');
        foreach ($views as $id) {
          $config_name = 'views.view.' . $id;
          if (in_array($config_name, $available_views)) {
            $storage
              ->create($file_storage->read($config_name))
              ->save();
          }
        }
      }
    }

    // Rebuild the router once.
    \Drupal::service('router.builder')->rebuild();
  }

  /**
   * Returns the schema definition.
   *
   * @internal
   */
  public static function schemaDefinition() {
    $schema['views_test_data'] = [
      'description' => 'Basic test table for Views tests.',
      'fields' => [
        'id' => [
          'type' => 'serial',
          'unsigned' => TRUE,
          'not null' => TRUE,
        ],
        'name' => [
          'description' => "A person's name",
          'type' => 'varchar_ascii',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
        ],
        'age' => [
          'description' => "The person's age",
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
        ],
        'job' => [
          'description' => "The person's job",
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
          'default' => 'Undefined',
        ],
        'created' => [
          'description' => "The creation date of this record",
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
        ],
        'status' => [
          'description' => "The status of this record",
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
        ],
      ],
      'primary key' => ['id'],
      'unique keys' => [
        'name' => ['name'],
      ],
      'indexes' => [
        'ages' => ['age'],
      ],
    ];
    return $schema;
  }

  /**
   * Returns the views data definition.
   */
  public static function viewsData() {
    // Declaration of the base table.
    $data['views_test_data']['table'] = [
      'group' => 'Views test',
      'base' => [
        'field' => 'id',
        'title' => 'Views test data',
        'help' => 'Users who have created accounts on your site.',
      ],
    ];

    // Declaration of fields.
    $data['views_test_data']['id'] = [
      'title' => 'ID',
      'help' => 'The test data ID',
      'field' => [
        'id' => 'numeric',
      ],
      'argument' => [
        'id' => 'numeric',
      ],
      'filter' => [
        'id' => 'numeric',
      ],
      'sort' => [
        'id' => 'standard',
      ],
    ];
    $data['views_test_data']['name'] = [
      'title' => 'Name',
      'help' => 'The name of the person',
      'field' => [
        'id' => 'standard',
      ],
      'argument' => [
        'id' => 'string',
      ],
      'filter' => [
        'id' => 'string',
      ],
      'sort' => [
        'id' => 'standard',
      ],
    ];
    $data['views_test_data']['age'] = [
      'title' => 'Age',
      'help' => 'The age of the person',
      'field' => [
        'id' => 'numeric',
      ],
      'argument' => [
        'id' => 'numeric',
      ],
      'filter' => [
        'id' => 'numeric',
      ],
      'sort' => [
        'id' => 'standard',
      ],
    ];
    $data['views_test_data']['job'] = [
      'title' => 'Job',
      'help' => 'The job of the person',
      'field' => [
        'id' => 'standard',
      ],
      'argument' => [
        'id' => 'string',
      ],
      'filter' => [
        'id' => 'string',
      ],
      'sort' => [
        'id' => 'standard',
      ],
    ];
    $data['views_test_data']['created'] = [
      'title' => 'Created',
      'help' => 'The creation date of this record',
      'field' => [
        'id' => 'date',
      ],
      'argument' => [
        'id' => 'date',
      ],
      'filter' => [
        'id' => 'date',
      ],
      'sort' => [
        'id' => 'date',
      ],
    ];
    $data['views_test_data']['status'] = [
      'title' => 'Status',
      'help' => 'The status of this record',
      'field' => [
        'id' => 'boolean',
      ],
      'filter' => [
        'id' => 'boolean',
      ],
      'sort' => [
        'id' => 'standard',
      ],
    ];
    return $data;
  }

  /**
   * Returns a very simple test dataset.
   */
  public static function dataSet() {
    return [
      [
        'name' => 'John',
        'age' => 25,
        'job' => 'Singer',
        'created' => gmmktime(0, 0, 0, 1, 1, 2000),
        'status' => 1,
      ],
      [
        'name' => 'George',
        'age' => 27,
        'job' => 'Singer',
        'created' => gmmktime(0, 0, 0, 1, 2, 2000),
        'status' => 0,
      ],
      [
        'name' => 'Ringo',
        'age' => 28,
        'job' => 'Drummer',
        'created' => gmmktime(6, 30, 30, 1, 1, 2000),
        'status' => 1,
      ],
      [
        'name' => 'Paul',
        'age' => 26,
        'job' => 'Songwriter',
        'created' => gmmktime(6, 0, 0, 1, 1, 2000),
        'status' => 0,
      ],
      [
        'name' => 'Meredith',
        'age' => 30,
        'job' => 'Speaker',
        'created' => gmmktime(6, 30, 10, 1, 1, 2000),
        'status' => 1,
      ],
    ];
  }

}
