<?php

namespace Drupal\new_dependency_test;

/**
 * Service that gets the other service of the same module injected.
 *
 * This service indirectly depends on a not-yet-defined service.
 */
class ServiceWithDependency {

  /**
   * The injected service.
   *
   * @var \Drupal\new_dependency_test\InjectedService
   */
  protected $service;

  /**
   * ServiceWithDependency constructor.
   *
   * @param \Drupal\new_dependency_test\InjectedService|null $service
   *   The service of the same module which has the new dependency.
   */
  public function __construct(?InjectedService $service = NULL) {
    $this->service = $service;
  }

  /**
   * Gets a greeting from the injected service and adds to it.
   *
   * @return string
   *   The greeting.
   */
  public function greet() {
    if (isset($this->service)) {
      return $this->service->greet() . ' World';
    }
    return 'Sorry, no service.';
  }

}
