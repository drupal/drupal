<?php

namespace Drupal\Core\Security;

/**
 * Defines a class for performing trusted callbacks in a static context.
 */
class StaticTrustedCallbackHelper {

  use DoTrustedCallbackTrait;

  /**
   * Performs a callback.
   *
   * @param callable $callback
   *   The callback to call. Note that callbacks which are objects and use the
   *   magic method __invoke() are not supported.
   * @param array $args
   *   The arguments to pass the callback.
   * @param string $message
   *   The error message if the callback is not trusted. If the message contains
   *   "%s" it will be replaced in with the resolved callback.
   * @param string $error_type
   *   (optional) The type of error to trigger. One of:
   *   - TrustedCallbackInterface::THROW_EXCEPTION
   *   - TrustedCallbackInterface::TRIGGER_SILENCED_DEPRECATION
   *   Defaults to TrustedCallbackInterface::THROW_EXCEPTION.
   * @param string $extra_trusted_interface
   *   (optional) An additional interface that if implemented by the callback
   *   object means any public methods on that object are trusted.
   *
   * @return mixed
   *   The callback's return value.
   *
   * @throws \Drupal\Core\Security\UntrustedCallbackException
   *   Exception thrown if the callback is not trusted and $error_type equals
   *   TrustedCallbackInterface::THROW_EXCEPTION.
   *
   * @see \Drupal\Core\Security\TrustedCallbackInterface
   * @see \Drupal\Core\Security\DoTrustedCallbackTrait::doTrustedCallback()
   */
  public static function callback(callable $callback, array $args, string $message, $error_type = TrustedCallbackInterface::THROW_EXCEPTION, $extra_trusted_interface = NULL) {
    return (new static())->doTrustedCallback($callback, $args, $message, $error_type, $extra_trusted_interface);
  }

}
