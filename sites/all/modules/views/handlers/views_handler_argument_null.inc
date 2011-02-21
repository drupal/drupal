<?php
// $Id: views_handler_argument_null.inc,v 1.1 2008/09/03 19:21:28 merlinofchaos Exp $
/**
 * Argument handler that ignores the argument.
 */
class views_handler_argument_null extends views_handler_argument {
  function option_definition() {
    $options = parent::option_definition();
    $options['must_not_be'] = array('default' => FALSE);
    return $options;
  }

  /**
   * Override options_form() so that only the relevant options
   * are displayed to the user.
   */
  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
    $form['must_not_be'] = array(
      '#type' => 'checkbox',
      '#title' => t('Fail basic validation if any argument is given'),
      '#default_value' => !empty($this->options['must_not_be']),
      '#description' => t('By checking this field, you can use this to make sure views with more arguments than necessary fail validation.'),
    );

    unset($form['wildcard']);
    unset($form['wildcard_substitution']);
  }

  /**
   * Override default_actions() to remove actions that don't
   * make sense for a null argument.
   */
  function default_actions($which = NULL) {
    if ($which) {
      if (in_array($which, array('ignore', 'not found', 'empty', 'default'))) {
        return parent::default_actions($which);
      }
      return;
    }
    $actions = parent::default_actions();
    unset($actions['summary asc']);
    unset($actions['summary desc']);
    return $actions;
  }

  function validate_argument_basic($arg) {
    if (!empty($this->options['must_not_be'])) {
      return !isset($arg);
    }

    return parent::validate_argument_basic($arg);
  }

  /**
   * Override the behavior of query() to prevent the query
   * from being changed in any way.
   */
  function query() {}
}
