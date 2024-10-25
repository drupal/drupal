<?php

declare(strict_types=1);

namespace Drupal\package_manager_bypass;

/**
 * Trait to make Composer Stager throw pre-determined exceptions in tests.
 *
 * @internal
 */
trait ComposerStagerExceptionTrait {

  /**
   * Sets an exception to be thrown.
   *
   * @param string|null $class
   *   The class of exception to throw, or NULL to delete a stored exception.
   * @param mixed ...$arguments
   *   Arguments to pass to the exception constructor.
   */
  public static function setException(?string $class = \Exception::class, mixed ...$arguments): void {
    if ($class) {
      \Drupal::state()->set(static::class . '-exception', func_get_args());
    }
    else {
      \Drupal::state()->delete(static::class . '-exception');
    }
  }

  /**
   * Throws the exception if set.
   */
  private function throwExceptionIfSet(): void {
    if ($exception = $this->state->get(static::class . '-exception')) {
      $class = array_shift($exception);
      throw new $class(...$exception);
    }
  }

}
