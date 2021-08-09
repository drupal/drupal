<?php

namespace Drupal\Tests\statistics\Functional;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Link;
use Drupal\Tests\system\Functional\Cache\AssertPageCacheContextsAndTagsTrait;

/**
 * Tests display of statistics report blocks.
 *
 * @group statistics
 */
class StatisticsReportsTest extends StatisticsTestBase {

  use AssertPageCacheContextsAndTagsTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

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
    $stats_path = $base_url . '/' . $this->getModulePath('statistics') . '/statistics.php';
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
    $this->assertSession()->pageTextContains('Popular content');
    $this->assertSession()->pageTextContains("Today's");
    $this->assertSession()->pageTextContains('All time');
    $this->assertSession()->pageTextContains('Last viewed');

    $tags = Cache::mergeTags($node->getCacheTags(), $block->getCacheTags());
    $tags = Cache::mergeTags($tags, $this->blockingUser->getCacheTags());
    $tags = Cache::mergeTags($tags, ['block_view', 'config:block_list', 'node_list', 'rendered', 'user_view']);
    $this->assertCacheTags($tags);
    $contexts = Cache::mergeContexts($node->getCacheContexts(), $block->getCacheContexts());
    $contexts = Cache::mergeContexts($contexts, ['url.query_args:_wrapper_format', 'url.site']);
    $this->assertCacheContexts($contexts);

    // Check if the node link is displayed.
    $this->assertSession()->responseContains(Link::fromTextAndUrl($node->label(), $node->toUrl('canonical'))->toString());
  }

}
