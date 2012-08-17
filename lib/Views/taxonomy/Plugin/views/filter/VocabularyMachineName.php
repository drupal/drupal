<?php

/**
 * @file
 * Definition of Views\taxonomy\Plugin\views\filter\VocabularyMachineName.
 */

namespace Views\taxonomy\Plugin\views\filter;

use Drupal\Core\Annotation\Plugin;
use Drupal\views\Plugin\views\filter\InOperator;

/**
 * Filter by vocabulary machine name.
 *
 * @ingroup views_filter_handlers
 *
 * @Plugin(
 *   id = "vocabulary_machine_name",
 *   module = "taxonomy"
 * )
 */
class VocabularyMachineName extends InOperator {

  function get_value_options() {
    if (isset($this->value_options)) {
      return;
    }

    $this->value_options = array();
    $vocabularies = taxonomy_vocabulary_get_names();
    foreach ($vocabularies as $voc) {
      $this->value_options[$voc->machine_name] = $voc->name;
    }
  }

}
