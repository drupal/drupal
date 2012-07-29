<?php

/**
 * @file
 * Definition of views_handler_argument_node_language.
 */

namespace Drupal\node\Plugin\views\argument;

use Drupal\views\Plugins\views\argument\ArgumentPluginBase;
use Drupal\Core\Annotation\Plugin;

/**
 * Argument handler to accept a language.
 */

/**
 * @Plugin(
 *   plugin_id = "node_language"
 * )
 */
class Language extends ArgumentPluginBase {
  function construct() {
    parent::construct('language');
  }

  /**
   * Override the behavior of summary_name(). Get the user friendly version
   * of the language.
   */
  function summary_name($data) {
    return $this->node_language($data->{$this->name_alias});
  }

  /**
   * Override the behavior of title(). Get the user friendly version of the
   * node type.
   */
  function title() {
    return $this->node_language($this->argument);
  }

  function node_language($langcode) {
    $languages = views_language_list();
    return isset($languages[$langcode]) ? $languages[$langcode] : t('Unknown language');
  }
}
