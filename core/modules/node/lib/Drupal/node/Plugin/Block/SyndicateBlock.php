<?php

/**
 * @file
 * Contains \Drupal\node\Plugin\Block\SyndicateBlock.
 */

namespace Drupal\node\Plugin\Block;

use Drupal\block\BlockBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a 'Syndicate' block that links to the site's RSS feed.
 *
 * @Block(
 *   id = "node_syndicate_block",
 *   admin_label = @Translation("Syndicate"),
 *   category = @Translation("System")
 * )
 */
class SyndicateBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'block_count' => 10,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account) {
    return $account->hasPermission('access content');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return array(
      '#theme' => 'feed_icon',
      '#url' => 'rss.xml',
    );
  }

}
