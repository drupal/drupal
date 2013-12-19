<?php

/**
 * @file
 * Contains \Drupal\system\Plugin\Block\SystemMainBlock.
 */

namespace Drupal\system\Plugin\Block;

use Drupal\block\BlockBase;

/**
 * Provides a 'Main page content' block.
 *
 * @Block(
 *   id = "system_main_block",
 *   admin_label = @Translation("Main page content")
 * )
 */
class SystemMainBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return array(
      drupal_set_page_content()
    );
  }

}
