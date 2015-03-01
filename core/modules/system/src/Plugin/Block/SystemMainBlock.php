<?php

/**
 * @file
 * Contains \Drupal\system\Plugin\Block\SystemMainBlock.
 */

namespace Drupal\system\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\MainContentBlockPluginInterface;
use Drupal\Core\Form\FormStateInterface;

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

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    // The main content block is never cacheable, because it may be dynamic.
    $form['cache']['#disabled'] = TRUE;
    $form['cache']['#description'] = $this->t('This block is never cacheable, it is not configurable.');
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
