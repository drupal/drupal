<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\localization\None.
 */

namespace Drupal\views\Plugin\views\localization;

use Drupal\Core\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Localization plugin for no localization.
 *
 * @ingroup views_localization_plugins
 */

/**
 * @Plugin(
 *   id = "none",
 *   title = @Translation("None"),
 *   help = @Translation("Do not pass admin strings for translation."),
 *   help_topic = "localization-none"
 * )
 */
class None extends LocalizationPluginBase {
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
