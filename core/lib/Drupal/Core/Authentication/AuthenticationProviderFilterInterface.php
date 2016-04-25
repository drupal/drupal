<?php

namespace Drupal\Core\Authentication;

use Symfony\Component\HttpFoundation\Request;

/**
 * Restrict authentication methods to a subset of the site.
 *
 * Some authentication methods should not be available throughout a whole site.
 * For instance, there are good reasons to restrict insecure methods like HTTP
 * basic authentication or a URL token authentication method to API-only
 * routes.
 */
interface AuthenticationProviderFilterInterface {

  /**
   * Checks whether the authentication method is allowed on a given route.
   *
   * While authentication itself is run before routing, this method is called
   * after routing, hence RouteMatch is available and can be used to inspect
   * route options.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param bool $authenticated
   *   Whether or not the request is authenticated.
   *
   * @return bool
   *   TRUE if an authentication method is allowed on the request, otherwise
   *   FALSE.
   */
  public function appliesToRoutedRequest(Request $request, $authenticated);

}
