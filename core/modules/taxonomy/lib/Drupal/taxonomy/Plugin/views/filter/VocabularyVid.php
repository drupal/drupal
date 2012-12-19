<?php

/**
 * @file
 * Definition of Drupal\taxonomy\Plugin\views\filter\VocabularyVid.
 */

namespace Drupal\taxonomy\Plugin\views\filter;

use Drupal\Core\Annotation\Plugin;
use Drupal\views\Plugin\views\filter\InOperator;

/**
 * Filter by vocabulary id.
 *
 * @ingroup views_filter_handlers
 *
 * @Plugin(
 *   id = "vocabulary_vid",
 *   module = "taxonomy"
 * )
 */
class VocabularyVid extends InOperator {

  function get_value_options() {
    if (isset($this->value_options)) {
      return;
    }

    $this->value_options = array();
    $vocabularies = entity_load_multiple('taxonomy_vocabulary');
    foreach ($vocabularies as $voc) {
      $this->value_options[$voc->id()] = $voc->label();
    }
  }

}
