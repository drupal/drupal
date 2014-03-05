<?php

/**
 * @file
 * Contains \Drupal\Core\Url.
 */

namespace Drupal\Core;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\DependencyInjection\DependencySerialization;
use Drupal\Core\Routing\MatchingRouteNotFoundException;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

/**
 * Defines an object that holds information about a URL.
 */
class Url extends DependencySerialization {

  /**
   * The URL generator.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * The route name.
   *
   * @var string
   */
  protected $routeName;

  /**
   * The route parameters.
   *
   * @var array
   */
  protected $routeParameters = array();

  /**
   * The URL options.
   *
   * @var array
   */
  protected $options = array();

  /**
   * Indicates whether this URL is external.
   *
   * @var bool
   */
  protected $external = FALSE;

  /**
   * The external path.
   *
   * Only used if self::$external is TRUE.
   *
   * @var string
   */
  protected $path;

  /**
   * Constructs a new Url object.
   *
   * @param string $route_name
   *   The name of the route
   * @param array $route_parameters
   *   (optional) An associative array of parameter names and values.
   * @param array $options
   *   (optional) An associative array of additional options, with the following
   *   elements:
   *   - 'query': An array of query key/value-pairs (without any URL-encoding)
   *     to append to the URL. Merged with the parameters array.
   *   - 'fragment': A fragment identifier (named anchor) to append to the URL.
   *     Do not include the leading '#' character.
   *   - 'absolute': Defaults to FALSE. Whether to force the output to be an
   *     absolute link (beginning with http:). Useful for links that will be
   *     displayed outside the site, such as in an RSS feed.
   *   - 'language': An optional language object used to look up the alias
   *     for the URL. If $options['language'] is omitted, it defaults to the
   *     current language for the language type Language::TYPE_URL.
   *   - 'https': Whether this URL should point to a secure location. If not
   *     defined, the current scheme is used, so the user stays on HTTP or HTTPS
   *     respectively. if mixed mode sessions are permitted, TRUE enforces HTTPS
   *     and FALSE enforces HTTP.
   */
  public function __construct($route_name, $route_parameters = array(), $options = array()) {
    $this->routeName = $route_name;
    $this->routeParameters = $route_parameters;
    $this->options = $options;
  }

  /**
   * Returns the Url object matching a path.
   *
   * @param string $path
   *   A path (e.g. 'node/1', 'http://drupal.org').
   *
   * @return static
   *   An Url object.
   *
   * @throws \Drupal\Core\Routing\MatchingRouteNotFoundException
   *   Thrown when the path cannot be matched.
   */
  public static function createFromPath($path) {
    if (UrlHelper::isExternal($path)) {
      $url = new static($path);
      $url->setExternal();
      return $url;
    }

    // Special case the front page route.
    if ($path == '<front>') {
      $route_name = $path;
      $route_parameters = array();
    }
    else {
      // Look up the route name and parameters used for the given path.
      try {
        $result = \Drupal::service('router')->match('/' . $path);
      }
      catch (ResourceNotFoundException $e) {
        throw new MatchingRouteNotFoundException(sprintf('No matching route could be found for the path "%s"', $path), 0, $e);
      }
      $route_name = $result[RouteObjectInterface::ROUTE_NAME];
      $route_parameters = $result['_raw_variables']->all();
    }
    return new static($route_name, $route_parameters);
  }

  /**
   * Returns the Url object matching a request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   A request object.
   *
   * @return static
   *   A Url object.
   *
   * @throws \Drupal\Core\Routing\MatchingRouteNotFoundException
   *   Thrown when the request cannot be matched.
   */
  public static function createFromRequest(Request $request) {
    try {
      $result = \Drupal::service('router')->matchRequest($request);
    }
    catch (ResourceNotFoundException $e) {
      throw new MatchingRouteNotFoundException(sprintf('No matching route could be found for the request: %s', $request), 0, $e);
    }
    $route_name = $result[RouteObjectInterface::ROUTE_NAME];
    $route_parameters = $result['_raw_variables']->all();
    return new static($route_name, $route_parameters);
  }

  /**
   * Sets this Url to be external.
   *
   * @return $this
   */
  protected function setExternal() {
    $this->external = TRUE;

    // What was passed in as the route name is actually the path.
    $this->path = $this->routeName;

    // Set empty route name and parameters.
    $this->routeName = '';
    $this->routeParameters = array();

    return $this;
  }

  /**
   * Indicates if this Url is external.
   *
   * @return bool
   */
  public function isExternal() {
    return $this->external;
  }

  /**
   * Returns the route name.
   *
   * @return string
   */
  public function getRouteName() {
    return $this->routeName;
  }

  /**
   * Returns the route parameters.
   *
   * @return array
   */
  public function getRouteParameters() {
    return $this->routeParameters;
  }

  /**
   * Sets the route parameters.
   *
   * @param array $parameters
   *   The array of parameters.
   *
   * @return $this
   */
  public function setRouteParameters($parameters) {
    if ($this->isExternal()) {
      throw new \Exception('External URLs do not have route parameters.');
    }
    $this->routeParameters = $parameters;
    return $this;
  }

  /**
   * Sets a specific route parameter.
   *
   * @param string $key
   *   The key of the route parameter.
   * @param mixed $value
   *   The route parameter.
   *
   * @return $this
   */
  public function setRouteParameter($key, $value) {
    if ($this->isExternal()) {
      throw new \Exception('External URLs do not have route parameters.');
    }
    $this->routeParameters[$key] = $value;
    return $this;
  }

  /**
   * Returns the URL options.
   *
   * @return array
   */
  public function getOptions() {
    return $this->options;
  }

  /**
   * Gets a specific option.
   *
   * @param string $name
   *   The name of the option.
   *
   * @return mixed
   *   The value for a specific option, or NULL if it does not exist.
   */
  public function getOption($name) {
    if (!isset($this->options[$name])) {
      return NULL;
    }

    return $this->options[$name];
  }

  /**
   * Sets the URL options.
   *
   * @param array $options
   *   The array of options.
   *
   * @return $this
   */
  public function setOptions($options) {
    $this->options = $options;
    return $this;
  }

  /**
   * Sets a specific option.
   *
   * @param string $name
   *   The name of the option.
   * @param mixed $value
   *   The option value.
   *
   * @return $this
   */
  public function setOption($name, $value) {
    $this->options[$name] = $value;
    return $this;
  }

  /**
   * Sets the absolute value for this Url.
   *
   * @param bool $absolute
   *   (optional) Whether to make this Url absolute or not. Defaults to TRUE.
   *
   * @return $this
   */
  public function setAbsolute($absolute = TRUE) {
    $this->options['absolute'] = $absolute;
    return $this;
  }

  /**
   * Generates the path for this Url object.
   */
  public function toString() {
    if ($this->isExternal()) {
      return $this->urlGenerator()->generateFromPath($this->path, $this->getOptions());
    }

    return $this->urlGenerator()->generateFromRoute($this->getRouteName(), $this->getRouteParameters(), $this->getOptions());
  }

  /**
   * Returns all the information about the route.
   *
   * @return array
   *   An associative array containing all the properties of the route.
   */
  public function toArray() {
    return array(
      'route_name' => $this->getRouteName(),
      'route_parameters' => $this->getRouteParameters(),
      'options' => $this->getOptions(),
    );
  }

  /**
   * Returns the route information for a render array.
   *
   * @return array
   *   An associative array suitable for a render array.
   */
  public function toRenderArray() {
    return array(
      '#route_name' => $this->getRouteName(),
      '#route_parameters' => $this->getRouteParameters(),
      '#options' => $this->getOptions(),
    );
  }

  /**
   * Returns the internal path for this route.
   *
   * This path will not include any prefixes, fragments, or query strings.
   *
   * @return string
   *   The internal path for this route.
   */
  public function getInternalPath() {
    if ($this->isExternal()) {
      throw new \Exception('External URLs do not have internal representations.');
    }
    return $this->urlGenerator()->getPathFromRoute($this->getRouteName(), $this->getRouteParameters());
  }

  /**
   * Gets the URL generator.
   *
   * @return \Drupal\Core\Routing\UrlGeneratorInterface
   *   The URL generator.
   */
  protected function urlGenerator() {
    if (!$this->urlGenerator) {
      $this->urlGenerator = \Drupal::urlGenerator();
    }
    return $this->urlGenerator;
  }

  /**
   * Sets the URL generator.
   *
   * @param \Drupal\Core\Routing\UrlGeneratorInterface
   *   The URL generator.
   *
   * @return $this
   */
  public function setUrlGenerator(UrlGeneratorInterface $url_generator) {
    $this->urlGenerator = $url_generator;
    return $this;
  }

}
