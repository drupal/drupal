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

  /**
   * Stores a count for hook_views_data being invoked.
   *
   * @var int
   */
  protected $count = 0;

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
    $this->state = $this->container->get('state');
  }

  /**
   * Tests the views.views_data service.
   *
   * @see \Drupal\views\ViewsDataCache
   */
  public function testViewsFetchData() {
    $table_name = 'views_test_data';
    $random_table_name = $this->randomName();
    // Invoke expected data directly from hook_views_data implementations.
    $expected_data = $this->container->get('module_handler')->invokeAll('views_data');

    // Verify that views_test_data_views_data() has only been called once after
    // calling clear().
    $this->startCount();
    $this->viewsDataCache->get();
    // Test views data has been invoked.
    $this->assertCountIncrement();
    // Clear the storage/cache.
    $this->viewsDataCache->clear();
    // Get the data again.
    $this->viewsDataCache->get();
    $this->viewsDataCache->get($table_name);
    $this->viewsDataCache->get($random_table_name);
    // Verify that view_test_data_views_data() has run once.
    $this->assertCountIncrement();

    // Get the data again.
    $this->viewsDataCache->get();
    $this->viewsDataCache->get($table_name);
    $this->viewsDataCache->get($random_table_name);
    // Verify that view_test_data_views_data() has not run again.
    $this->assertCountIncrement(FALSE);

    // Clear the views data, and test all table data.
    $this->viewsDataCache->clear();
    $this->startCount();
    $data = $this->viewsDataCache->get();
    $this->assertEqual($data, $expected_data, 'Make sure fetching all views data by works as expected.');
    // Views data should be invoked once.
    $this->assertCountIncrement();
    // Calling get() again, the count for this table should stay the same.
    $data = $this->viewsDataCache->get();
    $this->assertEqual($data, $expected_data, 'Make sure fetching all cached views data works as expected.');
    $this->assertCountIncrement(FALSE);

    // Clear the views data, and test data for a specific table.
    $this->viewsDataCache->clear();
    $this->startCount();
    $data = $this->viewsDataCache->get($table_name);
    $this->assertEqual($data, $expected_data[$table_name], 'Make sure fetching views data by table works as expected.');
    // Views data should be invoked once.
    $this->assertCountIncrement();
    // Calling get() again, the count for this table should stay the same.
    $data = $this->viewsDataCache->get($table_name);
    $this->assertEqual($data, $expected_data[$table_name], 'Make sure fetching cached views data by table works as expected.');
    $this->assertCountIncrement(FALSE);
    // Test that this data is present if all views data is returned.
    $data = $this->viewsDataCache->get();
    $this->assertTrue(isset($data[$table_name]), 'Make sure the views_test_data info appears in the total views data.');
    $this->assertEqual($data[$table_name], $expected_data[$table_name], 'Make sure the views_test_data has the expected values.');

    // Clear the views data, and test data for an invalid table.
    $this->viewsDataCache->clear();
    $this->startCount();
    // All views data should be requested on the first try.
    $data = $this->viewsDataCache->get($random_table_name);
    $this->assertEqual($data, array(), 'Make sure fetching views data for an invalid table returns an empty array.');
    $this->assertCountIncrement();
    // Test no data is rebuilt when requesting an invalid table again.
    $data = $this->viewsDataCache->get($random_table_name);
    $this->assertEqual($data, array(), 'Make sure fetching views data for an invalid table returns an empty array.');
    $this->assertCountIncrement(FALSE);
  }

  /**
   * Starts a count for hook_views_data being invoked.
   */
  protected function startCount() {
    $count = $this->state->get('views_test_data_views_data_count');
    $this->count = isset($count) ? $count : 0;
  }

  /**
   * Asserts that the count for hook_views_data either equal or has increased.
   *
   * @param bool $equal
   *   Whether to assert that the count should be equal. Defaults to FALSE.
   */
  protected function assertCountIncrement($increment = TRUE) {
    if ($increment) {
      // If an incremented count is expected, increment this now.
      $this->count++;
      $message = 'hook_views_data has been invoked.';
    }
    else {
      $message = 'hook_views_data has not been invoked';
    }

    $this->assertEqual($this->count, $this->state->get('views_test_data_views_data_count'), $message);
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
    $this->enableModules(array('views_ui'));
    $this->container->get('module_handler')->loadInclude('views_ui', 'inc', 'admin');

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
