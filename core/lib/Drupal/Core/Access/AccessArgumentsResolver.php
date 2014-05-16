<?php

/**
 * @file
 * Contains \Drupal\Core\Access\AccessArgumentsResolver.
 */

namespace Drupal\Core\Access;

use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Resolves the arguments to pass to an access check callable.
 */
class AccessArgumentsResolver implements AccessArgumentsResolverInterface {

  /**
   * {@inheritdoc}
   */
  public function getArguments(callable $callable, Route $route, Request $request, AccountInterface $account) {
    $arguments = array();
    foreach ($this->getReflector($callable)->getParameters() as $parameter) {
      $arguments[] = $this->getArgument($parameter, $route, $request, $account);
    }
    return $arguments;
  }

  /**
   * Returns the argument value for a parameter.
   *
   * @param \ReflectionParameter $parameter
   *   The parameter of a callable to get the value for.
   * @param \Symfony\Component\Routing\Route $route
   *   The access checked route.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   *
   * @return mixed
   *   The value of the requested parameter value.
   *
   * @throws \RuntimeException
   *   Thrown when there is a missing parameter.
   */
  protected function getArgument(\ReflectionParameter $parameter, Route $route, Request $request, AccountInterface $account) {
    $upcasted_route_arguments = $request->attributes->all();
    $raw_route_arguments = isset($upcasted_route_arguments['_raw_variables']) ? $upcasted_route_arguments['_raw_variables']->all() : $upcasted_route_arguments;
    $parameter_type_hint = $parameter->getClass();
    $parameter_name = $parameter->getName();

    // @todo Remove this once AccessManager::checkNamedRoute() is fixed to not
    //   leak _raw_variables from the request being duplicated.
    // @see https://drupal.org/node/2265939
    $raw_route_arguments += $upcasted_route_arguments;

    // If the route argument exists and is NULL, return it, regardless of
    // parameter type hint.
    if (!isset($upcasted_route_arguments[$parameter_name]) && array_key_exists($parameter_name, $upcasted_route_arguments)) {
      return NULL;
    }

    if ($parameter_type_hint) {
      // If the argument exists and complies with the type hint, return it.
      if (isset($upcasted_route_arguments[$parameter_name]) && is_object($upcasted_route_arguments[$parameter_name]) && $parameter_type_hint->isInstance($upcasted_route_arguments[$parameter_name])) {
        return $upcasted_route_arguments[$parameter_name];
      }
      // Otherwise, resolve $request, $route, and $account by type matching
      // only. This way, the callable may rename them in case the route
      // defines other parameters with these names.
      foreach (array($request, $route, $account) as $special_argument) {
        if ($parameter_type_hint->isInstance($special_argument)) {
          return $special_argument;
        }
      }
    }
    elseif (isset($raw_route_arguments[$parameter_name])) {
      return $raw_route_arguments[$parameter_name];
    }

    // If the callable provides a default value, use it.
    if ($parameter->isDefaultValueAvailable()) {
      return $parameter->getDefaultValue();
    }

    // Can't resolve it: call a method that throws an exception or can be
    // overridden to do something else.
    return $this->handleUnresolvedArgument($parameter);
  }

  /**
   * Returns a reflector for the access check callable.
   *
   * The access checker may be either a procedural function (in which case the
   * callable is the function name) or a method (in which case the callable is
   * an array of the object and method name).
   *
   * @param callable $callable
   *   The callable (either a function or a method).
   *
   * @return \ReflectionFunctionAbstract
   *   The ReflectionMethod or ReflectionFunction to introspect the callable.
   */
  protected function getReflector(callable $callable) {
    return is_array($callable) ? new \ReflectionMethod($callable[0], $callable[1]) : new \ReflectionFunction($callable);
  }

  /**
   * Handles unresolved arguments for getArgument().
   *
   * Subclasses that override this method may return a default value
   * instead of throwing an exception.
   *
   * @throws \RuntimeException
   *   Thrown when there is a missing parameter.
   */
  protected function handleUnresolvedArgument(\ReflectionParameter $parameter) {
    $class = $parameter->getDeclaringClass();
    $function = $parameter->getDeclaringFunction();
    if ($class && !$function->isClosure()) {
      $function_name = $class->getName() . '::' . $function->getName();
    }
    else {
      $function_name = $function->getName();
    }
    throw new \RuntimeException(sprintf('Access callable "%s" requires a value for the "$%s" argument.', $function_name, $parameter->getName()));
  }

}
