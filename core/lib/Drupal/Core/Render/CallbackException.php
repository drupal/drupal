<?php

namespace Drupal\Core\Render;

class CallbackException extends \LogicException {

  public function __construct(callable $callable, $message = '', $code = 0, \Throwable $previous = NULL) {
    $message = str_replace('@callable', static::serializeCallable($callable), $message);
    parent::__construct($message, $code, $previous);
  }

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
