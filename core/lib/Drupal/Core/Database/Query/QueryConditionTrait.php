<?php

/**
 * @file
 * Contains \Drupal\Core\Database\Query\QueryConditionTrait.
 */

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
   * Implements Drupal\Core\Database\Query\ConditionInterface::condition().
   */
  public function condition($field, $value = NULL, $operator = '=') {
    $this->condition->condition($field, $value, $operator);
    return $this;
  }

  /**
   * Implements Drupal\Core\Database\Query\ConditionInterface::isNull().
   */
  public function isNull($field) {
    $this->condition->isNull($field);
    return $this;
  }

  /**
   * Implements Drupal\Core\Database\Query\ConditionInterface::isNotNull().
   */
  public function isNotNull($field) {
    $this->condition->isNotNull($field);
    return $this;
  }

  /**
   * Implements Drupal\Core\Database\Query\ConditionInterface::exists().
   */
  public function exists(SelectInterface $select) {
    $this->condition->exists($select);
    return $this;
  }

  /**
   * Implements Drupal\Core\Database\Query\ConditionInterface::notExists().
   */
  public function notExists(SelectInterface $select) {
    $this->condition->notExists($select);
    return $this;
  }

  /**
   * Implements Drupal\Core\Database\Query\ConditionInterface::conditions().
   */
  public function &conditions() {
    return $this->condition->conditions();
  }

  /**
   * Implements Drupal\Core\Database\Query\ConditionInterface::arguments().
   */
  public function arguments() {
    return $this->condition->arguments();
  }

  /**
   * Implements Drupal\Core\Database\Query\ConditionInterface::where().
   */
  public function where($snippet, $args = array()) {
    $this->condition->where($snippet, $args);
    return $this;
  }

  /**
   * Implements Drupal\Core\Database\Query\ConditionInterface::compile().
   */
  public function compile(Connection $connection, PlaceholderInterface $queryPlaceholder) {
    $this->condition->compile($connection, $queryPlaceholder);
  }

  /**
   * Implements Drupal\Core\Database\Query\ConditionInterface::compiled().
   */
  public function compiled() {
    return $this->condition->compiled();
  }

  /**
   * Implements Drupal\Core\Database\Query\ConditionInterface::conditionGroupFactory().
   */
  public function conditionGroupFactory($conjunction = 'AND') {
    return new Condition($conjunction);
  }

  /**
   * Implements Drupal\Core\Database\Query\ConditionInterface::andConditionGroup().
   */
  public function andConditionGroup() {
    return $this->conditionGroupFactory('AND');
  }

  /**
   * Implements Drupal\Core\Database\Query\ConditionInterface::orConditionGroup().
   */
  public function orConditionGroup() {
    return $this->conditionGroupFactory('OR');
  }

}
