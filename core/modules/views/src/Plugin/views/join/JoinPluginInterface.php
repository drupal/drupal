<?php

namespace Drupal\views\Plugin\views\join;

/**
 * Represents a join and creates the SQL necessary to implement the join.
 *
 * Extensions of this class can be used to create more interesting joins.
 */
interface JoinPluginInterface {

  /**
   * Builds the SQL for the join this object represents.
   *
   * When possible, try to use table alias instead of table names.
   *
   * @param \Drupal\Core\Database\Query\Select $select_query
   *   A select query object.
   * @param string $table
   *   The base table to join.
   * @param \Drupal\views\Plugin\views\query\QueryPluginBase $view_query
   *   The source views query.
   */
  public function buildJoin($select_query, $table, $view_query);

}
