<?php

/**
 * @file
 * Contains \Drupal\system\Plugin\Block\SystemPoweredByBlock.
 */

namespace Drupal\system\Plugin\Block;

use Drupal\block\BlockBase;
use Drupal\Core\Cache\Cache;

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
    return array('#markup' => '<span>' . t('Powered by <a href="@poweredby">Drupal</a>', array('@poweredby' => 'http://drupal.org')) . '</span>');
  }


  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, array &$form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    // The 'Powered by Drupal' block is permanently cacheable, because its
    // contents can never change.
    $form['cache']['#disabled'] = TRUE;
    $form['cache']['max_age']['#value'] = Cache::PERMANENT;
    $form['cache']['#description'] = t('This block is always cached forever, it is not configurable.');

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return Cache::PERMANENT;
  }

  /**
   * {@inheritdoc}
   */
  public function isCacheable() {
    return TRUE;
  }

}
