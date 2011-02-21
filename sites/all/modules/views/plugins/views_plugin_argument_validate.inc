<?php
// $Id: views_plugin_argument_validate.inc,v 1.2.4.2 2010/01/28 22:44:37 dereine Exp $
/**
 * @file
 * Contains the base argument validator plugin.
 */

/**
 * @defgroup views_argument_validate_plugins Views' argument validate plugins
 * @{
 *
 * Allow specialized methods of validating arguments.
 *
 * @see hook_views_plugins
 */

/**
 * Base argument validator plugin to provide basic functionality.
 *
 * @ingroup views_argument_validate_plugins
 */
class views_plugin_argument_validate extends views_plugin {

  /**
   * Initialize this plugin with the view and the argument
   * it is linked to.
   */
  function init(&$view, &$argument, $options) {
    $this->view = &$view;
    $this->argument = &$argument;

    $this->convert_options($options);
    $this->unpack_options($this->options, $options);
  }

  /**
   * Retrieve the options when this is a new access
   * control plugin
   */
  function option_definition() { return array(); }

  /**
   * Provide the default form for setting options.
   */
  function options_form(&$form, &$form_state) { }

  /**
   * Provide the default form form for validating options
   */
  function options_validate(&$form, &$form_state) { }

  /**
   * Provide the default form form for submitting options
   */
  function options_submit(&$form, &$form_state) { }

  /**
   * Convert options from the older style.
   *
   * In Views 3, the method of storing default argument options has changed
   * and each plugin now gets its own silo. This method can be used to
   * move arguments from the old style to the new style. See
   * views_plugin_argument_default_fixed for a good example of this method.
   */
  function convert_options(&$options) { }

  /**
   * Determine if the administrator has the privileges to use this plugin
   */
  function access() { return TRUE; }

  /**
   * If we don't have access to the form but are showing it anyway, ensure that
   * the form is safe and cannot be changed from user input.
   *
   * This is only called by child objects if specified in the options_form(),
   * so it will not always be used.
   */
  function check_access(&$form, $option_name) {
    if (!$this->access()) {
      $form[$option_name]['#disabled'] = TRUE;
      $form[$option_name]['#value'] = $form[$this->option_name]['#default_value'];
      $form[$option_name]['#description'] .= ' <strong>' . t('Note: you do not have permission to modify this. If you change the default argument type, this setting will be lost and you will NOT be able to get it back.') . '</strong>';
    }
  }

  function validate_argument($arg) { return TRUE; }
}

/**
 * @}
 */

