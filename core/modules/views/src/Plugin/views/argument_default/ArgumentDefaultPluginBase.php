<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\views\argument_default\ArgumentDefaultPluginBase.
 */

namespace Drupal\views\Plugin\views\argument_default;

use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\argument\ArgumentPluginBase;
use Drupal\views\Plugin\views\PluginBase;

/**
 * @defgroup views_argument_default_plugins Views argument default plugins
 * @{
 * Plugins for argument defaults in Views.
 *
 * Argument default plugins provide default values for contextual filters.
 * This is useful for blocks and other display types lacking a natural argument
 * input. Examples are plugins to extract node and user IDs from the URL.
 *
 * Argument default plugins extend
 * \Drupal\views\Plugin\views\argument_default\ArgumentDefaultPluginBase. They
 * must be annotated with \Drupal\Views\Annotation\ViewsArgumentDefault
 * annotation, and they must be in namespace directory
 * Plugin\views\argument_default.
 *
 * @ingroup views_plugins
 * @see plugin_api
 */

/**
 * The fixed argument default handler; also used as the base.
 */
abstract class ArgumentDefaultPluginBase extends PluginBase {

  /**
   * The argument handler instance associated with this plugin.
   *
   * @var \Drupal\views\Plugin\views\argument\ArgumentPluginBase
   */
  protected $argument;

  /**
   * Return the default argument.
   *
   * This needs to be overridden by every default argument handler to properly do what is needed.
   */
  public function getArgument() { }

  /**
   * Sets the parent argument this plugin is associated with.
   *
   * @param \Drupal\views\Plugin\views\argument\ArgumentPluginBase $argument
   *   The parent argument to set.
   */
  public function setArgument(ArgumentPluginBase $argument) {
    $this->argument = $argument;
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
  protected function checkAccess(&$form, $option_name) {
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
