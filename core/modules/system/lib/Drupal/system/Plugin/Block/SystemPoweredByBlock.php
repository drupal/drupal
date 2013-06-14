<?php

/**
 * @file
 * Contains \Drupal\system\Plugin\Block\SystemPoweredByBlock.
 */

namespace Drupal\system\Plugin\Block;

use Drupal\block\BlockBase;
use Drupal\Component\Annotation\Plugin;
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
   * {@inheritdoc}
   */
  public function build() {
    return array(
      '#children' => theme('system_powered_by'),
    );
  }

}
