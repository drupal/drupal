<?php

namespace Drupal\Core\Render\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 * Provides a form element for a drop-down menu or scrolling selection box.
 *
 * Properties:
 * - #options: An associative array, where the keys are the values for each
 *   option, and the values are the option labels to be shown in the drop-down
 *   list. If a value is an array, it will be rendered similarly, but as an
 *   optgroup. The key of the sub-array will be used as the label for the
 *   optgroup. Nesting optgroups is not allowed.
 * - #empty_option: The label that will be displayed to denote no selection.
 * - #empty_value: The value of the option that is used to denote no selection.
 *
 * Usage example:
 * @code
 * $form['example_select'] = [
 *   '#type' => 'select',
 *   '#title' => $this->t('Select element'),
 *   '#options' => [
 *     '1' => $this->t('One'),
 *     '2' => [
 *       '2.1' => $this->t('Two point one'),
 *       '2.2' => $this->t('Two point two'),
 *     ],
 *     '3' => $this->t('Three'),
 *   ],
 * ];
 * @endcode
 *
 * @FormElement("select")
 */
class Select extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return array(
      '#input' => TRUE,
      '#multiple' => FALSE,
      '#process' => array(
        array($class, 'processSelect'),
        array($class, 'processAjaxForm'),
      ),
      '#pre_render' => array(
        array($class, 'preRenderSelect'),
      ),
      '#theme' => 'select',
      '#theme_wrappers' => array('form_element'),
      '#options' => array(),
    );
  }

  /**
   * Processes a select list form element.
   *
   * This process callback is mandatory for select fields, since all user agents
   * automatically preselect the first available option of single (non-multiple)
   * select lists.
   *
   * @param array $element
   *   The form element to process. Properties used:
   *   - #multiple: (optional) Indicates whether one or more options can be
   *     selected. Defaults to FALSE.
   *   - #default_value: Must be NULL or not set in case there is no value for the
   *     element yet, in which case a first default option is inserted by default.
   *     Whether this first option is a valid option depends on whether the field
   *     is #required or not.
   *   - #required: (optional) Whether the user needs to select an option (TRUE)
   *     or not (FALSE). Defaults to FALSE.
   *   - #empty_option: (optional) The label to show for the first default option.
   *     By default, the label is automatically set to "- Select -" for a required
   *     field and "- None -" for an optional field.
   *   - #empty_value: (optional) The value for the first default option, which is
   *     used to determine whether the user submitted a value or not.
   *     - If #required is TRUE, this defaults to '' (an empty string).
   *     - If #required is not TRUE and this value isn't set, then no extra option
   *       is added to the select control, leaving the control in a slightly
   *       illogical state, because there's no way for the user to select nothing,
   *       since all user agents automatically preselect the first available
   *       option. But people are used to this being the behavior of select
   *       controls.
   *       @todo Address the above issue in Drupal 8.
   *     - If #required is not TRUE and this value is set (most commonly to an
   *       empty string), then an extra option (see #empty_option above)
   *       representing a "non-selection" is added with this as its value.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The processed element.
   *
   * @see _form_validate()
   */
  public static function processSelect(&$element, FormStateInterface $form_state, &$complete_form) {
    // #multiple select fields need a special #name.
    if ($element['#multiple']) {
      $element['#attributes']['multiple'] = 'multiple';
      $element['#attributes']['name'] = $element['#name'] . '[]';
    }
    // A non-#multiple select needs special handling to prevent user agents from
    // preselecting the first option without intention. #multiple select lists do
    // not get an empty option, as it would not make sense, user interface-wise.
    else {
      // If the element is set to #required through #states, override the
      // element's #required setting.
      $required = isset($element['#states']['required']) ? TRUE : $element['#required'];
      // If the element is required and there is no #default_value, then add an
      // empty option that will fail validation, so that the user is required to
      // make a choice. Also, if there's a value for #empty_value or
      // #empty_option, then add an option that represents emptiness.
      if (($required && !isset($element['#default_value'])) || isset($element['#empty_value']) || isset($element['#empty_option'])) {
        $element += array(
          '#empty_value' => '',
          '#empty_option' => $required ? t('- Select -') : t('- None -'),
        );
        // The empty option is prepended to #options and purposively not merged
        // to prevent another option in #options mistakenly using the same value
        // as #empty_value.
        $empty_option = array($element['#empty_value'] => $element['#empty_option']);
        $element['#options'] = $empty_option + $element['#options'];
      }
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    if ($input !== FALSE) {
      if (isset($element['#multiple']) && $element['#multiple']) {
        // If an enabled multi-select submits NULL, it means all items are
        // unselected. A disabled multi-select always submits NULL, and the
        // default value should be used.
        if (empty($element['#disabled'])) {
          return (is_array($input)) ? array_combine($input, $input) : array();
        }
        else {
          return (isset($element['#default_value']) && is_array($element['#default_value'])) ? $element['#default_value'] : array();
        }
      }
      // Non-multiple select elements may have an empty option prepended to them
      // (see \Drupal\Core\Render\Element\Select::processSelect()). When this
      // occurs, usually #empty_value is an empty string, but some forms set
      // #empty_value to integer 0 or some other non-string constant. PHP
      // receives all submitted form input as strings, but if the empty option
      // is selected, set the value to match the empty value exactly.
      elseif (isset($element['#empty_value']) && $input === (string) $element['#empty_value']) {
        return $element['#empty_value'];
      }
      else {
        return $input;
      }
    }
  }

  /**
   * Prepares a select render element.
   */
  public static function preRenderSelect($element) {
    Element::setAttributes($element, array('id', 'name', 'size'));
    static::setAttributes($element, array('form-select'));
    return $element;
  }

}
