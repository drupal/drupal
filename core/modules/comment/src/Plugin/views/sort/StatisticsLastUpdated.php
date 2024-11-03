<?php

namespace Drupal\comment\Plugin\views\sort;

use Drupal\views\Attribute\ViewsSort;
use Drupal\views\Plugin\views\sort\Date;

/**
 * Sort handler for the newer of last comment / entity updated.
 *
 * @ingroup views_sort_handlers
 */
#[ViewsSort("comment_ces_last_updated")]
class StatisticsLastUpdated extends Date {

  /**
   * The node table.
   */
  // phpcs:ignore Drupal.NamingConventions.ValidVariableName.LowerCamelName, Drupal.Commenting.VariableComment.Missing
  protected ?string $node_table;

  /**
   * The field alias.
   */
  // phpcs:ignore Drupal.NamingConventions.ValidVariableName.LowerCamelName, Drupal.Commenting.VariableComment.Missing
  protected string $field_alias;

  public function query() {
    $this->ensureMyTable();
    $this->node_table = $this->query->ensureTable('node', $this->relationship);
    $this->field_alias = $this->query->addOrderBy(NULL, "GREATEST(" . $this->node_table . ".changed, " . $this->tableAlias . ".last_comment_timestamp)", $this->options['order'], $this->tableAlias . '_' . $this->field);
  }

}
