<?php

declare(strict_types=1);

namespace Drupal\package_manager_bypass;

/**
 * Records information about method invocations.
 *
 * This can be used by functional tests to ensure that the bypassed Composer
 * Stager services were called as expected. Kernel and unit tests should use
 * regular mocks instead.
 *
 * @internal
 */
trait LoggingDecoratorTrait {

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  private $state;

  /**
   * Returns the arguments from every invocation of the main class method.
   *
   * @return mixed[]
   *   The arguments from every invocation of the main class method.
   */
  public function getInvocationArguments(): array {
    return $this->state->get(static::class . ' arguments', []);
  }

  /**
   * Records the arguments from an invocation of the main class method.
   *
   * @param mixed ...$arguments
   *   The arguments that the main class method was called with.
   */
  private function saveInvocationArguments(...$arguments): void {
    $invocations = $this->getInvocationArguments();
    $invocations[] = $arguments;
    $this->state->set(static::class . ' arguments', $invocations);
  }

}
