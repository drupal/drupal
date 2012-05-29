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
  public static function getInfo() {
    return array(
      'name' => 'Statistics logging tests',
      'description' => 'Tests request logging for cached and uncached pages.',
      'group' => 'Statistics'
    );
  }

  function setUp() {
    parent::setUp(array('statistics', 'block'));

    // Create Basic page node type.
    if ($this->profile != 'standard') {
      $this->drupalCreateContentType(array('type' => 'page', 'name' => 'Basic page'));
    }

    $this->auth_user = $this->drupalCreateUser(array('access content', 'create page content', 'edit own page content'));

    // Ensure we have a node page to access.
    $this->node = $this->drupalCreateNode(array('title' => $this->randomName(255), 'uid' => $this->auth_user->uid));

    // Enable page caching.
    $config = config('system.performance');
    $config->set('cache', 1);
    $config->save();

    // Enable access logging.
    variable_set('statistics_enable_access_log', 1);
    variable_set('statistics_count_content_views', 1);

    // Clear the logs.
    db_truncate('accesslog');
    db_truncate('node_counter');
  }

  /**
   * Verifies request logging for cached and uncached pages.
   */
  function testLogging() {
    $path = 'node/' . $this->node->nid;
    $expected = array(
      'title' => $this->node->title,
      'path' => $path,
    );

    // Verify logging of an uncached page.
    $this->drupalGet($path);
    // Manually calling statistics.php, simulating ajax behavior.
    $nid = $this->node->nid;
    $post = http_build_query(array('nid' => $nid));
    $headers = array('Content-Type' => 'application/x-www-form-urlencoded');
    global $base_url;
    $stats_path = $base_url . '/' . drupal_get_path('module', 'statistics'). '/statistics.php';
    drupal_http_request($stats_path, array('method' => 'POST', 'data' => $post, 'headers' => $headers, 'timeout' => 10000));
    $this->assertIdentical($this->drupalGetHeader('X-Drupal-Cache'), 'MISS', t('Testing an uncached page.'));
    $log = db_query('SELECT * FROM {accesslog}')->fetchAll(PDO::FETCH_ASSOC);
    $this->assertTrue(is_array($log) && count($log) == 1, t('Page request was logged.'));
    $this->assertEqual(array_intersect_key($log[0], $expected), $expected);
    $node_counter = statistics_get($this->node->nid);
    $this->assertIdentical($node_counter['totalcount'], '1');

    // Verify logging of a cached page.
    $this->drupalGet($path);
    // Manually calling statistics.php, simulating ajax behavior.
    drupal_http_request($stats_path, array('method' => 'POST', 'data' => $post, 'headers' => $headers, 'timeout' => 10000));
    $this->assertIdentical($this->drupalGetHeader('X-Drupal-Cache'), 'HIT', t('Testing a cached page.'));
    $log = db_query('SELECT * FROM {accesslog}')->fetchAll(PDO::FETCH_ASSOC);
    $this->assertTrue(is_array($log) && count($log) == 2, t('Page request was logged.'));
    $this->assertEqual(array_intersect_key($log[1], $expected), $expected);
    $node_counter = statistics_get($this->node->nid);
    $this->assertIdentical($node_counter['totalcount'], '2');

    // Test logging from authenticated users
    $this->drupalLogin($this->auth_user);
    $this->drupalGet($path);
    // Manually calling statistics.php, simulating ajax behavior.
    drupal_http_request($stats_path, array('method' => 'POST', 'data' => $post, 'headers' => $headers, 'timeout' => 10000));
    $log = db_query('SELECT * FROM {accesslog}')->fetchAll(PDO::FETCH_ASSOC);
    // Check the 6th item since login and account pages are also logged
    $this->assertTrue(is_array($log) && count($log) == 6, t('Page request was logged.'));
    $this->assertEqual(array_intersect_key($log[5], $expected), $expected);
    $node_counter = statistics_get($this->node->nid);
    $this->assertIdentical($node_counter['totalcount'], '3');

    // Visit edit page to generate a title greater than 255.
    $path = 'node/' . $this->node->nid . '/edit';
    $expected = array(
      'title' => truncate_utf8(t('Edit Basic page') . ' ' . $this->node->title, 255),
      'path' => $path,
    );
    $this->drupalGet($path);
    $log = db_query('SELECT * FROM {accesslog}')->fetchAll(PDO::FETCH_ASSOC);
    $this->assertTrue(is_array($log) && count($log) == 7, t('Page request was logged.'));
    $this->assertEqual(array_intersect_key($log[6], $expected), $expected);

    // Create a path longer than 255 characters. Drupal's .htaccess file
    // instructs Apache to test paths against the file system before routing to
    // index.php. Many file systems restrict file names to 255 characters
    // (http://en.wikipedia.org/wiki/Comparison_of_file_systems#Limits), and
    // Apache returns a 403 when testing longer file names, but the total path
    // length is not restricted.
    $long_path = $this->randomName(127) . '/' . $this->randomName(128);

    // Test that the long path is properly truncated when logged.
    $this->drupalGet($long_path);
    $log = db_query('SELECT * FROM {accesslog}')->fetchAll(PDO::FETCH_ASSOC);
    $this->assertTrue(is_array($log) && count($log) == 8, 'Page request was logged for a path over 255 characters.');
    $this->assertEqual($log[7]['path'], truncate_utf8($long_path, 255));

  }
}
