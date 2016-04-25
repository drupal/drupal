<?php

namespace Drupal\Core\Database\Query;

use Drupal\Core\Database\Connection;

/**
 * Provides an implementation of ConditionInterface.
 *
 * @see \Drupal\Core\Database\Query\ConditionInterface
 */
trait QueryConditionTrait {

  /**
   * The condition object for this query.
   *
   * Condition handling is handled via composition.
   *
   * @var \Drupal\Core\Database\Query\Condition
   */
  protected $condition;

  /**
   * {@inheritdoc}
   */
  public function condition($field, $value = NULL, $operator = '=') {
    $this->condition->condition($field, $value, $operator);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isNull($field) {
    $this->condition->isNull($field);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isNotNull($field) {
    $this->condition->isNotNull($field);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function exists(SelectInterface $select) {
    $this->condition->exists($select);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function notExists(SelectInterface $select) {
    $this->condition->notExists($select);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function &conditions() {
    return $this->condition->conditions();
  }

  /**
   * {@inheritdoc}
   */
  public function arguments() {
    return $this->condition->arguments();
  }

  /**
   * {@inheritdoc}
   */
  public function where($snippet, $args = array()) {
    $this->condition->where($snippet, $args);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function compile(Connection $connection, PlaceholderInterface $queryPlaceholder) {
    $this->condition->compile($connection, $queryPlaceholder);
  }

  /**
   * {@inheritdoc}
   */
  public function compiled() {
    return $this->condition->compiled();
  }

  /**
   * {@inheritdoc}
   */
  public function conditionGroupFactory($conjunction = 'AND') {
    return new Condition($conjunction);
  }

  /**
   * {@inheritdoc}
   */
  public function andConditionGroup() {
    return $this->conditionGroupFactory('AND');
  }

  /**
   * {@inheritdoc}
   */
  public function orConditionGroup() {
    return $this->conditionGroupFactory('OR');
  }

}
