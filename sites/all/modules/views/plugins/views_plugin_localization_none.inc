<?php
// $Id: views_plugin_localization_none.inc,v 1.1.4.2 2010/12/01 20:05:06 dereine Exp $

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
