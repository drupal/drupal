<?php

/**
 * @file
 * Definition of Drupal\statistics\Tests\StatisticsTokenReplaceTest.
 */

namespace Drupal\statistics\Tests;

/**
 * Tests statistics token replacement in strings.
 */
class StatisticsTokenReplaceTest extends StatisticsTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Statistics token replacement',
      'description' => 'Generates text using placeholders for dummy content to check statistics token replacement.',
      'group' => 'Statistics',
    );
  }

  /**
   * Creates a node, then tests the statistics tokens generated from it.
   */
  function testStatisticsTokenReplacement() {
    global $language_interface;

    // Create user and node.
    $user = $this->drupalCreateUser(array('create page content'));
    $this->drupalLogin($user);
    $node = $this->drupalCreateNode(array('type' => 'page', 'uid' => $user->uid));

    // Hit the node.
    $this->drupalGet('node/' . $node->nid);
    // Manually calling statistics.php, simulating ajax behavior.
    $nid = $node->nid;
    $post = http_build_query(array('nid' => $nid));
    $headers = array('Content-Type' => 'application/x-www-form-urlencoded');
    global $base_url;
    $stats_path = $base_url . '/' . drupal_get_path('module', 'statistics'). '/statistics.php';
    drupal_http_request($stats_path, array('method' => 'POST', 'data' => $post, 'headers' => $headers, 'timeout' => 10000));
    $statistics = statistics_get($node->nid);

    // Generate and test tokens.
    $tests = array();
    $tests['[node:total-count]'] = 1;
    $tests['[node:day-count]'] = 1;
    $tests['[node:last-view]'] = format_date($statistics['timestamp']);
    $tests['[node:last-view:short]'] = format_date($statistics['timestamp'], 'short');

    // Test to make sure that we generated something for each token.
    $this->assertFalse(in_array(0, array_map('strlen', $tests)), t('No empty tokens generated.'));

    foreach ($tests as $input => $expected) {
      $output = token_replace($input, array('node' => $node), array('language' => $language_interface));
      $this->assertEqual($output, $expected, t('Statistics token %token replaced.', array('%token' => $input)));
    }
  }
}
