<?php

/**
 * @file
 * Contains \Drupal\search\Plugin\Block\SearchBlock.
 */

namespace Drupal\search\Plugin\Block;

use Drupal\block\BlockBase;
use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Provides a 'Search form' block.
 *
 * @Plugin(
 *   id = "search_form_block",
 *   admin_label = @Translation("Search form"),
 *   module = "search"
 * )
 */
class SearchBlock extends BlockBase {

  /**
   * Overrides \Drupal\block\BlockBase::access().
   */
  public function access() {
    return user_access('search content');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return array(drupal_get_form('search_block_form'));
  }

}
