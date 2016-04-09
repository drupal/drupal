<?php

namespace Drupal\language_elements_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * A form containing a language select element.
 */
class LanguageConfigurationElementTest extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'language_elements_configuration_element_test';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['langcode'] = array(
      '#title' => t('Language select'),
      '#type' => 'language_select',
      '#default_value' => language_get_default_langcode('entity_test', 'some_bundle'),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }
}
