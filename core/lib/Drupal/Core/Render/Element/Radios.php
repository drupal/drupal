<?php

namespace Drupal\Core\Render\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html as HtmlUtility;

/**
 * Provides a form element for a set of radio buttons.
 *
 * Properties:
 * - #options: An associative array, where the keys are the returned values for
 *   each radio button, and the values are the labels next to each radio button.
 *
 * Usage example:
 * @code
 * $form['settings']['active'] = array(
 *   '#type' => 'radios',
 *   '#title' => t('Poll status'),
 *   '#default_value' => 1,
 *   '#options' => array(0 => t('Closed'), 1 => t('Active')),
 * );
 * @endcode
 *
 * @see \Drupal\Core\Render\Element\Checkboxes
 * @see \Drupal\Core\Render\Element\Radio
 * @see \Drupal\Core\Render\Element\Select
 *
 * @FormElement("radios")
 */
class Radios extends FormElement {

  use CompositeFormElementTrait;

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return array(
      '#input' => TRUE,
      '#process' => array(
        array($class, 'processRadios'),
      ),
      '#theme_wrappers' => array('radios'),
      '#pre_render' => array(
        array($class, 'preRenderCompositeFormElement'),
      ),
    );
  }

  /**
   * Expands a radios element into individual radio elements.
   */
  public static function processRadios(&$element, FormStateInterface $form_state, &$complete_form) {
    if (count($element['#options']) > 0) {
      $weight = 0;
      foreach ($element['#options'] as $key => $choice) {
        // Maintain order of options as defined in #options, in case the element
        // defines custom option sub-elements, but does not define all option
        // sub-elements.
        $weight += 0.001;

        $element += array($key => array());
        // Generate the parents as the autogenerator does, so we will have a
        // unique id for each radio button.
        $parents_for_id = array_merge($element['#parents'], array($key));
        $element[$key] += array(
          '#type' => 'radio',
          '#title' => $choice,
          // The key is sanitized in Drupal\Core\Template\Attribute during output
          // from the theme function.
          '#return_value' => $key,
          // Use default or FALSE. A value of FALSE means that the radio button is
          // not 'checked'.
          '#default_value' => isset($element['#default_value']) ? $element['#default_value'] : FALSE,
          '#attributes' => $element['#attributes'],
          '#parents' => $element['#parents'],
          '#id' => HtmlUtility::getUniqueId('edit-' . implode('-', $parents_for_id)),
          '#ajax' => isset($element['#ajax']) ? $element['#ajax'] : NULL,
          // Errors should only be shown on the parent radios element.
          '#error_no_message' => TRUE,
          '#weight' => $weight,
        );
      }
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    if ($input !== FALSE) {
      // When there's user input (including NULL), return it as the value.
      // However, if NULL is submitted, FormBuilder::handleInputElement() will
      // apply the default value, and we want that validated against #options
      // unless it's empty. (An empty #default_value, such as NULL or FALSE, can
      // be used to indicate that no radio button is selected by default.)
      if (!isset($input) && !empty($element['#default_value'])) {
        $element['#needs_validation'] = TRUE;
      }
      return $input;
    }
    else {
      // For default value handling, simply return #default_value. Additionally,
      // for a NULL default value, set #has_garbage_value to prevent
      // FormBuilder::handleInputElement() converting the NULL to an empty
      // string, so that code can distinguish between nothing selected and the
      // selection of a radio button whose value is an empty string.
      $value = isset($element['#default_value']) ? $element['#default_value'] : NULL;
      if (!isset($value)) {
        $element['#has_garbage_value'] = TRUE;
      }
      return $value;
    }
  }

}
