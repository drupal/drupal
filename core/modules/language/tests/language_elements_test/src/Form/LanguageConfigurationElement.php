<?php
/**
 * @file
 * Contains \Drupal\language_elements_test\Form\LanguageConfigurationElement.
 */

namespace Drupal\language_elements_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * A form containing a language configuration element.
 */
class LanguageConfigurationElement extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'language_elements_configuration_element';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $conf = language_get_default_configuration('some_custom_type', 'some_bundle');

    $form['lang_configuration'] = array(
      '#type' => 'language_configuration',
      '#entity_information' => array(
        'entity_type' => 'some_custom_type',
        'bundle' => 'some_bundle',
      ),
      '#default_value' => $conf,
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => 'Save',
    );
    $form['#submit'][] = 'language_configuration_element_submit';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }
}
