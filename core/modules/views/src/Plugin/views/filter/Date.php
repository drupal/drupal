<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\filter\Date.
 */

namespace Drupal\views\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;

/**
 * Filter to handle dates stored as a timestamp.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("date")
 */
class Date extends Numeric {

  protected function defineOptions() {
    $options = parent::defineOptions();

    // value is already set up properly, we're just adding our new field to it.
    $options['value']['contains']['type']['default'] = 'date';

    return $options;
  }

  /**
   * Add a type selector to the value form
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    if (!$form_state->get('exposed')) {
      $form['value']['type'] = array(
        '#type' => 'radios',
        '#title' => $this->t('Value type'),
        '#options' => array(
          'date' => $this->t('A date in any machine readable format. CCYY-MM-DD HH:MM:SS is preferred.'),
          'offset' => $this->t('An offset from the current time such as "!example1" or "!example2"', array('!example1' => '+1 day', '!example2' => '-2 hours -30 minutes')),
        ),
        '#default_value' => !empty($this->value['type']) ? $this->value['type'] : 'date',
      );
    }
    parent::valueForm($form, $form_state);
  }

  public function validateOptionsForm(&$form, FormStateInterface $form_state) {
    parent::validateOptionsForm($form, $form_state);

    if (!empty($this->options['exposed']) && $form_state->isValueEmpty(array('options', 'expose', 'required'))) {
      // Who cares what the value is if it's exposed and non-required.
      return;
    }

    $this->validateValidTime($form['value'], $form_state, $form_state->getValue(array('options', 'operator')), $form_state->getValue(array('options', 'value')));
  }

  public function validateExposed(&$form, FormStateInterface $form_state) {
    if (empty($this->options['exposed'])) {
      return;
    }

    if (empty($this->options['expose']['required'])) {
      // Who cares what the value is if it's exposed and non-required.
      return;
    }

    $value = &$form_state->getValue($this->options['expose']['identifier']);
    if (!empty($this->options['expose']['use_operator']) && !empty($this->options['expose']['operator_id'])) {
      $operator = &$form_state->getValue($this->options['expose']['operator_id']);
    }
    else {
      $operator = $this->operator;
    }

    $this->validateValidTime($this->options['expose']['identifier'], $form_state, $operator, $value);

  }

  /**
   * Validate that the time values convert to something usable.
   */
  public function validateValidTime(&$form, FormStateInterface $form_state, $operator, $value) {
    $operators = $this->operators();

    if ($operators[$operator]['values'] == 1) {
      $convert = strtotime($value['value']);
      if (!empty($form['value']) && ($convert == -1 || $convert === FALSE)) {
        $form_state->setError($form['value'], $this->t('Invalid date format.'));
      }
    }
    elseif ($operators[$operator]['values'] == 2) {
      $min = strtotime($value['min']);
      if ($min == -1 || $min === FALSE) {
        $form_state->setError($form['min'], $this->t('Invalid date format.'));
      }
      $max = strtotime($value['max']);
      if ($max == -1 || $max === FALSE) {
        $form_state->setError($form['max'], $this->t('Invalid date format.'));
      }
    }
  }

  /**
   * Validate the build group options form.
   */
  protected function buildGroupValidate($form, FormStateInterface $form_state) {
    // Special case to validate grouped date filters, this is because the
    // $group['value'] array contains the type of filter (date or offset)
    // and therefore the number of items the comparission has to be done
    // against 'one' instead of 'zero'.
    foreach ($form_state->getValue(array('options', 'group_info', 'group_items')) as $id => $group) {
      if (empty($group['remove'])) {
        // Check if the title is defined but value wasn't defined.
        if (!empty($group['title'])) {
          if ((!is_array($group['value']) && empty($group['value'])) || (is_array($group['value']) && count(array_filter($group['value'])) == 1)) {
            $form_state->setError($form['group_info']['group_items'][$id]['value'], $this->t('The value is required if title for this item is defined.'));
          }
        }

        // Check if the value is defined but title wasn't defined.
        if ((!is_array($group['value']) && !empty($group['value'])) || (is_array($group['value']) && count(array_filter($group['value'])) > 1)) {
          if (empty($group['title'])) {
            $form_state->setError($form['group_info']['group_items'][$id]['title'], $this->t('The title is required if value for this item is defined.'));
          }
        }
      }
    }
  }


  public function acceptExposedInput($input) {
    if (empty($this->options['exposed'])) {
      return TRUE;
    }

    // Store this because it will get overwritten.
    $type = $this->value['type'];
    $rc = parent::acceptExposedInput($input);

    // Don't filter if value(s) are empty.
    $operators = $this->operators();
    if (!empty($this->options['expose']['use_operator']) && !empty($this->options['expose']['operator_id'])) {
      $operator = $input[$this->options['expose']['operator_id']];
    }
    else {
      $operator = $this->operator;
    }

    if ($operators[$operator]['values'] == 1) {
      if ($this->value['value'] == '') {
        return FALSE;
      }
    }
    else {
      if ($this->value['min'] == '' || $this->value['max'] == '') {
        return FALSE;
      }
    }

    // restore what got overwritten by the parent.
    $this->value['type'] = $type;
    return $rc;
  }

  protected function opBetween($field) {
    $a = intval(strtotime($this->value['min'], 0));
    $b = intval(strtotime($this->value['max'], 0));

    if ($this->value['type'] == 'offset') {
      $a = '***CURRENT_TIME***' . sprintf('%+d', $a); // keep sign
      $b = '***CURRENT_TIME***' . sprintf('%+d', $b); // keep sign
    }
    // This is safe because we are manually scrubbing the values.
    // It is necessary to do it this way because $a and $b are formulas when using an offset.
    $operator = strtoupper($this->operator);
    $this->query->addWhereExpression($this->options['group'], "$field $operator $a AND $b");
  }

  protected function opSimple($field) {
    $value = intval(strtotime($this->value['value'], 0));
    if (!empty($this->value['type']) && $this->value['type'] == 'offset') {
      $value = '***CURRENT_TIME***' . sprintf('%+d', $value); // keep sign
    }
    // This is safe because we are manually scrubbing the value.
    // It is necessary to do it this way because $value is a formula when using an offset.
    $this->query->addWhereExpression($this->options['group'], "$field $this->operator $value");
  }

}
