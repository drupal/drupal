<?php

/**
 * @file
 * Contains \Drupal\views_test_data\Plugin\views\filter\FilterExceptionTest.
 */

namespace Drupal\views_test_data\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\FilterPluginBase;

/**
 * Breaks the query with adding an invalid where expression.
 *
 * @ViewsFilter("test_exception_filter")
 */
class FilterExceptionTest extends FilterPluginBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->query->addWhereExpression(NULL, "syntax error");
  }

}
