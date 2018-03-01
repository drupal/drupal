<?php

namespace Drupal\Core\Render\Element;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form element for input of a weight.
 *
 * Weights are integers used to indicate ordering, with larger numbers later in
 * the order.
 *
 * Properties:
 * - #delta: The range of possible weight values used. A delta of 10 would
 *   indicate possible weight values between -10 and 10.
 *
 * Usage example:
 * @code
 * $form['weight'] = array(
 *   '#type' => 'weight',
 *   '#title' => $this->t('Weight'),
 *   '#default_value' => $edit['weight'],
 *   '#delta' => 10,
 * );
 * @endcode
 *
 * @FormElement("weight")
 */
class Weight extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#delta' => 10,
      '#default_value' => 0,
      '#process' => [
        [$class, 'processWeight'],
        [$class, 'processAjaxForm'],
      ],
    ];
  }

  /**
   * Expands a weight element into a select element.
   */
  public static function processWeight(&$element, FormStateInterface $form_state, &$complete_form) {
    $element['#is_weight'] = TRUE;

    $element_info_manager = \Drupal::service('element_info');
    // If the number of options is small enough, use a select field.
    $max_elements = \Drupal::config('system.site')->get('weight_select_max');
    if ($element['#delta'] <= $max_elements) {
      $element['#type'] = 'select';
      $weights = [];
      for ($n = (-1 * $element['#delta']); $n <= $element['#delta']; $n++) {
        $weights[$n] = $n;
      }
      $default_value = (int) $element['#default_value'];
      if (!isset($weights[$default_value])) {
        $weights[$default_value] = $default_value;
        ksort($weights);
      }
      $element['#options'] = $weights;
      $element += $element_info_manager->getInfo('select');
    }
    // Otherwise, use a text field.
    else {
      $element['#type'] = 'number';
      // Use a field big enough to fit most weights.
      $element['#size'] = 10;
      $element += $element_info_manager->getInfo('number');
    }

    return $element;
  }

}
