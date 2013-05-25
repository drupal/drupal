<?php

/**
 * @file
 * Definition of Drupal\statistics\Tests\StatisticsTokenReplaceTest.
 */

namespace Drupal\statistics\Tests;

use Drupal\Core\Language\Language;

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
    $language_interface = language(Language::TYPE_INTERFACE);

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
    $client = \Drupal::httpClient();
    $client->setConfig(array('curl.options' => array(CURLOPT_TIMEOUT => 10)));
    $client->post($stats_path, $headers, $post)->send();
    $statistics = statistics_get($node->nid);

    // Generate and test tokens.
    $tests = array();
    $tests['[node:total-count]'] = 1;
    $tests['[node:day-count]'] = 1;
    $tests['[node:last-view]'] = format_date($statistics['timestamp']);
    $tests['[node:last-view:short]'] = format_date($statistics['timestamp'], 'short');

    // Test to make sure that we generated something for each token.
    $this->assertFalse(in_array(0, array_map('strlen', $tests)), 'No empty tokens generated.');

    foreach ($tests as $input => $expected) {
      $output = \Drupal::token()->replace($input, array('node' => $node), array('langcode' => $language_interface->langcode));
      $this->assertEqual($output, $expected, format_string('Statistics token %token replaced.', array('%token' => $input)));
    }
  }
}
