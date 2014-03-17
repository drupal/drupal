<?php

/**
 * @file
 * Definition of Drupal\taxonomy\Plugin\views\argument\Taxonomy.
 */

namespace Drupal\taxonomy\Plugin\views\argument;

use Drupal\views\Plugin\views\argument\Numeric;
use Drupal\Component\Utility\String;

/**
 * Argument handler for basic taxonomy tid.
 *
 * @ingroup views_argument_handlers
 *
 * @PluginID("taxonomy")
 */
class Taxonomy extends Numeric {

  /**
   * Override the behavior of title(). Get the title of the node.
   */
  function title() {
    // There might be no valid argument.
    if ($this->argument) {
      $term = entity_load('taxonomy_term', $this->argument);
      if (!empty($term)) {
        return String::checkPlain($term->getName());
      }
    }
    // TODO review text
    return t('No name');
  }

}
