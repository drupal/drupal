<?php

/**
 * @file
 * Contains \Drupal\forum\Plugin\Block\ActiveTopicsBlock.
 */

namespace Drupal\forum\Plugin\Block;

/**
 * Provides an 'Active forum topics' block.
 *
 * @Block(
 *   id = "forum_active_block",
 *   admin_label = @Translation("Active forum topics"),
 *   category = @Translation("Lists (Views)")
 * )
 */
class ActiveTopicsBlock extends ForumBlockBase {

  /**
   * {@inheritdoc}
   */
  protected function buildForumQuery() {
    return db_select('forum_index', 'f')
      ->fields('f')
      ->addTag('node_access')
      ->addMetaData('base_table', 'forum_index')
      ->orderBy('f.last_comment_timestamp', 'DESC')
      ->range(0, $this->configuration['block_count']);
  }

}
