<?php

namespace Drupal\Tests\statistics\Functional;

use Drupal\Core\Cache\Cache;
use Drupal\Tests\system\Functional\Cache\AssertPageCacheContextsAndTagsTrait;

/**
 * Tests display of statistics report blocks.
 *
 * @group statistics
 */
class StatisticsReportsTest extends StatisticsTestBase {

  use AssertPageCacheContextsAndTagsTrait;

  /**
   * Tests the "popular content" block.
   */
  public function testPopularContentBlock() {
    // Clear the block cache to load the Statistics module's block definitions.
    $this->container->get('plugin.manager.block')->clearCachedDefinitions();

    // Visit a node to have something show up in the block.
    $node = $this->drupalCreateNode(['type' => 'page', 'uid' => $this->blockingUser->id()]);
    $this->drupalGet('node/' . $node->id());
    // Manually calling statistics.php, simulating ajax behavior.
    $nid = $node->id();
    $post = http_build_query(['nid' => $nid]);
    $headers = ['Content-Type' => 'application/x-www-form-urlencoded'];
    global $base_url;
    $stats_path = $base_url . '/' . drupal_get_path('module', 'statistics') . '/statistics.php';
    $client = \Drupal::httpClient();
    $client->post($stats_path, ['headers' => $headers, 'body' => $post]);

    // Configure and save the block.
    $block = $this->drupalPlaceBlock('statistics_popular_block', [
      'label' => 'Popular content',
      'top_day_num' => 3,
      'top_all_num' => 3,
      'top_last_num' => 3,
    ]);

    // Get some page and check if the block is displayed.
    $this->drupalGet('user');
    $this->assertText('Popular content', 'Found the popular content block.');
    $this->assertText("Today's", "Found today's popular content.");
    $this->assertText('All time', 'Found the all time popular content.');
    $this->assertText('Last viewed', 'Found the last viewed popular content.');

    $tags = Cache::mergeTags($node->getCacheTags(), $block->getCacheTags());
    $tags = Cache::mergeTags($tags, $this->blockingUser->getCacheTags());
    $tags = Cache::mergeTags($tags, ['block_view', 'config:block_list', 'node_list', 'rendered', 'user_view']);
    $this->assertCacheTags($tags);
    $contexts = Cache::mergeContexts($node->getCacheContexts(), $block->getCacheContexts());
    $contexts = Cache::mergeContexts($contexts, ['url.query_args:_wrapper_format']);
    $this->assertCacheContexts($contexts);

    // Check if the node link is displayed.
    $this->assertRaw(\Drupal::l($node->label(), $node->urlInfo('canonical')), 'Found link to visited node.');
  }

}
