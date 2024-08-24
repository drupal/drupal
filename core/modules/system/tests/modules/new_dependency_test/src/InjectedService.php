<?php

declare(strict_types=1);

namespace Drupal\new_dependency_test;

use Drupal\new_dependency_test_with_service\NewService;

/**
 * Generic service with a dependency on a service defined in a new module.
 */
class InjectedService {

  /**
   * The injected service.
   *
   * @var \Drupal\new_dependency_test_with_service\NewService
   */
  protected $service;

  /**
   * InjectedService constructor.
   *
   * @param \Drupal\new_dependency_test_with_service\NewService $service
   *   The service of the new module.
   */
  public function __construct(NewService $service) {
    $this->service = $service;
  }

  /**
   * Get the simple greeting from the service.
   *
   * @return string
   *   The greeting.
   */
  public function greet() {
    return $this->service->greet();
  }

}
