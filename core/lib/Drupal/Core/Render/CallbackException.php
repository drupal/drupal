<?php

namespace Drupal\Core\Render;

/**
 * Indicates that a render callback returned an unexpected value.
 */
class CallbackException extends \LogicException {

  /**
   * CallbackException constructor.
   *
   * @param callable $callable
   *   The callable which caused the error.
   * @param string $message
   *   (optional) The exception message. If "@callable" appears in it, it will
   *   be replaced by a human-readable name for the callable.
   * @param int $code
   *   (optional) The exception code.
   * @param \Throwable|null $previous
   *   (optional) The previous exception, if any.
   */
  public function __construct(callable $callable, $message = '', $code = 0, \Throwable $previous = NULL) {
    $message = str_replace('@callable', static::serializeCallable($callable), $message);
    parent::__construct($message, $code, $previous);
  }

  /**
   * Returns a human-readable name for a callable.
   *
   * @param callable $callable
   *   A callable.
   *
   * @return string
   *   A human-readable name for the callable.
   */
  protected static function serializeCallable(callable $callable): string {
    if ($callable instanceof \Closure) {
      return '[closure]';
    }
    elseif (is_array($callable)) {
      if (is_object($callable[0])) {
        $callable[0] = get_class($callable[0]);
      }
      return implode('::', $callable);
    }
    elseif (is_string($callable)) {
      return $callable;
    }
    else {
      return '[unknown]';
    }
  }

}
