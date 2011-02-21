<?php
// $Id: views_handler_filter_vocabulary_machine_name.inc,v 1.1.2.1 2010/10/17 10:48:08 dereine Exp $

/**
 * Filter by vocabulary machine name
 */
class views_handler_filter_vocabulary_machine_name extends views_handler_filter_in_operator {
  function get_value_options() {
    if (isset($this->value_options)) {
      return;
    }

    $this->value_options = array();
    $vocabularies = taxonomy_get_vocabularies();
    foreach ($vocabularies as $voc) {
      $this->value_options[$voc->machine_name] = $voc->name;
    }
  }
}
