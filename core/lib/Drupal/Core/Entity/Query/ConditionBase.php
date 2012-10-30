<?php

/**
 * @file
 * Definition of Drupal\Core\Entity\Query\ConditionBase.
 */

namespace Drupal\Core\Entity\Query;

/**
 * Common code for all implementations of the entity query condition interface.
 */
abstract class ConditionBase implements ConditionInterface {

  /**
   * Array of conditions.
   *
   * @var array
   */
  protected $conditions = array();

  /**
   * Constructs a Condition object.
   *
   * @param string $conjunction
   *   The operator to use to combine conditions: 'AND' or 'OR'.
   */
  public function __construct($conjunction = 'AND') {
    $this->conjunction = $conjunction;
  }

  /**
   * Implements Drupal\Core\Entity\Query\ConditionInterface::getConjunction().
   */
  public function getConjunction() {
    return $this->conjunction;
  }

  /**
   * Implements Countable::count().
   */
  public function count() {
    return count($this->conditions) - 1;
  }

  /**
   * Implements Drupal\Core\Entity\Query\ConditionInterface::compile().
   */
  public function condition($field, $value = NULL, $operator = NULL, $langcode = NULL) {
    $this->conditions[] = array(
      'field' => $field,
      'value' => $value,
      'operator' => $operator,
      'langcode' => $langcode,
    );

    return $this;
  }

  /**
   * Implements Drupal\Core\Entity\Query\ConditionInterface::conditions().
   */
  public function &conditions() {
    return $this->conditions;
  }

  /**
   * Makes sure condition groups are cloned as well.
   */
  function __clone() {
    foreach ($this->conditions as $key => $condition) {
      if ($condition['field'] instanceOf ConditionInterface) {
        $this->conditions[$key]['field'] = clone($condition['field']);
      }
    }
  }

}
