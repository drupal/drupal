<?php

/**
 * @file
 * Contains \Drupal\system\Plugin\block\block\SystemPoweredByBlock.
 */

namespace Drupal\system\Plugin\block\block;

use Drupal\block\BlockBase;
use Drupal\Core\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Provides a 'Powered by Drupal' block.
 *
 * @Plugin(
 *   id = "system_powered_by_block",
 *   admin_label = @Translation("Powered by Drupal"),
 *   module = "system"
 * )
 */
class SystemPoweredByBlock extends BlockBase {

  /**
   * Implements \Drupal\block\BlockBase::build().
   */
  public function build() {
    return array(
      '#children' => theme('system_powered_by'),
    );
  }

}
