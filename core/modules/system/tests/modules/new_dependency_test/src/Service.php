<?php

namespace Drupal\new_dependency_test;

/**
 * A service that can decorated itself.
 *
 * @see new_dependency_test.services.yml
 */
class Service {

  /**
   * The decorated service.
   *
   * @var \Drupal\new_dependency_test\Service
   */
  protected $inner;

  /**
   * Service constructor.
   *
   * @param \Drupal\new_dependency_test\Service|null $inner
   *   The service to decorate.
   */
  public function __construct(Service $inner = NULL) {
    $this->inner = $inner;
  }

  /**
   * Determines if the service is decorated.
   *
   * @return bool
   *   TRUE if the services is decorated, FALSE if not.
   */
  public function isDecorated() {
    return isset($this->inner);
  }

}
