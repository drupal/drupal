<?php

/**
 * @file
 * Definition of Drupal\Core\Database\Query\SelectExtender
 */

namespace Drupal\Core\Database\Query;

use Drupal\Core\Database\Connection;

/**
 * The base extender class for Select queries.
 */
class SelectExtender implements SelectInterface {

  /**
   * The Select query object we are extending/decorating.
   *
   * @var \Drupal\Core\Database\Query\SelectInterface
   */
  protected $query;

  /**
   * The connection object on which to run this query.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * A unique identifier for this query object.
   */
  protected $uniqueIdentifier;

  /**
   * The placeholder counter.
   */
  protected $placeholder = 0;

  public function __construct(SelectInterface $query, Connection $connection) {
    $this->uniqueIdentifier = uniqid('', TRUE);
    $this->query = $query;
    $this->connection = $connection;
  }

  /**
   * Implements Drupal\Core\Database\Query\PlaceholderInterface::uniqueIdentifier().
   */
  public function uniqueIdentifier() {
    return $this->uniqueIdentifier;
  }

  /**
   * Implements Drupal\Core\Database\Query\PlaceholderInterface::nextPlaceholder().
   */
  public function nextPlaceholder() {
    return $this->placeholder++;
  }

  /* Implementations of Drupal\Core\Database\Query\AlterableInterface. */

  public function addTag($tag) {
    $this->query->addTag($tag);
    return $this;
  }

  public function hasTag($tag) {
    return $this->query->hasTag($tag);
  }

  public function hasAllTags() {
    return call_user_func_array(array($this->query, 'hasAllTags'), func_get_args());
  }

  public function hasAnyTag() {
    return call_user_func_array(array($this->query, 'hasAnyTag'), func_get_args());
  }

  public function addMetaData($key, $object) {
    $this->query->addMetaData($key, $object);
    return $this;
  }

  public function getMetaData($key) {
    return $this->query->getMetaData($key);
  }

  /* Implementations of Drupal\Core\Database\Query\ConditionInterface for the WHERE clause. */

  public function condition($field, $value = NULL, $operator = NULL) {
    $this->query->condition($field, $value, $operator);
    return $this;
  }

  public function &conditions() {
    return $this->query->conditions();
  }

  public function arguments() {
    return $this->query->arguments();
  }

  public function where($snippet, $args = array()) {
    $this->query->where($snippet, $args);
    return $this;
  }

  public function compile(Connection $connection, PlaceholderInterface $queryPlaceholder) {
    return $this->query->compile($connection, $queryPlaceholder);
  }

  public function compiled() {
    return $this->query->compiled();
  }

  /* Implementations of Drupal\Core\Database\Query\ConditionInterface for the HAVING clause. */

  public function havingCondition($field, $value = NULL, $operator = '=') {
    $this->query->havingCondition($field, $value, $operator);
    return $this;
  }

  public function &havingConditions() {
    return $this->query->havingConditions();
  }

  public function havingArguments() {
    return $this->query->havingArguments();
  }

  public function having($snippet, $args = array()) {
    $this->query->having($snippet, $args);
    return $this;
  }

  public function havingCompile(Connection $connection) {
    return $this->query->havingCompile($connection);
  }

  /* Implementations of Drupal\Core\Database\Query\ExtendableInterface. */

  public function extend($extender_name) {
    $class = $this->connection->getDriverClass($extender_name);
    return new $class($this, $this->connection);
  }

  /* Alter accessors to expose the query data to alter hooks. */

  public function &getFields() {
    return $this->query->getFields();
  }

  public function &getExpressions() {
    return $this->query->getExpressions();
  }

  public function &getOrderBy() {
    return $this->query->getOrderBy();
  }

  public function &getGroupBy() {
    return $this->query->getGroupBy();
  }

  public function &getTables() {
    return $this->query->getTables();
  }

  public function &getUnion() {
    return $this->query->getUnion();
  }

  public function getArguments(PlaceholderInterface $queryPlaceholder = NULL) {
    return $this->query->getArguments($queryPlaceholder);
  }

  public function isPrepared() {
    return $this->query->isPrepared();
  }

  public function preExecute(SelectInterface $query = NULL) {
    // If no query object is passed in, use $this.
    if (!isset($query)) {
      $query = $this;
    }

    return $this->query->preExecute($query);
  }

  public function execute() {
    // By calling preExecute() here, we force it to preprocess the extender
    // object rather than just the base query object.  That means
    // hook_query_alter() gets access to the extended object.
    if (!$this->preExecute($this)) {
      return NULL;
    }

    return $this->query->execute();
  }

  public function distinct($distinct = TRUE) {
    $this->query->distinct($distinct);
    return $this;
  }

  public function addField($table_alias, $field, $alias = NULL) {
    return $this->query->addField($table_alias, $field, $alias);
  }

  public function fields($table_alias, array $fields = array()) {
    $this->query->fields($table_alias, $fields);
    return $this;
  }

  public function addExpression($expression, $alias = NULL, $arguments = array()) {
    return $this->query->addExpression($expression, $alias, $arguments);
  }

  public function join($table, $alias = NULL, $condition = NULL, $arguments = array()) {
    return $this->query->join($table, $alias, $condition, $arguments);
  }

  public function innerJoin($table, $alias = NULL, $condition = NULL, $arguments = array()) {
    return $this->query->innerJoin($table, $alias, $condition, $arguments);
  }

  public function leftJoin($table, $alias = NULL, $condition = NULL, $arguments = array()) {
    return $this->query->leftJoin($table, $alias, $condition, $arguments);
  }

  public function rightJoin($table, $alias = NULL, $condition = NULL, $arguments = array()) {
    return $this->query->rightJoin($table, $alias, $condition, $arguments);
  }

  public function addJoin($type, $table, $alias = NULL, $condition = NULL, $arguments = array()) {
    return $this->query->addJoin($type, $table, $alias, $condition, $arguments);
  }

  public function orderBy($field, $direction = 'ASC') {
    $this->query->orderBy($field, $direction);
    return $this;
  }

  public function orderRandom() {
    $this->query->orderRandom();
    return $this;
  }

  public function range($start = NULL, $length = NULL) {
    $this->query->range($start, $length);
    return $this;
  }

  public function union(SelectInterface $query, $type = '') {
    $this->query->union($query, $type);
    return $this;
  }

  public function groupBy($field) {
    $this->query->groupBy($field);
    return $this;
  }

  public function forUpdate($set = TRUE) {
    $this->query->forUpdate($set);
    return $this;
  }

  public function countQuery() {
    return $this->query->countQuery();
  }

  function isNull($field) {
    $this->query->isNull($field);
    return $this;
  }

  function isNotNull($field) {
    $this->query->isNotNull($field);
    return $this;
  }

  public function exists(SelectInterface $select) {
    $this->query->exists($select);
    return $this;
  }

  public function notExists(SelectInterface $select) {
    $this->query->notExists($select);
    return $this;
  }

  public function __toString() {
    return (string) $this->query;
  }

  public function __clone() {
    $this->uniqueIdentifier = uniqid('', TRUE);

    // We need to deep-clone the query we're wrapping, which in turn may
    // deep-clone other objects.  Exciting!
    $this->query = clone($this->query);
  }

  /**
   * Magic override for undefined methods.
   *
   * If one extender extends another extender, then methods in the inner extender
   * will not be exposed on the outer extender.  That's because we cannot know
   * in advance what those methods will be, so we cannot provide wrapping
   * implementations as we do above.  Instead, we use this slower catch-all method
   * to handle any additional methods.
   */
  public function __call($method, $args) {
    $return = call_user_func_array(array($this->query, $method), $args);

    // Some methods will return the called object as part of a fluent interface.
    // Others will return some useful value.  If it's a value, then the caller
    // probably wants that value.  If it's the called object, then we instead
    // return this object.  That way we don't "lose" an extender layer when
    // chaining methods together.
    if ($return instanceof SelectInterface) {
      return $this;
    }
    else {
      return $return;
    }
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
