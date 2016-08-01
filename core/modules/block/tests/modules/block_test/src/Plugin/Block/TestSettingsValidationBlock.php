<?php

namespace Drupal\block_test\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a test settings validation block.
 *
 * @Block(
 *  id = "test_settings_validation",
 *  admin_label = @Translation("Test settings validation block"),
 * )
 */
class TestSettingsValidationBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    return ['digits' => ['#type' => 'textfield']] + $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state) {
    if (!ctype_digit($form_state->getValue('digits'))) {
      $form_state->setErrorByName('digits', $this->t('Only digits are allowed'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return ['#markup' => 'foo'];
  }

}
