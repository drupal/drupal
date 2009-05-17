<?php

/**
 * @file
 * Contains the default style plugin.
 */

/**
 * Default style plugin to render rows one after another with no
 * decorations.
 *
 * @ingroup views_style_plugins
 */
class views_plugin_style_default extends views_plugin_style {
  /**
   * Set default options
   */
  function options(&$options) {
    parent::options($options);
  }

  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
  }
}
