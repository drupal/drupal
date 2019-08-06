<?php

namespace Drupal\Core\Entity\Query;

/**
 * Defines a interface for aggregated entity queries.
 */
interface QueryAggregateInterface extends QueryInterface {

  /**
   * Specifies a field and a function to aggregate on.
   *
   * Available functions: SUM, AVG, MIN, MAX and COUNT.
   *
   * @todo What about GROUP_CONCAT support?
   *
   * @param string $field
   *   The name of the field to aggregate by.
   * @param string $function
   *   The aggregation function, for example COUNT or MIN.
   * @param string $langcode
   *   (optional) The language code.
   * @param string $alias
   *   (optional) The key that will be used on the resultset.
   *
   * @return $this
   *   The called object.
   */
  public function aggregate($field, $function, $langcode = NULL, &$alias = NULL);

  /**
   * Specifies the field to group on.
   *
   * @param string $field
   *   The name of the field to group by.
   *
   * @return $this
   *   The called object.
   */
  public function groupBy($field);

  /**
   * Sets a condition for an aggregated value.
   *
   * @param string $field
   *   The name of the field to aggregate by.
   * @param string $function
   *   The aggregation function, for example COUNT or MIN.
   * @param mixed $value
   *   The actual value of the field.
   * @param $operator
   *   Possible values:
   *   - '=', '<>', '>', '>=', '<', '<=', 'STARTS_WITH', 'CONTAINS',
   *     'ENDS_WITH': These operators expect $value to be a literal of the
   *     same type as the column.
   *   - 'IN', 'NOT IN': These operators expect $value to be an array of
   *     literals of the same type as the column.
   *   - 'BETWEEN': This operator expects $value to be an array of two literals
   *     of the same type as the column.
   * @param string $langcode
   *   (optional) The language code.
   *
   * @return $this
   *   The called object.
   *
   * @see \Drupal\Core\Entity\Query\QueryInterface::condition()
   */
  public function conditionAggregate($field, $function = NULL, $value = NULL, $operator = '=', $langcode = NULL);

  /**
   * Queries for the existence of a field.
   *
   * @param string $field
   *   The name of the field.
   * @param string $function
   *   The aggregate function.
   * @param $langcode
   *   (optional) The language code.
   *
   * @return $this
   *   The called object.
   */
  public function existsAggregate($field, $function, $langcode = NULL);

  /**
   * Queries for the nonexistence of a field.
   *
   * @param string $field
   *   The name of a field.
   * @param string $function
   *   The aggregate function.
   * @param string $langcode
   *   (optional) The language code.
   *
   * @return $this
   *   The called object.
   */
  public function notExistsAggregate($field, $function, $langcode = NULL);

  /**
   * Creates an object holding a group of conditions.
   *
   * See andConditionAggregateGroup() and orConditionAggregateGroup() for more.
   *
   * @param string $conjunction
   *   - AND (default): this is the equivalent of andConditionAggregateGroup().
   *   - OR: this is the equivalent of andConditionAggregateGroup().
   *
   * @return ConditionInterface
   *   An object holding a group of conditions.
   */
  public function conditionAggregateGroupFactory($conjunction = 'AND');

  /**
   * Sorts by an aggregated value.
   *
   * @param string $field
   *   The name of a field.
   * @param string $function
   *   The aggregate function. This is only marked optional for interface
   *   compatibility, it is illegal to leave it out.
   * @param string $direction
   *   The order of sorting, either DESC for descending of ASC for ascending.
   * @param string $langcode
   *   (optional) The language code.
   *
   * @return $this
   *   The called object.
   */
  public function sortAggregate($field, $function, $direction = 'ASC', $langcode = NULL);

  /**
   * Executes the aggregate query.
   *
   * @return array
   *   A list of result row arrays. Each result row contains the aggregate
   *   results as keys and also the groupBy columns as keys:
   * @code
   * $result = $query
   *   ->aggregate('nid', 'count')
   *   ->condition('status', 1)
   *   ->groupby('type')
   *   ->executeAggregate();
   * @endcode
   * Will return:
   * @code
   * $result[0] = array('count_nid' => 3, 'type' => 'page');
   * $result[1] = array('count_nid' => 1, 'type' => 'poll');
   * $result[2] = array('count_nid' => 4, 'type' => 'story');
   * @endcode
   */
  public function execute();

}
