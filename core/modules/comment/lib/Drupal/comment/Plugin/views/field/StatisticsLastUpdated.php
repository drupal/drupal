<?php

/**
 * @file
 * Definition of Drupal\comment\Plugin\views\field\StatisticsLastUpdated.
 */

namespace Drupal\comment\Plugin\views\field;

use Drupal\views\Plugin\views\field\Date;
use Drupal\Core\Annotation\Plugin;

/**
 * Field handler to display the newer of last comment / node updated.
 *
 * @ingroup views_field_handlers
 *
 * @Plugin(
 *   id = "comment_ces_last_updated",
 *   module = "comment"
 * )
 */
class StatisticsLastUpdated extends Date {

  public function query() {
    $this->ensureMyTable();
    $this->node_table = $this->query->ensure_table('node', $this->relationship);
    $this->field_alias = $this->query->add_field(NULL, "GREATEST(" . $this->node_table . ".changed, " . $this->tableAlias . ".last_comment_timestamp)", $this->tableAlias . '_' . $this->field);
  }

}
