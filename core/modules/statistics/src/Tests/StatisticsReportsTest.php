<?php

/**
 * @file
 * Definition of Drupal\statistics\Tests\StatisticsReportsTest.
 */

namespace Drupal\statistics\Tests;

/**
 * Tests display of statistics report blocks.
 *
 * @group statistics
 */
class StatisticsReportsTest extends StatisticsTestBase {

  /**
   * Tests the "popular content" block.
   */
  function testPopularContentBlock() {
    // Clear the block cache to load the Statistics module's block definitions.
    $this->container->get('plugin.manager.block')->clearCachedDefinitions();

    // Visit a node to have something show up in the block.
    $node = $this->drupalCreateNode(array('type' => 'page', 'uid' => $this->blockingUser->id()));
    $this->drupalGet('node/' . $node->id());
    // Manually calling statistics.php, simulating ajax behavior.
    $nid = $node->id();
    $post = http_build_query(array('nid' => $nid));
    $headers = array('Content-Type' => 'application/x-www-form-urlencoded');
    global $base_url;
    $stats_path = $base_url . '/' . drupal_get_path('module', 'statistics'). '/statistics.php';
    $client = \Drupal::httpClient();
    $client->setDefaultOption('config/curl', array(CURLOPT_TIMEOUT => 10));
    $client->post($stats_path, array('headers' => $headers, 'body' => $post));

    // Configure and save the block.
    $this->drupalPlaceBlock('statistics_popular_block', array(
      'label' => 'Popular content',
      'top_day_num' => 3,
      'top_all_num' => 3,
      'top_last_num' => 3,
    ));

    // Get some page and check if the block is displayed.
    $this->drupalGet('user');
    $this->assertText('Popular content', 'Found the popular content block.');
    $this->assertText("Today's", "Found today's popular content.");
    $this->assertText('All time', 'Found the all time popular content.');
    $this->assertText('Last viewed', 'Found the last viewed popular content.');

    $this->assertRaw(\Drupal::l($node->label(), $node->urlInfo()), 'Found link to visited node.');
  }

}
