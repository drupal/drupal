<?php

namespace Drupal\system\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\MainContentBlockPluginInterface;
use Drupal\Core\Logger\LoggerChannelTrait;

/**
 * Provides a 'Main page content' block.
 *
 * @Block(
 *   id = "system_main_block",
 *   admin_label = @Translation("Main page content"),
 *   forms = {
 *     "settings_tray" = FALSE,
 *   },
 * )
 */
class SystemMainBlock extends BlockBase implements MainContentBlockPluginInterface {

  use LoggerChannelTrait;

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
  public function build(): array {
    if (!is_array($this->mainContent)) {
      $this->getLogger('system')->error('The system_main_block was placed but ::setMainContent() was not called.');
      return [];
    }
    return $this->mainContent;
  }

}
