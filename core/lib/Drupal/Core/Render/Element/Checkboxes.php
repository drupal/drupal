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
 * $form['favorites']['colors'] = array(
 *   '#type' => 'checkboxes',
 *   '#options' => array('blue' => $this->t('Blue'), 'red' => $this->t('Red')),
 *   '#title' => $this->t('Which colors do you like?'),
 *   ...
 * );
 * @endcode
 *
 * Element properties may be set on single option items as follows.
 *
 * @code
 * $form['favorites']['colors']['blue']['#description'] = $this->t('The color of the sky.');
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
    $class = static::class;
    return [
      '#input' => TRUE,
      '#process' => [
        [$class, 'processCheckboxes'],
      ],
      '#pre_render' => [
        [$class, 'preRenderCompositeFormElement'],
      ],
      '#theme_wrappers' => ['checkboxes'],
    ];
  }

  /**
   * Processes a checkboxes form element.
   */
  public static function processCheckboxes(&$element, FormStateInterface $form_state, &$complete_form) {
    $value = is_array($element['#value']) ? $element['#value'] : [];
    $element['#tree'] = TRUE;
    if (count($element['#options']) > 0) {
      if (!isset($element['#default_value']) || $element['#default_value'] == 0) {
        $element['#default_value'] = [];
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

        // Only enabled checkboxes receive their values from the form
        // submission, the disabled checkboxes use their default value.
        $default_value = NULL;
        if (isset($value[$key]) || (!empty($element[$key]['#disabled']) && in_array($key, $element['#default_value'], TRUE))) {
          $default_value = $key;
        }

        $element += [$key => []];
        $element[$key] += [
          '#type' => 'checkbox',
          '#title' => $choice,
          '#return_value' => $key,
          '#default_value' => $default_value,
          '#attributes' => $element['#attributes'],
          '#ajax' => $element['#ajax'] ?? NULL,
          // Errors should only be shown on the parent checkboxes element.
          '#error_no_message' => TRUE,
          '#weight' => $weight,
        ];
      }
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    if ($input === FALSE) {
      $value = [];
      $element += ['#default_value' => []];
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

      // Because the disabled checkboxes don't receive their input from the
      // form submission, we use their default value.
      if (!empty($element['#default_value'])) {
        foreach ($element['#default_value'] as $key) {
          if (!empty($element[$key]['#disabled'])) {
            $input[$key] = $key;
          }
        }
      }

      return array_combine($input, $input);
    }
    else {
      return [];
    }
  }

  /**
   * Determines which checkboxes were checked when a form is submitted.
   *
   * @param array $input
   *   An array returned by the FormAPI for a set of checkboxes.
   *
   * @return array
   *   An array of keys that were checked.
   */
  public static function getCheckedCheckboxes(array $input) {
    // Browsers do not include unchecked options in a form submission. The
    // FormAPI tries to normalize this to keep checkboxes consistent with other
    // form elements. Checkboxes show up as an array in the form of option_id =>
    // option_id|0, where integer 0 is an unchecked option.
    //
    // @see \Drupal\Core\Render\Element\Checkboxes::valueCallback()
    // @see https://www.w3.org/TR/html401/interact/forms.html#checkbox
    $checked = array_filter($input, function ($value) {
      return $value !== 0;
    });
    return array_keys($checked);
  }

  /**
   * Determines if all checkboxes in a set are unchecked.
   *
   * @param array $input
   *   An array returned by the FormAPI for a set of checkboxes.
   *
   * @return bool
   *   TRUE if all options are unchecked. FALSE otherwise.
   */
  public static function detectEmptyCheckboxes(array $input) {
    return empty(static::getCheckedCheckboxes($input));
  }

}
