<?php

/**
 * @file
 * Contains \Drupal\system\Plugin\Block\SystemPoweredByBlock.
 */

namespace Drupal\system\Plugin\Block;

use Drupal\block\BlockBase;
use Drupal\block\Annotation\Block;
use Drupal\Core\Annotation\Translation;

/**
 * Provides a 'Powered by Drupal' block.
 *
 * @Block(
 *   id = "system_powered_by_block",
 *   admin_label = @Translation("Powered by Drupal")
 * )
 */
class SystemPoweredByBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return array('#theme' => 'system_powered_by');
  }

}
