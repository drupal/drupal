<?php

/**
 * @file
 * Contains Drupal\Core\Database\Driver\fake\FakeSelect.
 */

namespace Drupal\Core\Database\Driver\fake;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\Condition;
use Drupal\Core\Database\Query\PlaceholderInterface;
use Drupal\Core\Database\Query\Select;
use Drupal\Core\Database\Query\SelectInterface;

class FakeSelect extends Select {

  /**
   * Contents of the pseudo-database.
   *
   * Keys are table names and values are arrays of rows in the table.
   * Every row there contains all table field values keyed by field name.
   *
   * @code
   * array(
   *   'user' => array(
   *     array(
   *       'uid' => 1,
   *       'name' => 'admin',
   *     ),
   *     array(
   *       'uid' => 2,
   *       'name' => 'alice',
   *     ),
   *   ),
   *   'node' => array(
   *     array(
   *       'nid' => 1,
   *     )
   *   )
   * )
   * @endcode
   *
   * @var array
   */
  protected $databaseContents;

  protected $countQuery = FALSE;
  protected $fieldsWithTable = array();

  /**
   * Constructs a new FakeSelect.
   *
   * @param array $database_contents
   *   An array of mocked database content.
   * @param string $table
   *   The base table name used within fake select.
   * @param string $alias
   *   The base table alias used within fake select.
   * @param string $conjunction
   *   The operator to use to combine conditions: 'AND' or 'OR'.
   */
  public function __construct(array $database_contents, $table, $alias, $conjunction = 'AND') {
    $this->databaseContents = $database_contents;
    $this->addJoin(NULL, $table, $alias);
    $this->where = new Condition($conjunction);
    $this->having = new Condition($conjunction);
  }

  /**
   * {@inheritdoc}
   */
  public function leftJoin($table, $alias = NULL, $condition = NULL, $arguments = array()) {
    return $this->addJoin('LEFT', $table, $alias, $condition, $arguments);
  }

  /**
   * {@inheritdoc}
   */
  public function addJoin($type, $table, $alias = NULL, $condition = NULL, $arguments = array()) {
    if ($table instanceof SelectInterface) {
      // @todo implement this.
      throw new \Exception('Subqueries are not supported at this moment.');
    }
    $alias = parent::addJoin($type, $table, $alias, $condition, $arguments);
    if (isset($type)) {
      if ($type != 'INNER' && $type != 'LEFT') {
        throw new \Exception(sprintf('%s type not supported, only INNER and LEFT.', $type));
      }
      if (!preg_match('/^(\w+\.)?(\w+)\s*=\s*(\w+)\.(\w+)$/', $condition, $matches)) {
        throw new \Exception('Only x.field1 = y.field2 conditions are supported.' . $condition);
      }
      if (!$matches[1] && count($this->tables) == 2) {
        $aliases = array_keys($this->tables);
        $matches[1] = $aliases[0];
      }
      else {
        $matches[1] = substr($matches[1], 0, -1);
      }
      if (!$matches[1]) {
        throw new \Exception('Only x.field1 = y.field2 conditions are supported.' . $condition);
      }
      if ($matches[1] == $alias) {
        $this->tables[$alias] += array(
          'added_field' => $matches[2],
          'original_table_alias' => $matches[3],
          'original_field' => $matches[4],
        );
      }
      elseif ($matches[3] == $alias) {
        $this->tables[$alias] += array(
          'added_field' => $matches[4],
          'original_table_alias' => $matches[1],
          'original_field' => $matches[2],
        );
      }
      else {
        throw new \Exception('The JOIN condition does not contain the alias of the joined table.');
      }
    }
    return $alias;
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    // @todo: Implement distinct() handling.

    // Single table count queries often do not contain fields which this class
    // does not support otherwise, so add a shortcut.
    if (count($this->tables) == 1 && $this->countQuery) {
      $table_info = reset($this->tables);
      $where = $this->where;
      if (!empty($this->databaseContents[$table_info['table']])) {
        $results = array_filter($this->databaseContents[$table_info['table']], function ($row_array) use ($where) {
          return ConditionResolver::matchGroup(new DatabaseRow($row_array), $where);
        });
      }
      else {
        $results = array();
      }
    }
    else {
      $all_rows = $this->executeJoins();
      $all_rows = $this->resolveConditions($this->where, $all_rows);
      if (!empty($this->order)) {
        usort($all_rows, array($this, 'sortCallback'));
      }
      // Now flatten the rows so that each row becomes a field alias => value
      // array.
      $results = array();
      foreach ($all_rows as $table_rows) {
        $result_row = array();
        foreach ($table_rows as $row) {
          $result_row += $row['result'];
        }
        $results[] = $result_row;
      }
    }
    if (!empty($this->range)) {
      $results = array_slice($results, $this->range['start'], $this->range['length']);
    }
    if ($this->countQuery) {
      $results = array(array(count($results)));
    }
    return new FakeStatement($results);
  }

  /**
   * Create an initial result set by executing the joins and picking fields.
   *
   * @return array
   *   A multidimensional array, the first key are table aliases, the second
   *   are field aliases, the values are the database contents or NULL in case
   *   of JOINs.
   */
  protected function executeJoins() {
    $fields = array();
    foreach ($this->fields as $field_info) {
      $this->fieldsWithTable[$field_info['table'] . '.' . $field_info['field']] = $field_info;
      $fields[$field_info['table']][$field_info['field']] = $field_info['alias'];
    }
    foreach ($this->tables as $alias => $table_info) {
      if ($table = reset($this->databaseContents[$table_info['table']])) {
        foreach (array_keys($table) as $field) {
          if (!isset($this->fields[$field])) {
            $this->fieldsWithTable[$field] = array(
              'table' => $alias,
              'field' => $field,
            );
            $this->fieldsWithTable["$alias.$field"] = array(
              'table' => $alias,
              'field' => $field,
            );
          }
        }
      }
    }
    // This will contain a multiple dimensional array. The first key will be a
    // table alias, the second either result or all, the third will be a field
    // alias. all contains every field in the table with the original field
    // names while result contains only the fields requested. This allows for
    // filtering on fields that were not added via addField().
    $results = array();
    foreach ($this->tables as $table_alias => $table_info) {
      // The base table for this query.
      if (empty($table_info['join type'])) {
        foreach ($this->databaseContents[$table_info['table']] as $candidate_row) {
          $results[] = $this->getNewRow($table_alias, $fields, $candidate_row);
        }
      }
      else {
        $new_rows = array();

        // Dynamically build a set of joined rows. Check existing rows and see
        // if they can be joined with incoming rows.
        foreach ($results as $row) {
          $joined = FALSE;
          foreach ($this->databaseContents[$table_info['table']] as $candidate_row) {
            if ($row[$table_info['original_table_alias']]['result'][$table_info['original_field']] == $candidate_row[$table_info['added_field']]) {
              $joined = TRUE;
              $new_rows[] = $this->getNewRow($table_alias, $fields, $candidate_row, $row);
            }
          }
          if (!$joined && $table_info['join type'] == 'LEFT') {
            // Because PHP doesn't scope their foreach statements,
            // $candidate_row may contain the last value assigned to it from the
            // previous statement.
            // @TODO: empty tables? Those are a problem.
            $keys = array_keys($candidate_row);
            $values = array_fill(0, count($keys), NULL);
            $new_row = array(
              'result' => $fields[$table_alias],
              'all' => array_combine($keys, $values),
            );
            $new_rows[] = array($table_alias => $new_row) + $row;
          }
        }
        $results = $new_rows;
      }
    }
    return $results;
  }

  /**
   * Retrieves a new row.
   *
   * @param string $table_alias
   * @param array $fields
   * @param array $candidate_row
   * @param array $row
   *
   * @return array
   */
  protected function getNewRow($table_alias, $fields, $candidate_row, $row = array()) {
    $new_row[$table_alias]['all'] = $candidate_row;
    foreach ($fields[$table_alias] as $field => $alias) {
      $new_row[$table_alias]['result'][$alias] = $candidate_row[$field];
    }
    return $new_row + $row;
  }

  /**
   * {@inheritdoc}
   */
  public function countQuery() {
    $query = clone $this;
    return $query->setCountQuery();
  }

  /**
   * Set this query to be a count query.
   */
  protected function setCountQuery() {
    $this->countQuery = TRUE;
    return $this;
  }

  /**
   * usort callback to order the results.
   */
  protected function sortCallback($a, $b) {
    $a_row = new DatabaseRowSelect($a, $this->fieldsWithTable, $this->fields);
    $b_row = new DatabaseRowSelect($b, $this->fieldsWithTable, $this->fields);
    foreach ($this->order as $field => $direction) {
      $a_value = $a_row->getValue($field);
      $b_value = $b_row->getValue($field);
      if ($a_value != $b_value) {
        return (($a_value < $b_value) == ($direction == 'ASC')) ? -1 : 1;
      }
    }
    return 0;
  }

  /**
   * Resolves conditions by removing non-matching rows.
   *
   * @param \Drupal\Core\Database\Query\Condition $condition_group
   *   The condition group to check.
   * @param array $rows
   *   An array of rows excluding non-matching rows.
   *
   * @return \Drupal\Core\Database\Driver\fake\ConditionResolver
   *   The condition resolver object.
   */
  protected function resolveConditions(Condition $condition_group, array &$rows) {
    $fields_with_table = $this->fieldsWithTable;
    $fields = $this->fields;
    return array_filter($rows, function ($row_array) use ($condition_group, $fields_with_table, $fields) {
      $row = new DatabaseRowSelect($row_array, $fields_with_table, $fields);
      return ConditionResolver::matchGroup($row, $condition_group);
    });
  }

  /**
   * {@inheritdoc}
   */
  public function orderBy($field, $direction = 'ASC') {
    $this->order[$field] = strtoupper($direction);
    return $this;
  }

  // ================== we could support these.
  /**
   * {@inheritdoc}
   */
  public function groupBy($field) {
    // @todo: Implement groupBy() method.
    throw new \Exception(sprintf('Method "%s" is not supported', __METHOD__));
  }

  /**
   * {@inheritdoc}
   */
  public function havingCondition($field, $value = NULL, $operator = NULL) {
    // @todo: Implement havingCondition() method.
    throw new \Exception(sprintf('Method "%s" is not supported', __METHOD__));
  }

  /**
   * {@inheritdoc}
   */
  public function uniqueIdentifier() {
    // TODO: Implement uniqueIdentifier() method.
    throw new \Exception(sprintf('Method "%s" is not supported', __METHOD__));
  }

  // ================== the rest won't be supported, ever.
  /**
   * {@inheritdoc}
   */
  public function nextPlaceholder() {
    // TODO: Implement nextPlaceholder() method.
    throw new \Exception(sprintf('Method "%s" is not supported', __METHOD__));
  }

  /**
   * {@inheritdoc}
   */
  public function isPrepared() {
    throw new \Exception(sprintf('Method "%s" is not supported', __METHOD__));
  }

  /**
   * {@inheritdoc}
   */
  public function preExecute(SelectInterface $query = NULL) {
    throw new \Exception(sprintf('Method "%s" is not supported', __METHOD__));
  }

  /**
   * {@inheritdoc}
   */
  public function where($snippet, $args = array()) {
    throw new \Exception(sprintf('Method "%s" is not supported', __METHOD__));
  }

  /**
   * {@inheritdoc}
   */
  public function extend($extender_name) {
    throw new \Exception(sprintf('Method "%s" is not supported', __METHOD__));
  }

  /**
   * {@inheritdoc}
   */
  public function &getExpressions() {
    throw new \Exception(sprintf('Method "%s" is not supported', __METHOD__));
  }

  /**
   * {@inheritdoc}
   */
  public function &getGroupBy() {
    throw new \Exception(sprintf('Method "%s" is not supported', __METHOD__));
  }

  /**
   * {@inheritdoc}
   */
  public function &getUnion() {
    throw new \Exception(sprintf('Method "%s" is not supported', __METHOD__));
  }

  /**
   * {@inheritdoc}
   */
  public function forUpdate($set = TRUE) {
    throw new \Exception(sprintf('Method "%s" is not supported', __METHOD__));
  }

  /**
   * {@inheritdoc}
   */
  public function rightJoin($table, $alias = NULL, $condition = NULL, $arguments = array()) {
    throw new \Exception(sprintf('Method "%s" is not supported', __METHOD__));
  }

  /**
   * {@inheritdoc}
   */
  public function &conditions() {
    throw new \Exception(sprintf('Method "%s" is not supported', __METHOD__));
  }

  /**
   * {@inheritdoc}
   */
  public function orderRandom() {
    // We could implement this but why bother.
    throw new \Exception(sprintf('Method "%s" is not supported', __METHOD__));
  }

  /**
   * {@inheritdoc}
   */
  public function union(SelectInterface $query, $type = '') {
    throw new \Exception(sprintf('Method "%s" is not supported', __METHOD__));
  }

  /**
   * {@inheritdoc}
   */
  public function addExpression($expression, $alias = NULL, $arguments = array()) {
    throw new \Exception(sprintf('Method "%s" is not supported', __METHOD__));
  }

  /**
   * {@inheritdoc}
   */
  public function &getTables() {
    throw new \Exception(sprintf('Method "%s" is not supported', __METHOD__));
  }

  /**
   * {@inheritdoc}
   */
  public function getArguments(PlaceholderInterface $query_place_holder = NULL) {
    throw new \Exception(sprintf('Method "%s" is not supported', __METHOD__));
  }

  /**
   * {@inheritdoc}
   */
  public function &getOrderBy() {
    throw new \Exception(sprintf('Method "%s" is not supported', __METHOD__));
  }

  /**
   * {@inheritdoc}
   */
  public function &getFields() {
    throw new \Exception(sprintf('Method "%s" is not supported', __METHOD__));
  }

  /**
   * {@inheritdoc}
   */
  public function exists(SelectInterface $select) {
    throw new \Exception(sprintf('Method "%s" is not supported', __METHOD__));
  }

  /**
   * {@inheritdoc}
   */
  public function notExists(SelectInterface $select) {
    throw new \Exception(sprintf('Method "%s" is not supported', __METHOD__));
  }

  /**
   * {@inheritdoc}
   */
  public function arguments() {
    throw new \Exception(sprintf('Method "%s" is not supported', __METHOD__));
  }

  /**
   * {@inheritdoc}
   */
  public function compile(Connection $connection, PlaceholderInterface $query_place_holder) {
    throw new \Exception(sprintf('Method "%s" is not supported', __METHOD__));
  }

  /**
   * {@inheritdoc}
   */
  public function compiled() {
    throw new \Exception(sprintf('Method "%s" is not supported', __METHOD__));
  }

  /**
   * {@inheritdoc}
   */
  public function fields($table_alias, array $fields = array()) {
    if (!$fields) {
      $table = $this->tables[$table_alias]['table'];
      if (!empty($this->databaseContents[$table])) {
        $fields = array_keys(reset($this->databaseContents[$table]));
      }
      else {
        throw new \Exception(SafeMarkup::format('All fields on empty table @table is not supported.', array('@table' => $table)));
      }
    }
    return parent::fields($table_alias, $fields);
  }
}
