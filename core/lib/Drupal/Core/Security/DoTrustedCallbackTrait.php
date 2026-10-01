<?php

namespace Drupal\Core\Security;

use Drupal\Core\Security\Attribute\TrustedCallback;

/**
 * Ensures that only predefined methods can be used as callback methods.
 *
 * @see \Drupal\Core\Security\Attribute\TrustedCallback
 * @see \Drupal\Core\Security\TrustedCallbackInterface
 */
trait DoTrustedCallbackTrait {

  /**
   * Performs a callback.
   *
   * If the callback is trusted the callback will occur. Trusted callbacks must
   * be methods that are tagged with the
   * \Drupal\Core\Security\Attribute\TrustedCallback attribute, or be methods of
   * a class that implements
   * \Drupal\Core\Security\TrustedCallbackInterface or $extra_trusted_interface,
   * or be an anonymous function. If the callback is not trusted an
   * UntrustedCallbackException is thrown.
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
   *   Thrown if the callback is not trusted.
   *
   * @see \Drupal\Core\Security\Attribute\TrustedCallback
   * @see \Drupal\Core\Security\TrustedCallbackInterface
   */
  public function doTrustedCallback(callable $callback, array $args, $message, $extra_trusted_interface = NULL) {
    if (func_num_args() > 4) {
      @trigger_error('Calling Drupal\\Core\\Security\\StaticTrustedCallbackHelper::callback() with 5 arguments is deprecated in drupal:11.5.0 and is removed from drupal:13.0.0. See https://www.drupal.org/node/3627046', E_USER_DEPRECATED);
      $extra_trusted_interface = func_get_arg(4);
    }
    $object_or_classname = $callback;
    $safe_callback = FALSE;

    if (is_array($callback)) {
      [$object_or_classname, $method_name] = $callback;
    }
    elseif (is_string($callback) && str_contains($callback, '::')) {
      [$object_or_classname, $method_name] = explode('::', $callback, 2);
    }

    if (isset($method_name)) {
      if ($extra_trusted_interface && is_subclass_of($object_or_classname, $extra_trusted_interface)) {
        $safe_callback = TRUE;
      }
      elseif (is_subclass_of($object_or_classname, TrustedCallbackInterface::class)) {
        if (is_object($object_or_classname)) {
          $methods = $object_or_classname->trustedCallbacks();
        }
        else {
          $methods = call_user_func($object_or_classname . '::trustedCallbacks');
        }
        $safe_callback = in_array($method_name, $methods, TRUE);
      }
      if (!$safe_callback) {
        $method = new \ReflectionMethod($object_or_classname, $method_name);
        $safe_callback = (bool) $method->getAttributes(TrustedCallback::class);
      }
    }
    elseif ($callback instanceof \Closure) {
      $safe_callback = TRUE;
    }

    if (!$safe_callback) {
      $description = $object_or_classname;
      if (is_object($description)) {
        $description = get_class($description);
      }
      if (isset($method_name)) {
        $description .= '::' . $method_name;
      }
      $message = sprintf($message, $description);
      throw new UntrustedCallbackException($message);
    }

    // @todo Allow named arguments in https://www.drupal.org/node/3174150
    return call_user_func_array($callback, array_values($args));
  }

}
