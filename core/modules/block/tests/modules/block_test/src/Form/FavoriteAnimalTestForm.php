<?php

namespace Drupal\block_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form that performs favorite animal test.
 *
 * @internal
 */
class FavoriteAnimalTestForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'block_test_form_favorite_animal_test';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['favorite_animal'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Your favorite animal.')
    ];

    $form['submit_animal'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit your chosen animal'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    drupal_set_message($this->t('Your favorite animal is: @favorite_animal', ['@favorite_animal' => $form['favorite_animal']['#value']]));
  }

}
