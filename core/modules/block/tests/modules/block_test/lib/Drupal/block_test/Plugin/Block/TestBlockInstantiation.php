<?php

/**
 * @file
 * Contains \Drupal\block_test\Plugin\Block\TestBlockInstantiation.
 */

namespace Drupal\block_test\Plugin\Block;

use Drupal\block\BlockBase;
use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Provides a basic block for testing block instantiation and configuration.
 *
 * @Plugin(
 *   id = "test_block_instantiation",
 *   admin_label = @Translation("Display message"),
 *   module = "block_test"
 * )
 */
class TestBlockInstantiation extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function settings() {
    return array(
      'display_message' => 'no message set',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function access() {
    return user_access('access content');
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, &$form_state) {
    $form['display_message'] = array(
      '#type' => 'textfield',
      '#title' => t('Display message'),
      '#default_value' => $this->configuration['display_message'],
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, &$form_state) {
    $this->configuration['display_message'] = $form_state['values']['display_message'];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return array(
      '#children' => $this->configuration['display_message'],
    );
  }

}
