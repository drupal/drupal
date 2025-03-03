<?php

declare(strict_types=1);

namespace Drupal\views_test_data\Plugin\views\join;

use Drupal\views\Attribute\ViewsJoin;
use Drupal\views\Plugin\views\join\JoinPluginBase;

/**
 * Defines a join test plugin.
 */
#[ViewsJoin("join_test")]
class JoinTest extends JoinPluginBase {
  /**
   * A value which is used to build an additional join condition.
   *
   * @var int
   */
  protected $joinValue;

  /**
   * Returns the joinValue property.
   *
   * @return int
   *   The value of the join.
   */
  public function getJoinValue() {
    return $this->joinValue;
  }

  /**
   * Sets the joinValue property.
   *
   * @param int $join_value
   *   The value of the join.
   */
  public function setJoinValue($join_value) {
    $this->joinValue = $join_value;
  }

  /**
   * {@inheritdoc}
   */
  public function buildJoin($select_query, $table, $view_query) {
    // Add an additional hardcoded condition to the query.
    $this->extra = 'views_test_data.uid = ' . $this->getJoinValue();
    parent::buildJoin($select_query, $table, $view_query);
  }

}
