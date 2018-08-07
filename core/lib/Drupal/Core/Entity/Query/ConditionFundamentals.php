<?php

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
  protected $conditions = [];

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
   * The query this condition belongs to.
   *
   * @var \Drupal\Core\Entity\Query\QueryInterface
   */
  protected $query;

  /**
   * List of potential namespaces of the classes belonging to this condition.
   *
   * @var array
   */
  protected $namespaces = [];

  /**
   * Constructs a Condition object.
   *
   * @param string $conjunction
   *   The operator to use to combine conditions: 'AND' or 'OR'.
   * @param QueryInterface $query
   *   The entity query this condition belongs to.
   * @param array $namespaces
   *   List of potential namespaces of the classes belonging to this condition.
   */
  public function __construct($conjunction, QueryInterface $query, $namespaces = []) {
    $this->conjunction = $conjunction;
    $this->query = $query;
    $this->namespaces = $namespaces;
  }

  /**
   * {@inheritdoc}
   */
  public function getConjunction() {
    return $this->conjunction;
  }

  /**
   * {@inheritdoc}
   */
  public function count() {
    return count($this->conditions);
  }

  /**
   * {@inheritdoc}
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
      if ($condition['field'] instanceof ConditionInterface) {
        $this->conditions[$key]['field'] = clone($condition['field']);
      }
    }
  }

}
