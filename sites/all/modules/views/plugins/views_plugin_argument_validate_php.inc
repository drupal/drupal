<?php
// $Id: views_plugin_argument_validate_php.inc,v 1.1.6.2 2010/01/28 22:44:37 dereine Exp $
/**
 * @file
 * Contains the php code argument validator plugin.
 */

/**
 * Provide PHP code to validate whether or not an argument is ok.
 *
 * @ingroup views_argument_validate_plugins
 */
class views_plugin_argument_validate_php extends views_plugin_argument_validate {
  function option_definition() {
    $options = parent::option_definition();
    $options['code'] = array('default' => '');

    return $options;
  }

  function options_form(&$form, &$form_state) {
    $form['code'] = array(
      '#type' => 'textarea',
      '#title' => t('PHP validate code'),
      '#default_value' => $this->options['code'],
      '#description' => t('Enter PHP code that returns TRUE or FALSE. No return is the same as FALSE, so be SURE to return something if you do not want to declare the argument invalid. Do not use &lt;?php ?&gt;. The argument to validate will be "$argument" and the view will be "$view". You may change the argument by setting "$handler->argument". You may change the title used for substitutions for this argument by setting "$argument->validated_title".'),
    );

    $this->check_access($form, 'code');
  }

  /**
   * Only let users with PHP block visibility permissions set/modify this
   * validate plugin.
   */
  function access() {
    return user_access('use PHP for settings');
  }

  function convert_options(&$options) {
    if (!isset($options['code']) && isset($this->argument->options['validate_argument_php'])) {
      $options['code'] = $this->argument->options['validate_argument_php'];
    }
  }

  function validate_argument($argument) {
    // set up variables to make it easier to reference during the argument.
    $view = &$this->view;
    $handler = &$this->argument;

    ob_start();
    $result = eval($this->options['code']);
    ob_end_clean();
    return $result;
  }
}

