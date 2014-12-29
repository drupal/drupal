<?php

/**
 * @file
 * Contains \Drupal\views\Tests\ViewAjaxTest.
 */

namespace Drupal\views\Tests;

use Drupal\Component\Serialization\Json;

/**
 * Tests the ajax view functionality.
 *
 * @group views
 */
class ViewAjaxTest extends ViewTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_ajax_view');

  protected function setUp() {
    parent::setUp();

    $this->enableViewsTestModule();
  }

  /**
   * Tests an ajax view.
   */
  public function testAjaxView() {
    $this->drupalGet('test_ajax_view');

    $drupal_settings = $this->getDrupalSettings();
    $this->assertTrue(isset($drupal_settings['views']['ajax_path']), 'The Ajax callback path is set in drupalSettings.');
    $this->assertEqual(count($drupal_settings['views']['ajaxViews']), 1);
    $view_entry = array_keys($drupal_settings['views']['ajaxViews'])[0];
    $this->assertEqual($drupal_settings['views']['ajaxViews'][$view_entry]['view_name'], 'test_ajax_view', 'The view\'s ajaxViews array entry has the correct \'view_name\' key.');
    $this->assertEqual($drupal_settings['views']['ajaxViews'][$view_entry]['view_display_id'], 'page_1', 'The view\'s ajaxViews array entry has the correct \'view_display_id\' key.');

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
