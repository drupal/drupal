<?php

namespace Drupal\Core\Form;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Render\Element;

/**
 * Handles form errors.
 */
class FormErrorHandler implements FormErrorHandlerInterface {

  use MessengerTrait;

  /**
   * {@inheritdoc}
   */
  public function handleFormErrors(array &$form, FormStateInterface $form_state) {
    // After validation check if there are errors.
    if ($errors = $form_state->getErrors()) {
      // Display error messages for each element.
      $this->displayErrorMessages($form, $form_state);

      // Loop through and assign each element its errors.
      $this->setElementErrorsFromFormState($form, $form_state);
    }

    return $this;
  }

  /**
   * Loops through and displays all form errors.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function displayErrorMessages(array $form, FormStateInterface $form_state) {
    $errors = $form_state->getErrors();

    // Loop through all form errors and set an error message.
    foreach ($errors as $error) {
      $this->messenger()->addMessage($error, 'error');
    }
  }

  /**
   * Stores errors and a list of child element errors directly on each element.
   *
   * Grouping elements like containers, details, fieldgroups and fieldsets may
   * need error info from their child elements to be able to accessibly show
   * form error messages to a user. For example, a details element should be
   * opened when child elements have errors.
   *
   * Grouping example:
   * Assume you have a 'street' element somewhere in a form, which is displayed
   * in a details element 'address'. It might be:
   *
   * @code
   * $form['street'] = [
   *   '#type' => 'textfield',
   *   '#title' => $this->t('Street'),
   *   '#group' => 'address',
   *   '#required' => TRUE,
   * ];
   * $form['address'] = [
   *   '#type' => 'details',
   *   '#title' => $this->t('Address'),
   * ];
   * @endcode
   *
   * When submitting an empty street field, the generated error is available to
   * the different render elements like so:
   * @code
   * // The street textfield element.
   * $element = [
   *   '#errors' => {Drupal\Core\StringTranslation\TranslatableMarkup},
   *   '#children_errors' => [],
   * ];
   * // The address detail element.
   * $element = [
   *   '#errors' => null,
   *   '#children_errors' => [
   *      'street' => {Drupal\Core\StringTranslation\TranslatableMarkup}
   *   ],
   * ];
   * @endcode
   *
   * The list of child element errors of an element is an associative array. A
   * child element error is keyed with the #array_parents value of the
   * respective element. The key is formed by imploding this value with '][' as
   * glue. For example, a value ['contact_info', 'name'] becomes
   * 'contact_info][name'.
   *
   * @param array $form
   *   An associative array containing a reference to the complete structure of
   *   the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $elements
   *   An associative array containing the part of the form structure that will
   *   be processed while traversing up the tree. For recursion only; leave
   *   empty when calling this method.
   */
  protected function setElementErrorsFromFormState(array &$form, FormStateInterface $form_state, array &$elements = []) {
    // At the start of traversing up the form tree set the to be processed
    // elements to the complete form structure by reference so that we can
    // modify the original form. When processing grouped elements a reference to
    // the complete form is needed.
    if (empty($elements)) {
      $elements = &$form;
    }

    // Recurse through all element children.
    foreach (Element::children($elements) as $key) {
      if (!empty($elements[$key])) {
        // Get the child by reference so that we can update the original form.
        $child = &$elements[$key];

        // Call self to traverse up the form tree. The current element's child
        // contains the next elements to be processed.
        $this->setElementErrorsFromFormState($form, $form_state, $child);

        $children_errors = [];

        // Inherit all recorded "children errors" of the direct child.
        if (!empty($child['#children_errors'])) {
          $children_errors = $child['#children_errors'];
        }

        // Additionally store the errors of the direct child itself, keyed by
        // its parent elements structure.
        if (!empty($child['#errors'])) {
          $child_parents = implode('][', $child['#array_parents']);
          $children_errors[$child_parents] = $child['#errors'];
        }

        if (!empty($elements['#children_errors'])) {
          $elements['#children_errors'] += $children_errors;
        }
        else {
          $elements['#children_errors'] = $children_errors;
        }

        // If this direct child belongs to a group populate the grouping element
        // with the children errors.
        if (!empty($child['#group'])) {
          $parents = explode('][', $child['#group']);
          $group_element = NestedArray::getValue($form, $parents);
          if (isset($group_element['#children_errors'])) {
            $group_element['#children_errors'] = $group_element['#children_errors'] + $children_errors;
          }
          else {
            $group_element['#children_errors'] = $children_errors;
          }
          NestedArray::setValue($form, $parents, $group_element);
        }
      }
    }

    // Store the errors for this element on the element directly.
    $elements['#errors'] = $form_state->getError($elements);
  }

}
