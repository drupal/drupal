<?php

namespace Drupal\Core\Render\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Attribute\FormElement;

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
 * $form['weight'] = [
 *   '#type' => 'weight',
 *   '#title' => $this->t('Weight'),
 *   '#default_value' => $edit['weight'],
 *   '#delta' => 10,
 * ];
 * @endcode
 */
#[FormElement('weight')]
class Weight extends FormElementBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = static::class;
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
   * Expands a weight element into a select/number element.
   */
  public static function processWeight(&$element, FormStateInterface $form_state, &$complete_form) {
    // If the number of options is small enough, use a select field. Otherwise,
    // use a number field.
    $type = $element['#delta'] <= \Drupal::config('system.site')->get('weight_select_max') ? 'select' : 'number';
    $element = array_merge($element, \Drupal::service('element_info')->getInfo($type));
    $element['#is_weight'] = TRUE;

    if ($type === 'select') {
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
    }
    else {
      // Use a field big enough to fit most weights.
      $element['#size'] = 10;
    }

    return $element;
  }

}
