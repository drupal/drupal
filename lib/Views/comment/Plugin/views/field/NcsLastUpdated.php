<?php

/**
 * @file
 * Definition of views_handler_field_ncs_last_updated.
 */

namespace Views\comment\Plugin\views\field;

use Drupal\views\Plugin\views\field\Date;
use Drupal\Core\Annotation\Plugin;

/**
 * Field handler to display the newer of last comment / node updated.
 *
 * @ingroup views_field_handlers
 */

/**
 * @Plugin(
 *   id = "comment_ncs_last_updated"
 * )
 */
class NcsLastUpdated extends Date {
  function query() {
    $this->ensure_my_table();
    $this->node_table = $this->query->ensure_table('node', $this->relationship);
    $this->field_alias = $this->query->add_field(NULL, "GREATEST(" . $this->node_table . ".changed, " . $this->table_alias . ".last_comment_timestamp)", $this->table_alias . '_' . $this->field);
  }
}
