<?php

namespace Drupal\views\Plugin\views\filter;

/**
 * Provides an interface for all views filters that implement operators.
 */
interface FilterOperatorsInterface {

  /**
   * Returns an array of operator information, keyed by operator ID.
   *
   * @return array[]
   */
  public function operators();

}
