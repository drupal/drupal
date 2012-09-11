<?php

/**
 * @file
 * Definition of Drupal\views\Tests\UpgradeTestCase.
 */

namespace Drupal\views\Tests;

/**
 * Tests the upgrade path of all conversions.
 *
 * You can find all conversions by searching for "moved to".
 */
class UpgradeTestCase extends ViewTestBase {

  /**
   * Modules to enable.
   *
   * To import a view the user needs use PHP for settings rights, so enable php
   * module.
   *
   * @var array
   */
  public static $modules = array('views_ui', 'block', 'php');

  public static function getInfo() {
    return array(
      'name' => 'Upgrade path',
      'description' => 'Tests the upgrade path of modules which were changed.',
      'group' => 'Views',
    );
  }

  protected function setUp() {
    parent::setUp();

    $this->enableViewsTestModule();
  }

  function viewsData() {
    $data = parent::viewsData();
    $data['views_test_data']['old_field_1']['moved to'] = array('views_test_data', 'id');
    $data['views_test_data']['old_field_2']['field']['moved to'] = array('views_test_data', 'name');
    $data['views_test_data']['old_field_3']['filter']['moved to'] = array('views_test_data', 'age');

    // @todo Test this scenario, too.
    $data['views_old_table_2']['old_field']['moved to'] = array('views_test_data', 'job');

    $data['views_old_table']['moved to'] = 'views_test_data';

    return $data;
  }

  function debugField($field) {
    $keys = array('id', 'table', 'field', 'actualField', 'original_field', 'realField');
    $info = array();
    foreach ($keys as $key) {
      $info[$key] = $field->{$key};
    }
    debug($info, NULL, TRUE);
  }

  /**
   * Tests the moved to parameter in general.
   */
  public function testMovedTo() {
    // Test moving on field lavel.
    $view = $this->createViewFromConfig('test_views_move_to_field');
    $view->update();
    $view->build();

//     $this->assertEqual('old_field_1', $view->field['old_field_1']->options['id'], "Id shouldn't change during conversion");
//     $this->assertEqual('id', $view->field['old_field_1']->field, 'The field should change during conversion');
    $this->assertEqual('id', $view->field['old_field_1']->realField);
    $this->assertEqual('views_test_data', $view->field['old_field_1']->table);
    $this->assertEqual('old_field_1', $view->field['old_field_1']->original_field, 'The field should have stored the original_field');

    // Test moving on handler lavel.
    $view = $this->createViewFromConfig('test_views_move_to_handler');
    $view->update();
    $view->build();

//     $this->assertEqual('old_field_2', $view->field['old_field_2']->options['id']);
    $this->assertEqual('name', $view->field['old_field_2']->realField);
    $this->assertEqual('views_test_data', $view->field['old_field_2']->table);

//     $this->assertEqual('old_field_3', $view->filter['old_field_3']->options['id']);
    $this->assertEqual('age', $view->filter['old_field_3']->realField);
    $this->assertEqual('views_test_data', $view->filter['old_field_3']->table);

    // Test moving on table level.
    $view = $this->createViewFromConfig('test_views_move_to_table');
    $view->update();
    $view->build();

    $this->assertEqual('views_test_data', $view->base_table, 'Make sure that view->base_table gets automatically converted.');
//     $this->assertEqual('id', $view->field['id']->field, 'If we move a whole table fields of this table should work, too.');
    $this->assertEqual('id', $view->field['id']->realField, 'To run the query right the realField has to be set right.');
    $this->assertEqual('views_test_data', $view->field['id']->table);
  }

}
