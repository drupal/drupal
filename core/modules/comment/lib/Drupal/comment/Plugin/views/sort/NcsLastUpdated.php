<?php

/**
 * @file
 * Definition of Drupal\comment\Plugin\views\sort\NcsLastUpdated.
 */

namespace Drupal\comment\Plugin\views\sort;

use Drupal\views\Plugin\views\sort\Date;
use Drupal\Component\Annotation\Plugin;

/**
 * Sort handler for the newer of last comment / node updated.
 *
 * @ingroup views_sort_handlers
 *
 * @Plugin(
 *   id = "ncs_last_updated",
 *   module = "comment"
 * )
 */
class NcsLastUpdated extends Date {

  public function query() {
    $this->ensureMyTable();
    $this->node_table = $this->query->ensure_table('node', $this->relationship);
    $this->field_alias = $this->query->add_orderby(NULL, "GREATEST(" . $this->node_table . ".changed, " . $this->tableAlias . ".last_comment_timestamp)", $this->options['order'], $this->tableAlias . '_' . $this->field);
  }

}
