<?php

/**
 * @file
 * Definition of Drupal\statistics\Tests\StatisticsLoggingTest.
 */

namespace Drupal\statistics\Tests;

use Drupal\simpletest\WebTestBase;
use PDO;

/**
 * Tests that logging via statistics_exit() works for all pages.
 *
 * We subclass WebTestBase rather than StatisticsTestBase, because we
 * want to test requests from an anonymous user.
 */
class StatisticsLoggingTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('statistics', 'block');

  /**
   * The Guzzle HTTP client.
   *
   * @var \Guzzle\Http\ClientInterface;
   */
  protected $client;

  public static function getInfo() {
    return array(
      'name' => 'Statistics logging tests',
      'description' => 'Tests request logging for cached and uncached pages.',
      'group' => 'Statistics'
    );
  }

  function setUp() {
    parent::setUp();

    // Create Basic page node type.
    if ($this->profile != 'standard') {
      $this->drupalCreateContentType(array('type' => 'page', 'name' => 'Basic page'));
    }

    $this->auth_user = $this->drupalCreateUser(array('access content', 'create page content', 'edit own page content'));

    // Ensure we have a node page to access.
    $this->node = $this->drupalCreateNode(array('title' => $this->randomName(255), 'uid' => $this->auth_user->id()));

    // Enable page caching.
    $config = \Drupal::config('system.performance');
    $config->set('cache.page.use_internal', 1);
    $config->set('cache.page.max_age', 300);
    $config->save();

    // Enable access logging.
    \Drupal::config('statistics.settings')
      ->set('count_content_views', 1)
      ->save();

    // Clear the logs.
    db_truncate('node_counter');

    $this->client = \Drupal::httpClient();
    $this->client->setConfig(array('curl.options' => array(CURLOPT_TIMEOUT => 10)));
  }

  /**
   * Verifies request logging for cached and uncached pages.
   */
  function testLogging() {
    $path = 'node/' . $this->node->id();
    $expected = array(
      'title' => $this->node->label(),
      'path' => $path,
    );

    // Verify logging of an uncached page.
    $this->drupalGet($path);
    // Manually calling statistics.php, simulating ajax behavior.
    $nid = $this->node->id();
    $post = array('nid' => $nid);
    global $base_url;
    $stats_path = $base_url . '/' . drupal_get_path('module', 'statistics'). '/statistics.php';
    $this->client->post($stats_path, array(), $post)->send();
    $this->assertIdentical($this->drupalGetHeader('X-Drupal-Cache'), 'MISS', 'Testing an uncached page.');
    $node_counter = statistics_get($this->node->id());
    $this->assertIdentical($node_counter['totalcount'], '1');

    // Verify logging of a cached page.
    $this->drupalGet($path);
    // Manually calling statistics.php, simulating ajax behavior.
    $this->client->post($stats_path, array(), $post)->send();
    $this->assertIdentical($this->drupalGetHeader('X-Drupal-Cache'), 'HIT', 'Testing a cached page.');
    $node_counter = statistics_get($this->node->id());
    $this->assertIdentical($node_counter['totalcount'], '2');

    // Test logging from authenticated users
    $this->drupalLogin($this->auth_user);
    $this->drupalGet($path);
    // Manually calling statistics.php, simulating ajax behavior.
    $this->client->post($stats_path, array(), $post)->send();
    $node_counter = statistics_get($this->node->id());
    $this->assertIdentical($node_counter['totalcount'], '3');

  }
}
