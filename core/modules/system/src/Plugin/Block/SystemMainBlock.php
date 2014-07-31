<?php

/**
 * @file
 * Contains \Drupal\system\Plugin\Block\SystemMainBlock.
 */

namespace Drupal\system\Plugin\Block;

use Drupal\block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'Main page content' block.
 *
 * @Block(
 *   id = "system_main_block",
 *   admin_label = @Translation("Main page content")
 * )
 */
class SystemMainBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return array(
      drupal_set_page_content()
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    // The main content block is never cacheable, because it may be dynamic.
    $form['cache']['#disabled'] = TRUE;
    $form['cache']['#description'] = t('This block is never cacheable, it is not configurable.');
    $form['cache']['max_age']['#value'] = 0;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function isCacheable() {
    // The main content block is never cacheable, because it may be dynamic.
    return FALSE;
  }

}
