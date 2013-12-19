<?php

/**
 * @file
 * Contains \Drupal\forum\Plugin\Block\NewTopicsBlock.
 */

namespace Drupal\forum\Plugin\Block;

/**
 * Provides a 'New forum topics' block.
 *
 * @Block(
 *   id = "forum_new_block",
 *   admin_label = @Translation("New forum topics"),
 *   category = @Translation("Lists (Views)")
 * )
 */
class NewTopicsBlock extends ForumBlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $query = db_select('forum_index', 'f')
      ->fields('f')
      ->addTag('node_access')
      ->addMetaData('base_table', 'forum_index')
      ->orderBy('f.created', 'DESC')
      ->range(0, $this->configuration['block_count']);

    return array(
      drupal_render_cache_by_query($query, 'forum_block_view'),
    );
  }

}
