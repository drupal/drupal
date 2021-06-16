<?php

namespace Drupal\views_test_data\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\BooleanOperator;

/**
 * Filter to test queryOpBoolean() with default operator.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("boolean_default")
 */
class FilterBooleanOperatorDefaultTest extends BooleanOperator {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();
    $field = "$this->tableAlias.$this->realField";
    $this->queryOpBoolean($field);
  }

}
