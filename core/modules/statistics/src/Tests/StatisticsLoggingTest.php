<?php

/**
 * @file
 * Definition of Drupal\statistics\Tests\StatisticsLoggingTest.
 */

namespace Drupal\statistics\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests request logging for cached and uncached pages.
 *
 * We subclass WebTestBase rather than StatisticsTestBase, because we
 * want to test requests from an anonymous user.
 *
 * @group statistics
 */
class StatisticsLoggingTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'statistics', 'block');

  /**
   * The Guzzle HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface;
   */
  protected $client;

  protected function setUp() {
    parent::setUp();

    // Create Basic page node type.
    if ($this->profile != 'standard') {
      $this->drupalCreateContentType(array('type' => 'page', 'name' => 'Basic page'));
    }

    $this->auth_user = $this->drupalCreateUser(array('access content', 'create page content', 'edit own page content'));

    // Ensure we have a node page to access.
    $this->node = $this->drupalCreateNode(array('title' => $this->randomMachineName(255), 'uid' => $this->auth_user->id()));

    // Enable access logging.
    \Drupal::config('statistics.settings')
      ->set('count_content_views', 1)
      ->save();

    // Clear the logs.
    db_truncate('node_counter');

    $this->client = \Drupal::httpClient();
    $this->client->setDefaultOption('config/curl', array(CURLOPT_TIMEOUT => 10));
  }

  /**
   * Verifies node hit counter logging and script placement.
   */
  function testLogging() {
    global $base_url;
    $path = 'node/' . $this->node->id();
    $module_path = drupal_get_path('module', 'statistics');
    $stats_path = $base_url . '/' . $module_path . '/statistics.php';
    $expected_library = $module_path . '/statistics.js';
    $expected_settings = '"statistics":{"data":{"nid":"' . $this->node->id() . '"}';

    // Verify that logging scripts are not found on a non-node page.
    $this->drupalGet('node');
    $this->assertNoRaw($expected_library, 'Statistics library JS not found on node page.');
    $this->assertNoRaw($expected_settings, 'Statistics settings not found on node page.');

    // Verify that logging scripts are not found on a non-existent node page.
    $this->drupalGet('node/9999');
    $this->assertNoRaw($expected_library, 'Statistics library JS not found on non-existent node page.');
    $this->assertNoRaw($expected_settings, 'Statistics settings not found on non-existent node page.');

    // Verify that logging scripts are found on a valid node page.
    $this->drupalGet($path);
    $this->assertRaw($expected_library, 'Found statistics library JS on node page.');
    $this->assertRaw($expected_settings, 'Found statistics settings on node page.');

    // Manually call statistics.php to simulate ajax data collection behavior.
    $nid = $this->node->id();
    $post = array('nid' => $nid);
    $this->client->post($stats_path, array('body' => $post));
    $node_counter = statistics_get($this->node->id());
    $this->assertIdentical($node_counter['totalcount'], '1');
  }

}
