<?php
// $Id: views_plugin_access.inc,v 1.1.6.1 2009/11/13 23:19:22 dereine Exp $

/**
 * The base plugin to handle access control.
 *
 * @ingroup views_access_plugins
 */
class views_plugin_access extends views_plugin {
  /**
   * Initialize the plugin.
   *
   * @param $view
   *   The view object.
   * @param $display
   *   The display handler.
   */
  function init(&$view, &$display) {
    $this->view = &$view;
    $this->display = &$display;

    if (is_object($display->handler)) {
      $options = $display->handler->get_option('access');
      // Overlay incoming options on top of defaults
      $this->unpack_options($this->options, $options);
    }
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
   * Return a string to display as the clickable title for the
   * access control.
   */
  function summary_title() {
    return t('Unknown');
  }

  /**
   * Determine if the current user has access or not.
   */
  function access($account) {
    // default to no access control.
    return TRUE;
  }

  /**
   * Determine the access callback and arguments.
   *
   * This information will be embedded in the menu in order to reduce
   * performance hits during menu item access testing, which happens
   * a lot.
   *
   * @return an array; the first item should be the function to call,
   *   and the second item should be an array of arguments. The first
   *   item may also be TRUE (bool only) which will indicate no
   *   access control.)
   */
  function get_access_callback() {
    // default to no access control.
    return TRUE;
  }
}
