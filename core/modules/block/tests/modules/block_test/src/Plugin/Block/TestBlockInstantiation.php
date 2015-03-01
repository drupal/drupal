<?php

/**
 * @file
 * Contains \Drupal\block_test\Plugin\Block\TestBlockInstantiation.
 */

namespace Drupal\block_test\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a basic block for testing block instantiation and configuration.
 *
 * @Block(
 *   id = "test_block_instantiation",
 *   admin_label = @Translation("Display message")
 * )
 */
class TestBlockInstantiation extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'display_message' => 'no message set',
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return $account->hasPermission('access content');
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['display_message'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Display message'),
      '#default_value' => $this->configuration['display_message'],
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['display_message'] = $form_state->getValue('display_message');
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
