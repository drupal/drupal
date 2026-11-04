<?php

declare(strict_types=1);

namespace Drupal\new_dependency_test;

/**
 * A service that can decorated itself.
 *
 * @see new_dependency_test.services.yml
 */
class Service {

  /**
   * Service constructor.
   *
   * @param \Drupal\new_dependency_test\Service|null $inner
   *   The service to decorate.
   */
  public function __construct(
    protected ?Service $inner = NULL,
  ) {
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
