<?php

/**
 * @file
 * Contains \Drupal\system\Plugin\Block\SystemHelpBlock.
 */

namespace Drupal\system\Plugin\Block;

use Drupal\block\BlockBase;
use Drupal\block\Annotation\Block;
use Drupal\Core\Annotation\Translation;

/**
 * Provides a 'System Help' block.
 *
 * @Block(
 *   id = "system_help_block",
 *   admin_label = @Translation("System Help")
 * )
 */
class SystemHelpBlock extends BlockBase {

  /**
   * Stores the help text associated with the active menu item.
   *
   * @var string
   */
  protected $help;

  /**
   * Overrides \Drupal\block\BlockBase::access().
   */
  public function access() {
    $this->help = menu_get_active_help();
    return (bool) $this->help;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return array(
      '#children' => $this->help,
    );
  }

}
