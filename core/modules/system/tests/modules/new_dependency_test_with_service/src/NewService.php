<?php

namespace Drupal\new_dependency_test_with_service;

/**
 * Generic service returning a greeting.
 */
class NewService {

  /**
   * Get a simple greeting.
   *
   * @return string
   *   The greeting provided by the new service.
   */
  public function greet() {
    return 'Hello';
  }

}
