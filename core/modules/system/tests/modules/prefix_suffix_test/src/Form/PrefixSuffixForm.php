<?php

namespace Drupal\prefix_suffix_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form used for testing Claro prefix and suffix behavior.
 */
class PrefixSuffixForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'prefix_suffix_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['ma'] = [
      '#markup' => 'I am a form!!!',
    ];
    $form['standard_prefix'] = [
      '#type' => 'textfield',
      '#field_prefix' => 'prefix',
    ];
    $form['standard_suffix'] = [
      '#type' => 'textfield',
      '#field_suffix' => 'suffix',
    ];
    $form['standard_prefix_standard_suffix'] = [
      '#type' => 'textfield',
      '#field_prefix' => 'prefix',
      '#field_suffix' => 'suffix',
    ];
    $form['long_prefix'] = [
      '#type' => 'textfield',
      '#field_prefix' => 'long prefix long prefix long prefix long prefix long prefix',
    ];
    $form['long_suffix'] = [
      '#type' => 'textfield',
      '#field_suffix' => 'long suffix long suffixlong suffixlong suffixlong suffix long suffix',
    ];
    $form['long_prefix_long_suffix'] = [
      '#type' => 'textfield',
      '#field_prefix' => 'long prefix long prefix long prefix long prefix long prefix',
      '#field_suffix' => 'long suffix long suffixlong suffixlong suffixlong suffix long suffix',
    ];
    $form['long_prefix_standard_suffix'] = [
      '#type' => 'textfield',
      '#field_prefix' => 'long prefix long prefix long prefix long prefix long prefix',
      '#field_suffix' => 'suffix',
    ];
    $form['standard_prefix_long_suffix'] = [
      '#type' => 'textfield',
      '#field_prefix' => 'prefix',
      '#field_suffix' => 'long suffix long suffixlong suffixlong suffixlong suffix long suffix',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Intentionally empty.
  }

}
