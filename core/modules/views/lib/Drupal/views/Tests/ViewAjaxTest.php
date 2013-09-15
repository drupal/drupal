<?php

/**
 * @file
 * Contains \Drupal\views\Tests\ViewAjaxTest.
 */

namespace Drupal\views\Tests;
use Drupal\Component\Utility\Json;

/**
 * Tests the ajax view functionality.
 */
class ViewAjaxTest extends ViewTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_ajax_view');

  public static function getInfo() {
    return array(
      'name' => 'View: Ajax',
      'description' => 'Tests the ajax view functionality.',
      'group' => 'Views'
    );
  }

  protected function setUp() {
    parent::setUp();

    $this->enableViewsTestModule();
  }

  /**
   * Tests an ajax view.
   */
  public function testAjaxView() {
    $this->drupalGet('test_ajax_view');

    $data = array();
    $data['view_name'] = 'test_ajax_view';
    $data['view_display_id'] = 'test_ajax_view';

    $post = array(
      'view_name' => 'test_ajax_view',
      'view_display_id' => 'page_1',
    );
    $response = $this->drupalPost('views/ajax', 'application/json', $post);
    $data = Json::decode($response);

    // Ensure that the view insert command is part of the result.
    $this->assertEqual($data[1]['command'], 'insert');
    $this->assertTrue(strpos($data[1]['selector'], '.view-dom-id-') === 0);

    $this->drupalSetContent($data[1]['data']);
    $result = $this->xpath('//div[contains(@class, "views-row")]');
    $this->assertEqual(count($result), 2, 'Ensure that two items are renderd in the HTML.');
  }

}
