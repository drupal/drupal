<?php

namespace Drupal\comment\Plugin\views\field;

use Drupal\views\Plugin\views\field\Date;

/**
 * Field handler to display the newer of last comment / node updated.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("comment_ces_last_updated")
 */
class StatisticsLastUpdated extends Date {

  /**
   * The node table.
   */
  protected $node_table;

  public function query() {
    $this->ensureMyTable();
    $this->node_table = $this->query->ensureTable('node_field_data', $this->relationship);
    $this->field_alias = $this->query->addField(NULL, "GREATEST(" . $this->node_table . ".changed, " . $this->tableAlias . ".last_comment_timestamp)", $this->tableAlias . '_' . $this->field);
  }

}
