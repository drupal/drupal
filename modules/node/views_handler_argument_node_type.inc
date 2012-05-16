<?php

/**
 * @file
 * Definition of views_handler_argument_node_type.
 */

/**
 * Argument handler to accept a node type.
 */
class views_handler_argument_node_type extends views_handler_argument_string {
  function construct() {
    parent::construct('type');
  }

  /**
   * Override the behavior of summary_name(). Get the user friendly version
   * of the node type.
   */
  function summary_name($data) {
    return $this->node_type($data->{$this->name_alias});
  }

  /**
   * Override the behavior of title(). Get the user friendly version of the
   * node type.
   */
  function title() {
    return $this->node_type($this->argument);
  }

  function node_type($type) {
    $output = node_type_get_name($type);
    if (empty($output)) {
      $output = t('Unknown content type');
    }
    return check_plain($output);
  }
}
