<?php
// $Id: views_handler_argument_node_type.inc,v 1.2.4.1 2009/11/02 22:01:26 merlinofchaos Exp $
/**
 * Argument handler to accept a node type.
 */
class views_handler_argument_node_type extends views_handler_argument {
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
      $output = t('Unknown node type');
    }
    return check_plain($output);
  }
}
