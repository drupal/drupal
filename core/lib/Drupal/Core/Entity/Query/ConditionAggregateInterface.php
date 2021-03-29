<?php

namespace Drupal\Core\Entity\Query;

/**
 * Defines aggregated entity query conditions.
 */
interface ConditionAggregateInterface extends \Countable {

  /**
   * Gets the current conjunction.
   *
   * @return string
   *   Can be AND or OR.
   */
  public function getConjunction();

  /**
   * Adds a condition.
   *
   * @param string|ConditionAggregateInterface $field
   * @param string $function
   * @param mixed $value
   * @param string $operator
   * @param string $langcode
   *
   * @return $this
   *   The called object.
   *
   * @see \Drupal\Core\Entity\Query\QueryInterface::condition()
   */
  public function condition($field, $function = NULL, $value = NULL, $operator = NULL, $langcode = NULL);

  /**
   * Queries for the existence of a field.
   *
   * @param string $field
   * @param string $function
   * @param string $langcode
   *
   * @return \Drupal\Core\Entity\Query\ConditionInterface
   *
   * @see \Drupal\Core\Entity\Query\QueryInterface::exists()
   */
  public function exists($field, $function, $langcode = NULL);

  /**
   * Queries for the nonexistence of a field.
   *
   * @param string $field
   * @param string $function
   * @param string $langcode
   *
   * @return \Drupal\Core\Entity\Query\ConditionInterface
   *
   * @see \Drupal\Core\Entity\Query\QueryInterface::notExists()
   */
  public function notExists($field, $function, $langcode = NULL);

  /**
   * Gets a complete list of all conditions in this conditional clause.
   *
   * This method returns by reference. That allows alter hooks to access the
   * data structure directly and manipulate it before it gets compiled.
   *
   * @return array
   */
  public function &conditions();

  /**
   * Compiles this conditional clause.
   *
   * @param $query
   *   The query object this conditional clause belongs to.
   */
  public function compile($query);

}
