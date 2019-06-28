<?php

namespace Drupal\new_dependency_test;

/**
 * Service that gets the other service of the same module injected.
 *
 * This service indirectly depends on a not-yet-defined service.
 */
class DecoratedDependentService {

  /**
   * The injected service.
   *
   * @var \Drupal\new_dependency_test\DependentService
   */
  protected $service;

  /**
   * DecoratedDependentService constructor.
   *
   * @param \Drupal\new_dependency_test\DependentService|null $service
   *   The service of the same module which has the new dependency.
   */
  public function __construct(DependentService $service = NULL) {
    $this->service = $service;
  }

  /**
   * Get the simple greeting from the service and decorate it.
   *
   * @return string
   *   The enhanced greeting.
   */
  public function greet() {
    if (isset($this->service)) {
      return $this->service->greet() . ' World';
    }
    return 'Sorry, no service.';
  }

}
