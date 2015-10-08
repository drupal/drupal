<?php

/**
 * @file
 * Contains \Drupal\system\Plugin\Block\SystemMainBlock.
 */

namespace Drupal\system\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\MainContentBlockPluginInterface;

/**
 * Provides a 'Main page content' block.
 *
 * @Block(
 *   id = "system_main_block",
 *   admin_label = @Translation("Main page content")
 * )
 */
class SystemMainBlock extends BlockBase implements MainContentBlockPluginInterface {

  /**
   * The render array representing the main page content.
   *
   * @var array
   */
  protected $mainContent;

  /**
   * {@inheritdoc}
   */
  public function setMainContent(array $main_content) {
    $this->mainContent = $main_content;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return $this->mainContent;
  }

}
