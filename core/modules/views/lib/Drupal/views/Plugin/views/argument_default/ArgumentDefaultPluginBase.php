<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\argument_default\ArgumentDefaultPluginBase.
 */

namespace Drupal\views\Plugin\views\argument_default;

use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\PluginBase;

/**
 * @defgroup views_argument_default_plugins Views argument default plugins
 * @{
 * Allow specialized methods of filling in arguments when they aren't provided.
 */

/**
 * The fixed argument default handler; also used as the base.
 */
abstract class ArgumentDefaultPluginBase extends PluginBase {

  /**
   * Return the default argument.
   *
   * This needs to be overridden by every default argument handler to properly do what is needed.
   */
  function get_argument() { }

  /**
   * Initialize this plugin with the view and the argument
   * it is linked to.
   */
  public function init(ViewExecutable $view, &$argument, $options) {
    $this->setOptionDefaults($this->options, $this->defineOptions());
    $this->view = &$view;
    $this->argument = &$argument;

    $this->unpackOptions($this->options, $options);
  }

  /**
   * Retrieve the options when this is a new access
   * control plugin
   */
  protected function defineOptions() { return array(); }

  /**
   * Provide the default form for setting options.
   */
  public function buildOptionsForm(&$form, &$form_state) { }

  /**
   * Provide the default form form for validating options
   */
  public function validateOptionsForm(&$form, &$form_state) { }

  /**
   * Provide the default form form for submitting options
   */
  public function submitOptionsForm(&$form, &$form_state, &$options = array()) { }

  /**
   * Determine if the administrator has the privileges to use this
   * plugin
   */
  public function access() { return TRUE; }

  /**
   * If we don't have access to the form but are showing it anyway, ensure that
   * the form is safe and cannot be changed from user input.
   *
   * This is only called by child objects if specified in the buildOptionsForm(),
   * so it will not always be used.
   */
  function check_access(&$form, $option_name) {
    if (!$this->access()) {
      $form[$option_name]['#disabled'] = TRUE;
      $form[$option_name]['#value'] = $form[$this->option_name]['#default_value'];
      $form[$option_name]['#description'] .= ' <strong>' . t('Note: you do not have permission to modify this. If you change the default filter type, this setting will be lost and you will NOT be able to get it back.') . '</strong>';
    }
  }

}

/**
 * @}
 */
