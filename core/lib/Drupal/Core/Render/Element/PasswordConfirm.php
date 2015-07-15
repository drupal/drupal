<?php

/**
 * @file
 * Contains \Drupal\Core\Render\Element\PasswordConfirm.
 */

namespace Drupal\Core\Render\Element;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form element for double-input of passwords.
 *
 * Formats as a pair of password fields, which do not validate unless the two
 * entered passwords match.
 *
 * Usage example:
 * @code
 * $form['pass'] = array(
 *   '#type' => 'password_confirm',
 *   '#title' => t('Password'),
 *   '#size' => 25,
 * );
 * @endcode
 *
 * @see \Drupal\Core\Render\Element\Password
 *
 * @FormElement("password_confirm")
 */
class PasswordConfirm extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return array(
      '#input' => TRUE,
      '#markup' => '',
      '#process' => array(
        array($class, 'processPasswordConfirm'),
      ),
      '#theme_wrappers' => array('form_element'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    if ($input === FALSE) {
      $element += array('#default_value' => array());
      return $element['#default_value'] + array('pass1' => '', 'pass2' => '');
    }
  }

  /**
   * Expand a password_confirm field into two text boxes.
   */
  public static function processPasswordConfirm(&$element, FormStateInterface $form_state, &$complete_form) {
    $element['pass1'] =  array(
      '#type' => 'password',
      '#title' => t('Password'),
      '#value' => empty($element['#value']) ? NULL : $element['#value']['pass1'],
      '#required' => $element['#required'],
      '#attributes' => array('class' => array('password-field', 'js-password-field')),
      '#error_no_message' => TRUE,
    );
    $element['pass2'] =  array(
      '#type' => 'password',
      '#title' => t('Confirm password'),
      '#value' => empty($element['#value']) ? NULL : $element['#value']['pass2'],
      '#required' => $element['#required'],
      '#attributes' => array('class' => array('password-confirm', 'js-password-confirm')),
      '#error_no_message' => TRUE,
    );
    $element['#element_validate'] = array(array(get_called_class(), 'validatePasswordConfirm'));
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
    if (!empty($pass1) || !empty($pass2)) {
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
