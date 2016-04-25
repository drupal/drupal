<?php

namespace Drupal\Core\Database\Query;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\InvalidQueryException;

/**
 * Generic class for a series of conditions in a query.
 */
class Condition implements ConditionInterface, \Countable {

  /**
   * Array of conditions.
   *
   * @var array
   */
  protected $conditions = array();

  /**
   * Array of arguments.
   *
   * @var array
   */
  protected $arguments = array();

  /**
   * Whether the conditions have been changed.
   *
   * TRUE if the condition has been changed since the last compile.
   * FALSE if the condition has been compiled and not changed.
   *
   * @var bool
   */
  protected $changed = TRUE;

  /**
   * The identifier of the query placeholder this condition has been compiled against.
   */
  protected $queryPlaceholderIdentifier;

  /**
   * Constructs a Condition object.
   *
   * @param string $conjunction
   *   The operator to use to combine conditions: 'AND' or 'OR'.
   */
  public function __construct($conjunction) {
    $this->conditions['#conjunction'] = $conjunction;
  }

  /**
   * Implements Countable::count().
   *
   * Returns the size of this conditional. The size of the conditional is the
   * size of its conditional array minus one, because one element is the
   * conjunction.
   */
  public function count() {
    return count($this->conditions) - 1;
  }

  /**
   * {@inheritdoc}
   */
  public function condition($field, $value = NULL, $operator = '=') {
    if (empty($operator)) {
      $operator = '=';
    }
    if (empty($value) && is_array($value)) {
      throw new InvalidQueryException(sprintf("Query condition '%s %s ()' cannot be empty.", $field, $operator));
    }

    $this->conditions[] = array(
      'field' => $field,
      'value' => $value,
      'operator' => $operator,
    );

    $this->changed = TRUE;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function where($snippet, $args = array()) {
    $this->conditions[] = array(
      'field' => $snippet,
      'value' => $args,
      'operator' => NULL,
    );
    $this->changed = TRUE;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isNull($field) {
    return $this->condition($field, NULL, 'IS NULL');
  }

  /**
   * {@inheritdoc}
   */
  public function isNotNull($field) {
    return $this->condition($field, NULL, 'IS NOT NULL');
  }

  /**
   * {@inheritdoc}
   */
  public function exists(SelectInterface $select) {
    return $this->condition('', $select, 'EXISTS');
  }

  /**
   * {@inheritdoc}
   */
  public function notExists(SelectInterface $select) {
    return $this->condition('', $select, 'NOT EXISTS');
  }

  /**
   * {@inheritdoc}
   */
  public function &conditions() {
    return $this->conditions;
  }

  /**
   * {@inheritdoc}
   */
  public function arguments() {
    // If the caller forgot to call compile() first, refuse to run.
    if ($this->changed) {
      return NULL;
    }
    return $this->arguments;
  }

  /**
   * {@inheritdoc}
   */
  public function compile(Connection $connection, PlaceholderInterface $queryPlaceholder) {
    // Re-compile if this condition changed or if we are compiled against a
    // different query placeholder object.
    if ($this->changed || isset($this->queryPlaceholderIdentifier) && ($this->queryPlaceholderIdentifier != $queryPlaceholder->uniqueIdentifier())) {
      $this->queryPlaceholderIdentifier = $queryPlaceholder->uniqueIdentifier();

      $condition_fragments = array();
      $arguments = array();

      $conditions = $this->conditions;
      $conjunction = $conditions['#conjunction'];
      unset($conditions['#conjunction']);
      foreach ($conditions as $condition) {
        if (empty($condition['operator'])) {
          // This condition is a literal string, so let it through as is.
          $condition_fragments[] = ' (' . $condition['field'] . ') ';
          $arguments += $condition['value'];
        }
        else {
          // It's a structured condition, so parse it out accordingly.
          // Note that $condition['field'] will only be an object for a dependent
          // DatabaseCondition object, not for a dependent subquery.
          if ($condition['field'] instanceof ConditionInterface) {
            // Compile the sub-condition recursively and add it to the list.
            $condition['field']->compile($connection, $queryPlaceholder);
            $condition_fragments[] = '(' . (string) $condition['field'] . ')';
            $arguments += $condition['field']->arguments();
          }
          else {
            // For simplicity, we treat all operators as the same data structure.
            // In the typical degenerate case, this won't get changed.
            $operator_defaults = array(
              'prefix' => '',
              'postfix' => '',
              'delimiter' => '',
              'operator' => $condition['operator'],
              'use_value' => TRUE,
            );
            // Remove potentially dangerous characters.
            // If something passed in an invalid character stop early, so we
            // don't rely on a broken SQL statement when we would just replace
            // those characters.
            if (stripos($condition['operator'], 'UNION') !== FALSE || strpbrk($condition['operator'], '[-\'"();') !== FALSE) {
              $this->changed = TRUE;
              $this->arguments = [];
              // Provide a string which will result into an empty query result.
              $this->stringVersion = '( AND 1 = 0 )';

              // Conceptually throwing an exception caused by user input is bad
              // as you result into a WSOD, which depending on your webserver
              // configuration can result into the assumption that your site is
              // broken.
              // On top of that the database API relies on __toString() which
              // does not allow to throw exceptions.
              trigger_error('Invalid characters in query operator: ' . $condition['operator'], E_USER_ERROR);
              return;
            }
            $operator = $connection->mapConditionOperator($condition['operator']);
            if (!isset($operator)) {
              $operator = $this->mapConditionOperator($condition['operator']);
            }
            $operator += $operator_defaults;

            $placeholders = array();
            if ($condition['value'] instanceof SelectInterface) {
              $condition['value']->compile($connection, $queryPlaceholder);
              $placeholders[] = (string) $condition['value'];
              $arguments += $condition['value']->arguments();
              // Subqueries are the actual value of the operator, we don't
              // need to add another below.
              $operator['use_value'] = FALSE;
            }
            // We assume that if there is a delimiter, then the value is an
            // array. If not, it is a scalar. For simplicity, we first convert
            // up to an array so that we can build the placeholders in the same way.
            elseif (!$operator['delimiter'] && !is_array($condition['value'])) {
              $condition['value'] = array($condition['value']);
            }
            if ($operator['use_value']) {
              foreach ($condition['value'] as $value) {
                $placeholder = ':db_condition_placeholder_' . $queryPlaceholder->nextPlaceholder();
                $arguments[$placeholder] = $value;
                $placeholders[] = $placeholder;
              }
            }
            $condition_fragments[] = ' (' . $connection->escapeField($condition['field']) . ' ' . $operator['operator'] . ' ' . $operator['prefix'] . implode($operator['delimiter'], $placeholders) . $operator['postfix'] . ') ';
          }
        }
      }

      $this->changed = FALSE;
      $this->stringVersion = implode($conjunction, $condition_fragments);
      $this->arguments = $arguments;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function compiled() {
    return !$this->changed;
  }

  /**
   * Implements PHP magic __toString method to convert the conditions to string.
   *
   * @return string
   *   A string version of the conditions.
   */
  public function __toString() {
    // If the caller forgot to call compile() first, refuse to run.
    if ($this->changed) {
      return '';
    }
    return $this->stringVersion;
  }

  /**
   * PHP magic __clone() method.
   *
   * Only copies fields that implement Drupal\Core\Database\Query\ConditionInterface. Also sets
   * $this->changed to TRUE.
   */
  function __clone() {
    $this->changed = TRUE;
    foreach ($this->conditions as $key => $condition) {
      if ($key !== '#conjunction') {
        if ($condition['field'] instanceof ConditionInterface) {
          $this->conditions[$key]['field'] = clone($condition['field']);
        }
        if ($condition['value'] instanceof SelectInterface) {
          $this->conditions[$key]['value'] = clone($condition['value']);
        }
      }
    }
  }

  /**
   * Gets any special processing requirements for the condition operator.
   *
   * Some condition types require special processing, such as IN, because
   * the value data they pass in is not a simple value. This is a simple
   * overridable lookup function.
   *
   * @param $operator
   *   The condition operator, such as "IN", "BETWEEN", etc. Case-sensitive.
   *
   * @return array
   *   The extra handling directives for the specified operator or an empty
   *   array if there are no extra handling directives.
   */
  protected function mapConditionOperator($operator) {
    // $specials does not use drupal_static as its value never changes.
    static $specials = array(
      'BETWEEN' => array('delimiter' => ' AND '),
      'IN' => array('delimiter' => ', ', 'prefix' => ' (', 'postfix' => ')'),
      'NOT IN' => array('delimiter' => ', ', 'prefix' => ' (', 'postfix' => ')'),
      'EXISTS' => array('prefix' => ' (', 'postfix' => ')'),
      'NOT EXISTS' => array('prefix' => ' (', 'postfix' => ')'),
      'IS NULL' => array('use_value' => FALSE),
      'IS NOT NULL' => array('use_value' => FALSE),
      // Use backslash for escaping wildcard characters.
      'LIKE' => array('postfix' => " ESCAPE '\\\\'"),
      'NOT LIKE' => array('postfix' => " ESCAPE '\\\\'"),
      // These ones are here for performance reasons.
      '=' => array(),
      '<' => array(),
      '>' => array(),
      '>=' => array(),
      '<=' => array(),
    );
    if (isset($specials[$operator])) {
      $return = $specials[$operator];
    }
    else {
      // We need to upper case because PHP index matches are case sensitive but
      // do not need the more expensive Unicode::strtoupper() because SQL statements are ASCII.
      $operator = strtoupper($operator);
      $return = isset($specials[$operator]) ? $specials[$operator] : array();
    }

    $return += array('operator' => $operator);

    return $return;
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
