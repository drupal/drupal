<?php

/**
 * @file
 * Definition of Drupal\views\Tests\ViewsDataTest.
 */

namespace Drupal\views\Tests;

/**
 * Tests the fetching of views data.
 *
 * @see hook_views_data
 */
class ViewsDataTest extends ViewUnitTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Table Data',
      'description' => 'Tests the fetching of views data.',
      'group' => 'Views',
    );
  }

  /**
   * Tests the views_fetch_data function.
   *
   * @see views_fetch_data
   */
  public function testViewsFetchData() {
    $table_name = 'views_test_data';
    $expected_data = $this->viewsData();

    $data = drupal_container()->get('views.views_data')->get($table_name);
    $this->assertEqual($data, $expected_data[$table_name], 'Make sure fetching views data by table works as expected.');

    $data = drupal_container()->get('views.views_data')->get();
    $this->assertTrue(isset($data[$table_name]), 'Make sure the views_test_data info appears in the total views data.');
    $this->assertEqual($data[$table_name], $expected_data[$table_name], 'Make sure the views_test_data has the expected values.');
  }

  /**
   * Overrides Drupal\views\Tests\ViewTestBase::viewsData().
   */
  protected function viewsData() {
    $data = parent::viewsData();

    // Tweak the views data to have a base for testing views_fetch_fields().
    unset($data['views_test_data']['id']['field']);
    unset($data['views_test_data']['name']['argument']);
    unset($data['views_test_data']['age']['filter']);
    unset($data['views_test_data']['job']['sort']);
    $data['views_test_data']['created']['area']['id'] = 'text';
    $data['views_test_data']['age']['area']['id'] = 'text';
    $data['views_test_data']['age']['area']['sub_type'] = 'header';
    $data['views_test_data']['job']['area']['id'] = 'text';
    $data['views_test_data']['job']['area']['sub_type'] = array('header', 'footer');


    return $data;
  }


  /**
   * Tests the views_fetch_fields function().
   */
  public function testViewsFetchFields() {
    module_load_include('inc', 'views_ui', 'admin');

    $expected = array(
      'field' => array(
        'name',
        'age',
        'job',
        'created',
      ),
      'argument' => array(
        'id',
        'age',
        'job',
        'created',
      ),
      'filter' => array(
        'id',
        'name',
        'job',
        'created',
      ),
      'sort' => array(
        'id',
        'name',
        'age',
        'created',
      ),
      'area' => array(
        'created',
        'job',
        'age'
      ),
      'header' => array(
        'created',
        'job',
        'age'
      ),
      'footer' => array(
        'created',
        'job',
      ),
    );

    $handler_types = array('field', 'argument', 'filter', 'sort', 'area');
    foreach ($handler_types as $handler_type) {
      $fields = views_fetch_fields('views_test_data', $handler_type);
      $expected_keys = array_walk($expected[$handler_type], function(&$item) {
        $item = "views_test_data.$item";
      });
      $this->assertEqual($expected_keys, array_keys($fields), format_string('Handlers of type @handler_type are listed as expected.', array('@handler_type' => $handler_type)));
    }

    // Check for subtype filtering, so header and footer.
    foreach (array('header', 'footer') as $sub_type) {
      $fields = views_fetch_fields('views_test_data', 'area', FALSE, $sub_type);

      $expected_keys = array_walk($expected[$sub_type], function(&$item) {
        $item = "views_test_data.$item";
      });
      $this->assertEqual($expected_keys, array_keys($fields), format_string('Sub_type @sub_type is filtered as expected.', array('@sub_type' => $sub_type)));
    }
  }

}
