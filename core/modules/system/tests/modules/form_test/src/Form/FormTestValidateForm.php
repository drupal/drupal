<?php

declare(strict_types=1);

namespace Drupal\form_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\form_test\Callbacks;

/**
 * Form builder for testing \Drupal\Core\Form\FormValidatorInterface::validateForm().
 *
 * Serves for testing form processing and alterations by form validation
 * handlers, especially for the case of a validation error:
 * - $form_state->setValueForElement() should be able to alter submitted values
 *   in $form_state->getValues() without affecting the form element.
 * - #element_validate handlers should be able to alter the $element in the form
 *   structure and the alterations should be contained in the rebuilt form.
 * - #validate handlers should be able to alter the $form and the alterations
 *   should be contained in the rebuilt form.
 *
 * @internal
 */
class FormTestValidateForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'form_test_validate_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $object = new Callbacks();

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => 'Name',
      '#default_value' => '',
      '#element_validate' => [[$object, 'validateName']],
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'Save',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('name') == 'validate') {
      // Alter the form element.
      $form['name']['#value'] = '#value changed by #validate';
      // Alter the submitted value in $form_state.
      $form_state->setValueForElement($form['name'], 'value changed by setValueForElement() in #validate');
      // Output the element's value from $form_state.
      $this->messenger()->addStatus($this->t('@label value: @value', ['@label' => $form['name']['#title'], '@value' => $form_state->getValue('name')]));

      // Trigger a form validation error to see our changes.
      $form_state->setErrorByName('');

      // To simplify this test, enable form caching and use form storage to
      // remember our alteration.
      $form_state->setCached();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
