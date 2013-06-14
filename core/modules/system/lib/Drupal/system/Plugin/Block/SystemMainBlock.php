<?php

/**
 * @file
 * Contains \Drupal\system\Plugin\Block\SystemMainBlock.
 */

namespace Drupal\system\Plugin\Block;

use Drupal\block\BlockBase;
use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Provides a 'Main page content' block.
 *
 * @Plugin(
 *   id = "system_main_block",
 *   admin_label = @Translation("Main page content"),
 *   module = "system"
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
