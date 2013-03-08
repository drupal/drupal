<?php

/**
 * @file
 * Contains \Drupal\forum\Plugin\block\block\NewTopicsBlock.
 */

namespace Drupal\forum\Plugin\block\block;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Provides a 'New forum topics' block.
 *
 * @Plugin(
 *   id = "forum_new_block",
 *   admin_label = @Translation("New forum topics"),
 *   module = "forum"
 * )
 */
class NewTopicsBlock extends ForumBlockBase {

  /**
   * Implements \Drupal\block\BlockBase::build().
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
