<?php

/**
 * @file
 * Definition of views_handler_sort_ncs_last_updated.
 */

namespace Drupal\comment\Plugin\views\sort;

use Drupal\views\Plugin\views\sort\Date;
use Drupal\Core\Annotation\Plugin;

/**
 * Sort handler for the newer of last comment / node updated.
 *
 * @ingroup views_sort_handlers
 */

/**
 * @Plugin(
 *   plugin_id = 'ncs_last_updated'
 * )
 */
class NcsLastUpdated extends Date {
  function query() {
    $this->ensure_my_table();
    $this->node_table = $this->query->ensure_table('node', $this->relationship);
    $this->field_alias = $this->query->add_orderby(NULL, "GREATEST(" . $this->node_table . ".changed, " . $this->table_alias . ".last_comment_timestamp)", $this->options['order'], $this->table_alias . '_' . $this->field);
  }
}
