<?php

/**
 * @file
 * Definition of Views\comment\Plugin\views\field\NcsLastUpdated.
 */

namespace Views\comment\Plugin\views\field;

use Drupal\views\Plugin\views\field\Date;
use Drupal\Core\Annotation\Plugin;

/**
 * Field handler to display the newer of last comment / node updated.
 *
 * @ingroup views_field_handlers
 *
 * @Plugin(
 *   id = "comment_ncs_last_updated",
 *   module = "comment"
 * )
 */
class NcsLastUpdated extends Date {

  public function query() {
    $this->ensureMyTable();
    $this->node_table = $this->query->ensure_table('node', $this->relationship);
    $this->field_alias = $this->query->add_field(NULL, "GREATEST(" . $this->node_table . ".changed, " . $this->tableAlias . ".last_comment_timestamp)", $this->tableAlias . '_' . $this->field);
  }

}
