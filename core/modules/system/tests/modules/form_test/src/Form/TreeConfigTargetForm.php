<?php

namespace Drupal\form_test\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class TreeConfigTargetForm extends ConfigFormBase {

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
    return 'form_test_tree_config_target_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['vegetables'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#tree' => TRUE,
      '#input' => TRUE,
      '#title' => t('Vegetable preferences'),
    ];
    $form['vegetables']['favorite'] = [
      '#type' => 'textfield',
      '#title' => t('Favorite'),
      '#default_value' => 'Potato',
      '#config_target' => 'form_test.object:favorite_vegetable',
    ];
    $form['vegetables']['nemesis'] = [
      '#type' => 'textfield',
      '#title' => t('Nemesis'),
      '#config_target' => 'form_test.object:nemesis_vegetable',
    ];
    return parent::buildForm($form, $form_state);
  }

}
