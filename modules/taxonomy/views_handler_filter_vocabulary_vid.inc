<?php

/**
 * @file
 * Definition of views_handler_filter_vocabulary_vid.
 */

/**
 * Filter by vocabulary id.
 *
 * @ingroup views_filter_handlers
 */
class views_handler_filter_vocabulary_vid extends views_handler_filter_in_operator {
  function get_value_options() {
    if (isset($this->value_options)) {
      return;
    }

    $this->value_options = array();
    $vocabularies = taxonomy_get_vocabularies();
    foreach ($vocabularies as $voc) {
      $this->value_options[$voc->vid] = $voc->name;
    }
  }
}
