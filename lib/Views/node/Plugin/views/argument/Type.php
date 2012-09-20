<?php

/**
 * @file
 * Definition of Views\node\Plugin\views\argument\Type.
 */

namespace Views\node\Plugin\views\argument;

use Drupal\views\Plugin\views\argument\String;
use Drupal\Core\Annotation\Plugin;

/**
 * Argument handler to accept a node type.
 *
 * @Plugin(
 *   id = "node_type",
 *   module = "node"
 * )
 */
class Type extends String {

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
    $output = node_type_get_label($type);
    if (empty($output)) {
      $output = t('Unknown content type');
    }
    return check_plain($output);
  }

}
