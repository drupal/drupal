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

  /**
   * Stores the views data cache service used by this test.
   *
   * @var \Drupal\views\ViewsDataCache
   */
  protected $viewsDataCache;

  public static function getInfo() {
    return array(
      'name' => 'Table Data',
      'description' => 'Tests the fetching of views data.',
      'group' => 'Views',
    );
  }

  protected function setUp() {
    parent::setUp();

    $this->viewsDataCache = $this->container->get('views.views_data');
  }

  /**
   * Tests the views.views_data service.
   *
   * @see \Drupal\views\ViewsDataCache
   */
  public function testViewsFetchData() {
    $table_name = 'views_test_data';
    $expected_data = $this->viewsData();

    $data = $this->viewsDataCache->get($table_name);
    $this->assertEqual($data, $expected_data[$table_name], 'Make sure fetching views data by table works as expected.');

    $data = $this->viewsDataCache->get($this->randomName());
    $this->assertTrue(empty($data), 'Make sure fetching views data for an invalid table returns empty.');

    $data = $this->viewsDataCache->get();
    $this->assertTrue(isset($data[$table_name]), 'Make sure the views_test_data info appears in the total views data.');
    $this->assertEqual($data[$table_name], $expected_data[$table_name], 'Make sure the views_test_data has the expected values.');

    // Verify that views_test_data_views_data() has only been called once.
    $state = \Drupal::service('state');
    $count = $state->get('views_test_data_views_data_count');

    // Clear the storage/cache.
    $this->viewsDataCache->clear();
    // Get the data again.
    $this->viewsDataCache->get($table_name);
    // Verify that view_test_data_views_data() has run again.
    $this->assertEqual($count + 1, $state->get('views_test_data_views_data_count'));

    // Get the data again.
    $this->viewsDataCache->get($table_name);
    // Also request all table data.
    $this->viewsDataCache->get();
    // Verify that view_test_data_views_data() has not run again.
    $this->assertEqual($count + 1, $state->get('views_test_data_views_data_count'));
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

  /**
   * Tests the fetchBaseTables() method.
   */
  public function testFetchBaseTables() {
    // Enabled node module so there is more than 1 base table to test.
    $this->enableModules(array('node'));
    $data = $this->viewsDataCache->get();
    $base_tables = $this->viewsDataCache->fetchBaseTables();

    // Test the number of tables returned and their order.
    $this->assertEqual(count($base_tables), 3, 'The correct amount of base tables were returned.');
    $this->assertIdentical(array_keys($base_tables), array('node', 'node_revision', 'views_test_data'), 'The tables are sorted as expected.');

    // Test the values returned for each base table.
    $defaults = array(
      'title' => '',
      'help' => '',
      'weight' => 0,
    );
    foreach ($base_tables as $base_table => $info) {
      // Merge in default values as in fetchBaseTables().
      $expected = $data[$base_table]['table']['base'] += $defaults;
      foreach ($defaults as $key => $default) {
        $this->assertEqual($info[$key], $expected[$key]);
      }
    }
  }

}
