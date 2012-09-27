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
class ViewsDataTest extends ViewTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Table Data',
      'description' => 'Tests the fetching of views data.',
      'group' => 'Views',
    );
  }

  protected function setUp() {
    parent::setUp();

    $this->enableViewsTestModule();
  }

  /**
   * Tests the views_fetch_data function.
   *
   * @see views_fetch_data
   */
  public function testViewsFetchData() {
    $table_name = 'views_test_data';
    $expected_data = $this->viewsData();

    $data = views_fetch_data($table_name);
    $this->assertEqual($data, $expected_data[$table_name], 'Make sure fetching views data by table works as expected.');

    $data = views_fetch_data();
    $this->assertTrue(isset($data[$table_name]), 'Make sure the views_test_data info appears in the total views data.');
    $this->assertEqual($data[$table_name], $expected_data[$table_name], 'Make sure the views_test_data has the expected values.');

    $data = views_fetch_data(NULL, TRUE, TRUE);
    $this->assertTrue(isset($data[$table_name]), 'Make sure the views_fetch_data appears in the total views data with reset = TRUE.');
    $this->assertEqual($data[$table_name], $expected_data[$table_name], 'Make sure the views_test_data has the expected values.');
  }

}
