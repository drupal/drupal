<?php

/**
 * @file
 * Contains \Drupal\comment\Plugin\views\field\StatisticsLastUpdated.
 */

namespace Drupal\comment\Plugin\views\field;

use Drupal\views\Plugin\views\field\Date;

/**
 * Field handler to display the newer of last comment / node updated.
 *
 * @ingroup views_field_handlers
 *
 * @PluginID("comment_ces_last_updated")
 */
class StatisticsLastUpdated extends Date {

  public function query() {
    $this->ensureMyTable();
    $this->node_table = $this->query->ensureTable('node', $this->relationship);
    $this->field_alias = $this->query->addField(NULL, "GREATEST(" . $this->node_table . ".changed, " . $this->tableAlias . ".last_comment_timestamp)", $this->tableAlias . '_' . $this->field);
  }

}
