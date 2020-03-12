<?php

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
   *
   * @var string
   */
  protected $uniqueIdentifier;

  /**
   * The placeholder counter.
   *
   * @var int
   */
  protected $placeholder = 0;

  public function __construct(SelectInterface $query, Connection $connection) {
    $this->uniqueIdentifier = uniqid('', TRUE);
    $this->query = $query;
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public function uniqueIdentifier() {
    return $this->uniqueIdentifier;
  }

  /**
   * {@inheritdoc}
   */
  public function nextPlaceholder() {
    return $this->placeholder++;
  }

  /**
   * {@inheritdoc}
   */
  public function addTag($tag) {
    $this->query->addTag($tag);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasTag($tag) {
    return $this->query->hasTag($tag);
  }

  /**
   * {@inheritdoc}
   */
  public function hasAllTags() {
    return call_user_func_array([$this->query, 'hasAllTags'], func_get_args());
  }

  /**
   * {@inheritdoc}
   */
  public function hasAnyTag() {
    return call_user_func_array([$this->query, 'hasAnyTag'], func_get_args());
  }

  /**
   * {@inheritdoc}
   */
  public function addMetaData($key, $object) {
    $this->query->addMetaData($key, $object);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getMetaData($key) {
    return $this->query->getMetaData($key);
  }

  /**
   * {@inheritdoc}
   */
  public function condition($field, $value = NULL, $operator = '=') {
    $this->query->condition($field, $value, $operator);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function &conditions() {
    return $this->query->conditions();
  }

  /**
   * {@inheritdoc}
   */
  public function arguments() {
    return $this->query->arguments();
  }

  /**
   * {@inheritdoc}
   */
  public function where($snippet, $args = []) {
    $this->query->where($snippet, $args);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function compile(Connection $connection, PlaceholderInterface $queryPlaceholder) {
    return $this->query->compile($connection, $queryPlaceholder);
  }

  /**
   * {@inheritdoc}
   */
  public function compiled() {
    return $this->query->compiled();
  }

  /**
   * {@inheritdoc}
   */
  public function havingCondition($field, $value = NULL, $operator = '=') {
    $this->query->havingCondition($field, $value, $operator);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function &havingConditions() {
    return $this->query->havingConditions();
  }

  /**
   * {@inheritdoc}
   */
  public function havingArguments() {
    return $this->query->havingArguments();
  }

  /**
   * {@inheritdoc}
   */
  public function having($snippet, $args = []) {
    $this->query->having($snippet, $args);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function havingCompile(Connection $connection) {
    return $this->query->havingCompile($connection);
  }

  /**
   * {@inheritdoc}
   */
  public function havingIsNull($field) {
    $this->query->havingIsNull($field);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function havingIsNotNull($field) {
    $this->query->havingIsNotNull($field);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function havingExists(SelectInterface $select) {
    $this->query->havingExists($select);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function havingNotExists(SelectInterface $select) {
    $this->query->havingNotExists($select);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function extend($extender_name) {
    $class = $this->connection->getDriverClass($extender_name);
    return new $class($this, $this->connection);
  }

  /* Alter accessors to expose the query data to alter hooks. */

  /**
   * {@inheritdoc}
   */
  public function &getFields() {
    return $this->query->getFields();
  }

  /**
   * {@inheritdoc}
   */
  public function &getExpressions() {
    return $this->query->getExpressions();
  }

  /**
   * {@inheritdoc}
   */
  public function &getOrderBy() {
    return $this->query->getOrderBy();
  }

  /**
   * {@inheritdoc}
   */
  public function &getGroupBy() {
    return $this->query->getGroupBy();
  }

  /**
   * {@inheritdoc}
   */
  public function &getTables() {
    return $this->query->getTables();
  }

  /**
   * {@inheritdoc}
   */
  public function &getUnion() {
    return $this->query->getUnion();
  }

  /**
   * {@inheritdoc}
   */
  public function escapeLike($string) {
    return $this->query->escapeLike($string);
  }

  /**
   * {@inheritdoc}
   */
  public function escapeField($string) {
    $this->query->escapeField($string);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getArguments(PlaceholderInterface $queryPlaceholder = NULL) {
    return $this->query->getArguments($queryPlaceholder);
  }

  /**
   * {@inheritdoc}
   */
  public function isPrepared() {
    return $this->query->isPrepared();
  }

  /**
   * {@inheritdoc}
   */
  public function preExecute(SelectInterface $query = NULL) {
    // If no query object is passed in, use $this.
    if (!isset($query)) {
      $query = $this;
    }

    return $this->query->preExecute($query);
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    // By calling preExecute() here, we force it to preprocess the extender
    // object rather than just the base query object.  That means
    // hook_query_alter() gets access to the extended object.
    if (!$this->preExecute($this)) {
      return NULL;
    }

    return $this->query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function distinct($distinct = TRUE) {
    $this->query->distinct($distinct);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addField($table_alias, $field, $alias = NULL) {
    return $this->query->addField($table_alias, $field, $alias);
  }

  /**
   * {@inheritdoc}
   */
  public function fields($table_alias, array $fields = []) {
    $this->query->fields($table_alias, $fields);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addExpression($expression, $alias = NULL, $arguments = []) {
    return $this->query->addExpression($expression, $alias, $arguments);
  }

  /**
   * {@inheritdoc}
   */
  public function join($table, $alias = NULL, $condition = NULL, $arguments = []) {
    return $this->query->join($table, $alias, $condition, $arguments);
  }

  /**
   * {@inheritdoc}
   */
  public function innerJoin($table, $alias = NULL, $condition = NULL, $arguments = []) {
    return $this->query->innerJoin($table, $alias, $condition, $arguments);
  }

  /**
   * {@inheritdoc}
   */
  public function leftJoin($table, $alias = NULL, $condition = NULL, $arguments = []) {
    return $this->query->leftJoin($table, $alias, $condition, $arguments);
  }

  /**
   * {@inheritdoc}
   */
  public function rightJoin($table, $alias = NULL, $condition = NULL, $arguments = []) {
    return $this->query->rightJoin($table, $alias, $condition, $arguments);
  }

  /**
   * {@inheritdoc}
   */
  public function addJoin($type, $table, $alias = NULL, $condition = NULL, $arguments = []) {
    return $this->query->addJoin($type, $table, $alias, $condition, $arguments);
  }

  /**
   * {@inheritdoc}
   */
  public function orderBy($field, $direction = 'ASC') {
    $this->query->orderBy($field, $direction);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function orderRandom() {
    $this->query->orderRandom();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function range($start = NULL, $length = NULL) {
    $this->query->range($start, $length);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function union(SelectInterface $query, $type = '') {
    $this->query->union($query, $type);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function groupBy($field) {
    $this->query->groupBy($field);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function forUpdate($set = TRUE) {
    $this->query->forUpdate($set);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function countQuery() {
    return $this->query->countQuery();
  }

  /**
   * {@inheritdoc}
   */
  public function isNull($field) {
    $this->query->isNull($field);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isNotNull($field) {
    $this->query->isNotNull($field);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function exists(SelectInterface $select) {
    $this->query->exists($select);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function notExists(SelectInterface $select) {
    $this->query->notExists($select);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function alwaysFalse() {
    $this->query->alwaysFalse();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    return (string) $this->query;
  }

  /**
   * {@inheritdoc}
   */
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
    $return = call_user_func_array([$this->query, $method], $args);

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
