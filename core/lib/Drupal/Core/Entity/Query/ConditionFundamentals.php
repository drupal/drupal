<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Query\ConditionFundamentals.
 */

namespace Drupal\Core\Entity\Query;

/**
 * Common code for all implementations of the entity query condition interfaces.
 */
abstract class ConditionFundamentals {

  /**
   * Array of conditions.
   *
   * @var array
   */
  protected $conditions = array();

  /**
   * The conjunction of this condition group. The value is one of the following:
   *
   * - AND (default)
   * - OR
   *
   * @var string
   */
  protected $conjunction;

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
   * Implements \Drupal\Core\Entity\Query\ConditionInterface::getConjunction().
   */
  public function getConjunction() {
    return $this->conjunction;
  }

  /**
   * Implements \Countable::count().
   */
  public function count() {
    return count($this->conditions) - 1;
  }

  /**
   * Implements \Drupal\Core\Entity\Query\ConditionInterface::conditions().
   */
  public function &conditions() {
    return $this->conditions;
  }

  /**
   * Implements the magic __clone function.
   *
   * Makes sure condition groups are cloned as well.
   */
  public function __clone() {
    foreach ($this->conditions as $key => $condition) {
      if ($condition['field'] instanceOf ConditionInterface) {
        $this->conditions[$key]['field'] = clone($condition['field']);
      }
    }
  }

}
