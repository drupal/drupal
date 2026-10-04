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
  public static function callback(callable $callback, array $args, string $message, $extra_trusted_interface = NULL) {
    if (func_num_args() > 4) {
      @trigger_error('Calling Drupal\\Core\\Security\\StaticTrustedCallbackHelper::callback() with 5 arguments is deprecated in drupal:11.5.0 and is removed from drupal:13.0.0. See https://www.drupal.org/node/3627046', E_USER_DEPRECATED);
      $extra_trusted_interface = func_get_arg(4);
    }
    return (new static())->doTrustedCallback($callback, $args, $message, $extra_trusted_interface);
  }

}
