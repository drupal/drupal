<?php
// $Id: views_plugin_argument_default_php.inc,v 1.1.6.4 2010/11/05 07:20:54 dereine Exp $
/**
 * @file
 * Contains the php code argument default plugin.
 */

/**
 * Default argument plugin to provide a PHP code block.
 */
class views_plugin_argument_default_php extends views_plugin_argument_default {
  function option_definition() {
    $options = parent::option_definition();
    $options['code'] = array('default' => '');

    return $options;
  }

  function options_form(&$form, &$form_state) {
    $form['code'] = array(
      '#type' => 'textarea',
      '#title' => t('PHP argument code'),
      '#default_value' => $this->options['code'],
      '#process' => array('ctools_dependent_process'),
      '#description' => t('Enter PHP code that returns a value to use for this argument. Do not use &lt;?php ?&gt;. You must return only a single value for just this argument.'),
    );

    // Only do this if using one simple standard form gadget
    $this->check_access($form, 'code');
  }

  function convert_options(&$options) {
    if (!isset($options['code']) && isset($this->argument->options['default_argument_php'])) {
      $options['code'] = $this->argument->options['default_argument_php'];
    }
  }

  /**
   * Only let users with PHP block visibility permissions set/modify this
   * default plugin.
   */
  function access() {
    return user_access('use PHP for settings');
  }

  function get_argument() {
    // set up variables to make it easier to reference during the argument.
    $view = &$this->view;
    $argument = &$this->argument;
    ob_start();
    $result = eval($this->options['code']);
    ob_end_clean();
    return $result;
  }
}
