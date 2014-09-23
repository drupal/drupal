<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\views\argument_validator\ArgumentValidatorPluginBase.
 */

namespace Drupal\views\Plugin\views\argument_validator;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\argument\ArgumentPluginBase;
use Drupal\views\Plugin\views\PluginBase;

/**
 * @defgroup views_argument_validate_plugins Views argument validate plugins
 * @{
 * Plugins for validating views contextual filters.
 *
 * Views argument validator plugins validate arguments (contextual filters) on
 * views. They can ensure arguments are valid, and even do transformations on
 * the arguments. They can also provide replacement patterns for the view title.
 * For example, the 'content' validator verifies verifies that the argument
 * value corresponds to a node, loads that node, and provides the node title
 * as a replacement pattern for the view title.
 *
 * Argument validator plugins extend
 * \Drupal\views\Plugin\views\argument_validator\ArgumentValidatorPluginBase.
 * They must be annotated with
 * \Drupal\views\Annotation\ViewsArgumentValidator annotation, and they
 * must be in namespace directory Plugin\views\argument_validator.
 *
 * @ingroup views_plugins
 * @see plugin_api
 */

/**
 * Base argument validator plugin to provide basic functionality.
 */
abstract class ArgumentValidatorPluginBase extends PluginBase {

  /**
   * The argument handler instance associated with this plugin.
   *
   * @var \Drupal\views\Plugin\views\argument\ArgumentPluginBase
   */
  protected $argument;

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
  public function buildOptionsForm(&$form, FormStateInterface $form_state) { }

  /**
   * Provide the default form form for validating options
   */
  public function validateOptionsForm(&$form, FormStateInterface $form_state) { }

  /**
   * Provide the default form form for submitting options
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state, &$options = array()) { }

  /**
   * Determine if the administrator has the privileges to use this plugin
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
      $form[$option_name]['#description'] .= ' <strong>' . $this->t('Note: you do not have permission to modify this. If you change the default filter type, this setting will be lost and you will NOT be able to get it back.') . '</strong>';
    }
  }

  public function validateArgument($arg) { return TRUE; }

  /**
   * Process the summary arguments for displaying.
   *
   * Some plugins alter the argument so it uses something else interal.
   * For example the user validation set's the argument to the uid,
   * for a faster query. But there are use cases where you want to use
   * the old value again, for example the summary.
   */
  public function processSummaryArguments(&$args) { }

}

/**
 * @}
 */
