<?php

namespace Drupal\system\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\MainContentBlockPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides a 'Main page content' block.
 */
#[Block(
  id: "system_main_block",
  admin_label: new TranslatableMarkup("Main page content"),
  forms: [
    'settings_tray' => FALSE,
  ]
)]
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
