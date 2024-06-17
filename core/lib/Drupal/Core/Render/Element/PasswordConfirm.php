<?php

namespace Drupal\Core\Render\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Attribute\FormElement;

/**
 * Provides a form element for double-input of passwords.
 *
 * Formats as a pair of password fields, which do not validate unless the two
 * entered passwords match.
 *
 * Properties:
 * - #size: The size of the input element in characters.
 *
 * Usage example:
 * @code
 * $form['pass'] = [
 *   '#type' => 'password_confirm',
 *   '#title' => $this->t('Password'),
 *   '#size' => 25,
 * ];
 * @endcode
 *
 * @see \Drupal\Core\Render\Element\Password
 */
#[FormElement('password_confirm')]
class PasswordConfirm extends FormElementBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = static::class;
    return [
      '#input' => TRUE,
      '#markup' => '',
      '#process' => [
        [$class, 'processPasswordConfirm'],
      ],
      '#theme_wrappers' => ['form_element'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    if ($input === FALSE) {
      $element += ['#default_value' => []];
      return $element['#default_value'] + ['pass1' => '', 'pass2' => ''];
    }
    $value = ['pass1' => '', 'pass2' => ''];
    // Throw out all invalid array keys; we only allow pass1 and pass2.
    foreach ($value as $allowed_key => $default) {
      // These should be strings, but allow other scalars since they might be
      // valid input in programmatic form submissions. Any nested array values
      // are ignored.
      if (isset($input[$allowed_key]) && is_scalar($input[$allowed_key])) {
        $value[$allowed_key] = (string) $input[$allowed_key];
      }
    }
    return $value;
  }

  /**
   * Expand a password_confirm field into two text boxes.
   */
  public static function processPasswordConfirm(&$element, FormStateInterface $form_state, &$complete_form) {
    $element['pass1'] = [
      '#type' => 'password',
      '#title' => t('Password'),
      '#value' => empty($element['#value']) ? NULL : $element['#value']['pass1'],
      '#required' => $element['#required'],
      '#attributes' => [
        'class' => ['password-field', 'js-password-field'],
        'autocomplete' => ['new-password'],
      ],
      '#error_no_message' => TRUE,
    ];
    $element['pass2'] = [
      '#type' => 'password',
      '#title' => t('Confirm password'),
      '#value' => empty($element['#value']) ? NULL : $element['#value']['pass2'],
      '#required' => $element['#required'],
      '#attributes' => [
        'class' => ['password-confirm', 'js-password-confirm'],
        'autocomplete' => ['new-password'],
      ],
      '#error_no_message' => TRUE,
    ];
    $element['#element_validate'] = [[static::class, 'validatePasswordConfirm']];
    $element['#tree'] = TRUE;

    if (isset($element['#size'])) {
      $element['pass1']['#size'] = $element['pass2']['#size'] = $element['#size'];
    }

    return $element;
  }

  /**
   * Validates a password_confirm element.
   */
  public static function validatePasswordConfirm(&$element, FormStateInterface $form_state, &$complete_form) {
    $pass1 = trim($element['pass1']['#value']);
    $pass2 = trim($element['pass2']['#value']);
    if (strlen($pass1) > 0 || strlen($pass2) > 0) {
      if (strcmp($pass1, $pass2)) {
        $form_state->setError($element, t('The specified passwords do not match.'));
      }
    }
    elseif ($element['#required'] && $form_state->getUserInput()) {
      $form_state->setError($element, t('Password field is required.'));
    }

    // Password field must be converted from a two-element array into a single
    // string regardless of validation results.
    $form_state->setValueForElement($element['pass1'], NULL);
    $form_state->setValueForElement($element['pass2'], NULL);
    $form_state->setValueForElement($element, $pass1);

    return $element;
  }

}
