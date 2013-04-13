<?php

/**
 * @file
 * Contains \Drupal\forum\Plugin\block\block\ActiveTopicsBlock.
 */

namespace Drupal\forum\Plugin\block\block;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Provides an 'Active forum topics' block.
 *
 * @Plugin(
 *   id = "forum_active_block",
 *   admin_label = @Translation("Active forum topics"),
 *   module = "forum"
 * )
 */
class ActiveTopicsBlock extends ForumBlockBase {

  /**
   * Implements \Drupal\block\BlockBase::blockBuild().
   */
  protected function blockBuild() {
    $query = db_select('forum_index', 'f')
      ->fields('f')
      ->addTag('node_access')
      ->addMetaData('base_table', 'forum_index')
      ->orderBy('f.last_comment_timestamp', 'DESC')
      ->range(0, $this->configuration['block_count']);

    return array(
      drupal_render_cache_by_query($query, 'forum_block_view'),
    );
  }

}
