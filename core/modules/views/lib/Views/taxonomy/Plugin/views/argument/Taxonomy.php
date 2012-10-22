<?php

/**
 * @file
 * Definition of Views\taxonomy\Plugin\views\argument\Taxonomy.
 */

namespace Views\taxonomy\Plugin\views\argument;

use Drupal\views\Plugin\views\argument\Numeric;
use Drupal\Core\Annotation\Plugin;

/**
 * Argument handler for basic taxonomy tid.
 *
 * @ingroup views_argument_handlers
 *
 * @Plugin(
 *   id = "taxonomy",
 *   module = "taxonomy"
 * )
 */
class Taxonomy extends Numeric {

  /**
   * Override the behavior of title(). Get the title of the node.
   */
  function title() {
    // There might be no valid argument.
    if ($this->argument) {
      $term = taxonomy_term_load($this->argument);
      if (!empty($term)) {
        return check_plain($term->name);
      }
    }
    // TODO review text
    return t('No name');
  }

}
