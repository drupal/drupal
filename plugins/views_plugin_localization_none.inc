<?php

/**
 * @file
 * Contains the 'none' localization plugin.
 */

/**
 * Localization plugin for no localization.
 *
 * @ingroup views_localization_plugins
 */
class views_plugin_localization_none extends views_plugin_localization {
  var $translate = FALSE;

  /**
   * Translate a string; simply return the string.
   */
  function translate($source) {
    return $source['value'];
  }

  /**
   * Save a string for translation; not supported.
   */
  function save($source) {
    return FALSE;
  }

  /**
   * Delete a string; not supported.
   */
  function delete($source) {
    return FALSE;
  }
}
