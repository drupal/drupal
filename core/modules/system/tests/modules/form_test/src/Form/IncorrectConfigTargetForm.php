<?php

namespace Drupal\form_test\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class IncorrectConfigTargetForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['form_test.object'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'form_test_incorrect_config_target_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['missing_key'] = [
      '#type' => 'textfield',
      '#title' => t('Missing key'),
      '#config_target' => 'form_test.object:does_not_exist',
    ];
    return parent::buildForm($form, $form_state);
  }

}
