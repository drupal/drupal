<?php

namespace Drupal\system\Plugin\Block;

use Drupal\Core\Block\BlockBase;

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
  public function defaultConfiguration() {
    return ['label_display' => FALSE];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return array('#markup' => '<span>' . $this->t('Powered by <a href=":poweredby">Drupal</a>', array(':poweredby' => 'https://www.drupal.org')) . '</span>');
  }

}
