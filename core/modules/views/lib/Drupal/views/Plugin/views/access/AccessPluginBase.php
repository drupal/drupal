<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\access\AccessPluginBase.
 */

namespace Drupal\views\Plugin\views\access;

use Drupal\views\Plugin\views\PluginBase;
use Drupal\views\ViewExecutable;

/**
 * @defgroup views_access_plugins Views access plugins
 * @{
 * The base plugin to handle access to a view.
 *
 * Therefore it primarily has to implement the access and the get_access_callback
 * method.
 */

/**
 * The base plugin to handle access control.
 */
abstract class AccessPluginBase extends PluginBase {

  /**
   * Initialize the plugin.
   *
   * @param $view
   *   The view object.
   * @param $display
   *   The display handler.
   */
  public function init(ViewExecutable $view, &$display, $options = NULL) {
    $this->setOptionDefaults($this->options, $this->defineOptions());
    $this->view = &$view;
    $this->displayHandler = &$display;

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
  public function submitOptionsForm(&$form, &$form_state) { }

  /**
   * Return a string to display as the clickable title for the
   * access control.
   */
  public function summaryTitle() {
    return t('Unknown');
  }

  /**
   * Determine if the current user has access or not.
   *
   * @param Drupal\user\User $account
   *   The user who wants to access this view.
   *
   * @return TRUE
   *   Returns whether the user has access to the view.
   */
  abstract public function access($account);

  /**
   * Determine the access callback and arguments.
   *
   * This information will be embedded in the menu in order to reduce
   * performance hits during menu item access testing, which happens
   * a lot.
   *
   * @return array
   *   The first item of the array should be the function to call,and the
   *   second item should be an array of arguments. The first item may also be
   *   TRUE (bool only) which will indicate no access control.
   */
  abstract function get_access_callback();

}

/**
 * @}
 */
