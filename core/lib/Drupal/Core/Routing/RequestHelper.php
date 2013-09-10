<?php

/**
 * @file
 * Contains \Drupal\Core\Routing\RequestHelper.
 */

namespace Drupal\Core\Routing;

use Symfony\Component\HttpFoundation\Request;

/**
 * Provides some helper methods for dealing with the request.
 */
class RequestHelper {

  /**
   * Duplicates a request for another path.
   *
   * This method does basically the same as Request::create() but keeping all
   * the previous variables to speed it up.
   *
   * @param \Symfony\Component\HttpFoundation\Request $original_request
   *   The original request object to clone.
   * @param string $uri
   *   The URI.
   * @param string $method
   *   The HTTP method.
   * @param array $parameters
   *   The query (GET) or request (POST) parameters.
   * @param array $query
   *   The GET parameters.
   * @param array $post
   *   The POST parameters.
   * @param array $attributes
   *   The request attributes (parameters parsed from the PATH_INFO, ...).
   * @param array $cookies
   *   The COOKIE parameters.
   * @param array $files
   *   The FILES parameters.
   * @param array $server
   *   The SERVER parameters.
   *
   * @return \Symfony\Component\HttpFoundation\Request
   *   The cloned request instance.
   *
   * @see \Symfony\Component\HttpFoundation\Request::create()
   * @see \Symfony\Component\HttpFoundation\Request::duplicate()
   */
  public static function duplicate(Request $original_request, $uri, $method = 'GET', $parameters = array(), array $query = NULL, array $post = NULL, array $attributes = NULL, array $cookies = NULL, array $files = NULL, array $server = NULL) {
    $request = $original_request->duplicate($query, $post, $attributes, $cookies, $files, $server);

    $server = array();

    $server['PATH_INFO'] = '';
    $server['REQUEST_METHOD'] = strtoupper($method);

    $components = parse_url($uri);
    if (isset($components['host'])) {
      $server['SERVER_NAME'] = $components['host'];
      $server['HTTP_HOST'] = $components['host'];
    }

    if (isset($components['scheme'])) {
      if ('https' === $components['scheme']) {
        $server['HTTPS'] = 'on';
        $server['SERVER_PORT'] = 443;
      }
      else {
        unset($server['HTTPS']);
        $server['SERVER_PORT'] = 80;
      }
    }

    if (isset($components['port'])) {
      $server['SERVER_PORT'] = $components['port'];
      $server['HTTP_HOST'] = $server['HTTP_HOST'] . ':' . $components['port'];
    }

    if (isset($components['user'])) {
      $server['PHP_AUTH_USER'] = $components['user'];
    }

    if (isset($components['pass'])) {
      $server['PHP_AUTH_PW'] = $components['pass'];
    }

    if (!isset($components['path'])) {
      $components['path'] = '/';
    }

    switch (strtoupper($method)) {
      case 'POST':
      case 'PUT':
      case 'DELETE':
        if (!isset($server['CONTENT_TYPE'])) {
          $server['CONTENT_TYPE'] = 'application/x-www-form-urlencoded';
        }
      case 'PATCH':
        $post = $parameters;
        $query = array();
        break;
      default:
        $post = array();
        $query = $parameters;
        break;
    }

    if (isset($components['query'])) {
      parse_str(html_entity_decode($components['query']), $query_string);
      $query = array_replace($query_string, $query);
    }
    $query_string = http_build_query($query, '', '&');

    // Prepend a ? if there is a query string.
    if ($query_string !== '') {
      $query_string = '?' . $query_string;
    }

    $server['REQUEST_URI'] = $components['path'] . $query_string;
    $server['QUERY_STRING'] = $query_string;
    $request->server->add($server);
    // The 'request' attribute name corresponds to $_REQUEST, but Symfony
    // documents it as holding the POST parameters.
    $request->request->add($post);
    $request->query->add($query);

    return $request;
  }

}
