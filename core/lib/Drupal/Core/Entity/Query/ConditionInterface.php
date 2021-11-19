<?php

namespace Drupal\Core\Entity\Query;

/**
 * Defines the entity query condition interface.
 */
interface ConditionInterface {

  /**
   * Gets the current conjunction.
   *
   * @return string
   *   Can be AND or OR.
   */
  public function getConjunction();

  /**
   * Implements \Countable::count().
   *
   * Returns the size of this conditional. The size of the conditional is the
   * size of its conditional array.
   */
  public function count();

  /**
   * Adds a condition.
   *
   * @param string|\Drupal\Core\Entity\Query\ConditionInterface $field
   *   The field.
   * @param mixed $value
   *   (optional) The value.
   * @param string $operator
   *   (optional) The operator.
   * @param string $langcode
   *   (optional) For which language the entity should be prepared, defaults to
   *   the current content language.
   *
   * @return $this
   *
   * @see \Drupal\Core\Entity\Query\QueryInterface::condition()
   */
  public function condition($field, $value = NULL, $operator = NULL, $langcode = NULL);

  /**
   * Queries for the existence of a field.
   *
   * @param string $field
   *   The field to query for existence.
   * @param string $langcode
   *   (optional) For which language the entity should be prepared, defaults to
   *   the current content language.
   *
   * @return $this
   *
   * @see \Drupal\Core\Entity\Query\QueryInterface::exists()
   */
  public function exists($field, $langcode = NULL);

  /**
   * Queries for the nonexistence of a field.
   *
   * @param string $field
   *   The field to query for nonexistence.
   * @param string $langcode
   *   (optional) For which language the entity should be prepared, defaults to
   *   the current content language.
   *
   * @return $this
   *
   * @see \Drupal\Core\Entity\Query\QueryInterface::notExists()
   */
  public function notExists($field, $langcode = NULL);

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
