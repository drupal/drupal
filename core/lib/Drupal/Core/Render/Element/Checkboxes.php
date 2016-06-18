<?php

namespace Drupal\Core\Render\Element;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form element for a set of checkboxes.
 *
 * Properties:
 * - #options: An associative array whose keys are the values returned for each
 *   checkbox, and whose values are the labels next to each checkbox. The
 *   #options array cannot have a 0 key, as it would not be possible to discern
 *   checked and unchecked states.
 *
 * Usage example:
 * @code
 * $form['high_school']['tests_taken'] = array(
 *   '#type' => 'checkboxes',
 *   '#options' => array('SAT' => $this->t('SAT'), 'ACT' => $this->t('ACT')),
 *   '#title' => $this->t('What standardized tests did you take?'),
 *   ...
 * );
 * @endcode
 *
 * @see \Drupal\Core\Render\Element\Radios
 * @see \Drupal\Core\Render\Element\Checkbox
 *
 * @FormElement("checkboxes")
 */
class Checkboxes extends FormElement {

  use CompositeFormElementTrait;

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return array(
      '#input' => TRUE,
      '#process' => array(
        array($class, 'processCheckboxes'),
      ),
      '#pre_render' => array(
        array($class, 'preRenderCompositeFormElement'),
      ),
      '#theme_wrappers' => array('checkboxes'),
    );
  }

  /**
   * Processes a checkboxes form element.
   */
  public static function processCheckboxes(&$element, FormStateInterface $form_state, &$complete_form) {
    $value = is_array($element['#value']) ? $element['#value'] : array();
    $element['#tree'] = TRUE;
    if (count($element['#options']) > 0) {
      if (!isset($element['#default_value']) || $element['#default_value'] == 0) {
        $element['#default_value'] = array();
      }
      $weight = 0;
      foreach ($element['#options'] as $key => $choice) {
        // Integer 0 is not a valid #return_value, so use '0' instead.
        // @see \Drupal\Core\Render\Element\Checkbox::valueCallback().
        // @todo For Drupal 8, cast all integer keys to strings for consistency
        //   with \Drupal\Core\Render\Element\Radios::processRadios().
        if ($key === 0) {
          $key = '0';
        }
        // Maintain order of options as defined in #options, in case the element
        // defines custom option sub-elements, but does not define all option
        // sub-elements.
        $weight += 0.001;

        $element += array($key => array());
        $element[$key] += array(
          '#type' => 'checkbox',
          '#title' => $choice,
          '#return_value' => $key,
          '#default_value' => isset($value[$key]) ? $key : NULL,
          '#attributes' => $element['#attributes'],
          '#ajax' => isset($element['#ajax']) ? $element['#ajax'] : NULL,
          // Errors should only be shown on the parent checkboxes element.
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
    if ($input === FALSE) {
      $value = array();
      $element += array('#default_value' => array());
      foreach ($element['#default_value'] as $key) {
        $value[$key] = $key;
      }
      return $value;
    }
    elseif (is_array($input)) {
      // Programmatic form submissions use NULL to indicate that a checkbox
      // should be unchecked. We therefore remove all NULL elements from the
      // array before constructing the return value, to simulate the behavior
      // of web browsers (which do not send unchecked checkboxes to the server
      // at all). This will not affect non-programmatic form submissions, since
      // all values in \Drupal::request()->request are strings.
      // @see \Drupal\Core\Form\FormBuilderInterface::submitForm()
      foreach ($input as $key => $value) {
        if (!isset($value)) {
          unset($input[$key]);
        }
      }
      return array_combine($input, $input);
    }
    else {
      return array();
    }
  }

}
