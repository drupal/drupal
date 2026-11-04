<?php

declare(strict_types=1);

namespace Drupal\new_dependency_test;

/**
 * Service that gets the other service of the same module injected.
 *
 * This service indirectly depends on a not-yet-defined service.
 */
class ServiceWithDependency {

  /**
   * ServiceWithDependency constructor.
   *
   * @param \Drupal\new_dependency_test\InjectedService|null $service
   *   The service of the same module which has the new dependency.
   */
  public function __construct(
    protected ?InjectedService $service = NULL,
  ) {
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
    return 'No service.';
  }

}
