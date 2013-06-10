<?php

/**
 * @file
 * Contains Drupal\Core\Routing\PathBasedGeneratorInterface.
 */

namespace Drupal\Core\Routing;

use Symfony\Component\HttpFoundation\Request;

/**
 * Defines an interface for generating a url from a path as opposed to a route.
 */
interface PathBasedGeneratorInterface {

  /**
   * Generates an internal or external URL.
   *
   * @param $path
   *   (optional) The internal path or external URL being linked to, such as
   *   "node/34" or "http://example.com/foo".
   *
   * @param $options
   *   (optional) An associative array of additional options.
   *
   * @return
   *   A string containing a URL to the given path.
   */
  public function generateFromPath($path = NULL, $options = array());

  /**
   * Sets the $request property.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HttpRequest object representing the current request.
   */
  public function setRequest(Request $request);

  /**
   * Sets the baseUrl property.
   *
   * This property is made up of scheme, host and base_path, e.g.
   *   'http://www.example.com/mydrupalinstall/'
   *
   * The base url is usually set by the request but we allow it to be set
   * independent of the request so that code that calls url() outside the context
   * of a request can use the global $base_url variable to set this value.
   *
   * @todo Remove this once the url() function no longer supports being called
   *   when there is no request.
   *
   * @var string $url
   *   The base url to use for url generation.
   */
  public function setBaseUrl($url);

  /**
   * Sets the basePath property.
   *
   * This will be either '/' or '[subdir]/', where [subdir] is the name of the
   * subdirectory that Drupal is running in.
   *
   * The base path is usually set by the request but we allow it to be set
   * independent of the request so that code that calls url() outside the context
   * of a request can use the global $base_url variable to set this value.
   *
   * @todo Remove this once the url() function no longer supports being called
   *   when there is no request.
   *
   * @var string $path
   *   The base path to use for url generation.
   */
  public function setBasePath($path);

  /**
   * Sets the scriptPath property.
   *
   * The script path is usually set by the request and is either 'index.php' or
   * the empty string, depending on whether the request path actually contains
   * the script path or not. We allow it to be set independent of the request so
   * that code that calls url() outside the context of a request can use the global
   * $script_path variable to set this value.
   *
   * @todo Remove this once the url() function no longer supports being called
   *   when there is no request.
   *
   * @var string $path
   *   The script path to use for url generation.
   */
  public function setScriptPath($path);

}
