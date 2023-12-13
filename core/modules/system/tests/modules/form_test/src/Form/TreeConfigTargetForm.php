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

    $form['test1'] = [
      '#type' => 'select',
      '#title' => $this->t('Test 1'),
      '#options' => [
        'option1' => $this->t('Option 1'),
        'option2' => $this->t('Option 2'),
      ],
      '#ajax' => [
        'callback' => '::updateOptions',
        'wrapper' => 'edit-test1-wrapper',
      ],
      '#prefix' => '<div id="edit-test1-wrapper">',
      '#suffix' => '</div>',
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * Updates the options of a select list.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The updated form element.
   */
  public function updateOptions(array $form, FormStateInterface $form_state) {
    $form['test1']['#options']['option1'] = $this->t('Option 1!!!');
    $form['test1']['#options'] += [
      'option3' => $this->t('Option 3'),
      'option4' => $this->t('Option 4'),
    ];
    return $form['test1'];
  }

}
