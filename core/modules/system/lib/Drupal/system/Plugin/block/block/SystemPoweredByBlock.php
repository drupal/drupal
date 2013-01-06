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
 *   subject = @Translation("Powered by Drupal"),
 *   module = "system"
 * )
 */
class SystemPoweredByBlock extends BlockBase {

  /**
   * Implements \Drupal\block\BlockBase::blockBuild().
   */
  public function blockBuild() {
    return array(
      '#children' => theme('system_powered_by'),
    );
  }

}
