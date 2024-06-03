<?php

namespace Drupal\Core\Access;

use Drupal\Component\Utility\ArgumentsResolver;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Resolves the arguments to pass to an access check callable.
 */
class AccessArgumentsResolverFactory implements AccessArgumentsResolverFactoryInterface {

  /**
   * {@inheritdoc}
   */
  public function getArgumentsResolver(RouteMatchInterface $route_match, AccountInterface $account, ?Request $request = NULL) {
    $route = $route_match->getRouteObject();

    // Defaults for the parameters defined on the route object need to be added
    // to the raw arguments.
    $raw_route_arguments = $route_match->getRawParameters()->all() + $route->getDefaults();

    $upcasted_route_arguments = $route_match->getParameters()->all();

    // Parameters which are not defined on the route object, but still are
    // essential for access checking are passed as wildcards to the argument
    // resolver. An access-check method with a parameter of type Route,
    // RouteMatchInterface, AccountInterface or Request will receive those
    // arguments regardless of the parameter name.
    $wildcard_arguments = [$route, $route_match, $account];
    if (isset($request)) {
      $wildcard_arguments[] = $request;
    }

    return new ArgumentsResolver($raw_route_arguments, $upcasted_route_arguments, $wildcard_arguments);
  }

}
