<?php

declare(strict_types=1);

namespace Drupal\block_test\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides a basic block for testing block instantiation and configuration.
 */
#[Block(
  id: "test_block_instantiation",
  admin_label: new TranslatableMarkup("Display message")
)]
class TestBlockInstantiation extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'display_message' => 'no message set',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'access content');
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['display_message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Display message'),
      '#default_value' => $this->configuration['display_message'],
    ];
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
    return [
      '#children' => $this->configuration['display_message'],
    ];
  }

}
