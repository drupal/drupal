<?php

/**
 * @file
 * Contains \Drupal\form_test\Form\FormTestFormStateValuesCleanForm.
 */

namespace Drupal\form_test\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form builder for \Drupal\Core\Form\FormState::cleanValues() test.
 */
class FormTestFormStateValuesCleanForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'form_test_form_state_clean_values_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Build an example form containing multiple submit and button elements; not
    // only on the top-level.
    $form = array('#tree' => TRUE);
    $form['foo'] = array('#type' => 'submit', '#value' => t('Submit'));
    $form['bar'] = array('#type' => 'submit', '#value' => t('Submit'));
    $form['beer'] = array('#type' => 'value', '#value' => 1000);
    $form['baz']['foo'] = array('#type' => 'button', '#value' => t('Submit'));
    $form['baz']['baz'] = array('#type' => 'submit', '#value' => t('Submit'));
    $form['baz']['beer'] = array('#type' => 'value', '#value' => 2000);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->cleanValues();
    // This won't have a proper JSON header, but Drupal doesn't check for that
    // anyway so this is fine until it's replaced with a JsonResponse.
    print Json::encode($form_state->getValues());
    exit;
  }

}
