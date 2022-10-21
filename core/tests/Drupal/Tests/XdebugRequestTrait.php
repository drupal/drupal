<?php

namespace Drupal\Tests;

use Symfony\Component\HttpFoundation\Request;

trait XdebugRequestTrait {

  /**
   * Adds xdebug cookies, from request setup.
   *
   * In order to debug web tests you need to either set a cookie, have the
   * Xdebug session in the URL or set an environment variable in case of CLI
   * requests. If the developer listens to connection on the parent site, by
   * default the cookie is not forwarded to the client side, so you cannot
   * debug the code running on the child site. In order to make debuggers work
   * this bit of information is forwarded. Make sure that the debugger listens
   * to at least three external connections.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return array
   *   The extracted cookies.
   */
  protected function extractCookiesFromRequest(Request $request) {
    $cookie_params = $request->cookies;
    $cookies = [];
    if ($cookie_params->has('XDEBUG_SESSION')) {
      $cookies['XDEBUG_SESSION'][] = $cookie_params->get('XDEBUG_SESSION');
    }
    // For CLI requests, the information is stored in $_SERVER.
    $server = $request->server;
    if ($server->has('XDEBUG_SESSION')) {
      $cookies['XDEBUG_SESSION'][] = $server->get('XDEBUG_SESSION');
    }
    elseif ($server->has('XDEBUG_CONFIG')) {
      // $_SERVER['XDEBUG_CONFIG'] has the form "key1=value1 key2=value2 ...".
      $pairs = explode(' ', $server->get('XDEBUG_CONFIG'));
      foreach ($pairs as $pair) {
        [$key, $value] = explode('=', $pair);
        // Account for key-value pairs being separated by multiple spaces.
        if (trim($key, ' ') == 'idekey') {
          $cookies['XDEBUG_SESSION'][] = trim($value, ' ');
        }
      }
    }
    return $cookies;
  }

}
