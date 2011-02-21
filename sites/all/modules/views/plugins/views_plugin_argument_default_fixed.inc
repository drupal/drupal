<?php
// $Id: views_plugin_argument_default_fixed.inc,v 1.1.4.2 2010/01/28 22:45:04 dereine Exp $
/**
 * @file
 * Contains the fixed argument default plugin.
 */

/**
 * The fixed argument default handler.
 */
class views_plugin_argument_default_fixed extends views_plugin_argument_default {
  function option_definition() {
    $options = parent::option_definition();
    $options['argument'] = array('default' => '');

    return $options;
  }

  function options_form(&$form, &$form_state) {
    $form['argument'] = array(
      '#type' => 'textfield',
      '#title' => t('Default argument'),
      '#default_value' => $this->options['argument'],
    );
  }

  /**
   * Return the default argument.
   */
  function get_argument() {
    return $this->options['argument'];
  }

  function convert_options(&$options) {
    if (!isset($options['argument']) && isset($this->argument->options['default_argument_fixed'])) {
      $options['argument'] = $this->argument->options['default_argument_fixed'];
    }
  }
}

/**
 * @}
 */

