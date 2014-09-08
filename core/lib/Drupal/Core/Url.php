<?php

/**
 * @file
 * Contains \Drupal\Core\Url.
 */

namespace Drupal\Core;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines an object that holds information about a URL.
 */
class Url {
  use DependencySerializationTrait;

  /**
   * The URL generator.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * The access manager
   *
   * @var \Drupal\Core\Access\AccessManagerInterface
   */
  protected $accessManager;

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
   *     current language for the language type LanguageInterface::TYPE_URL.
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
   * Returns the Url object matching a path. READ THE FOLLOWING SECURITY NOTE.
   *
   * SECURITY NOTE: The path is not checked to be valid and accessible by the
   * current user to allow storing and reusing Url objects by different users.
   * The 'path.validator' service getUrlIfValid() method should be used instead
   * of this one if validation and access check is desired. Otherwise,
   * 'access_manager' service checkNamedRoute() method should be used on the
   * router name and parameters stored in the Url object returned by this
   * method.
   *
   * @param string $path
   *   A path (e.g. 'node/1', 'http://drupal.org').
   *
   * @return static
   *   An Url object. Warning: the object is created even if the current user
   *   can not access the path.
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
      return new static($path);
    }
    else {
      return static::createFromRequest(Request::create("/$path"));
    }
  }

  /**
   * Returns the Url object matching a request. READ THE SECURITY NOTE ON createFromPath().
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   A request object.
   *
   * @return static
   *   A Url object. Warning: the object is created even if the current user
   *   would get an access denied running the same request via the normal page
   *   flow.
   *
   * @throws \Drupal\Core\Routing\MatchingRouteNotFoundException
   *   Thrown when the request cannot be matched.
   */
  public static function createFromRequest(Request $request) {
    // We use the router without access checks because URL objects might be
    // created and stored for different users.
    $result = \Drupal::service('router.no_access_checks')->matchRequest($request);
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
    $this->routeName = NULL;
    $this->routeParameters = array();
    // Flag the path as external so the UrlGenerator does not need to check.
    $this->options['external'] = TRUE;

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
   *
   * @throws \UnexpectedValueException.
   *   If this is an external URL with no corresponding route.
   */
  public function getRouteName() {
    if ($this->isExternal()) {
      throw new \UnexpectedValueException('External URLs do not have an internal route name.');
    }

    return $this->routeName;
  }

  /**
   * Returns the route parameters.
   *
   * @return array
   *
   * @throws \UnexpectedValueException.
   *   If this is an external URL with no corresponding route.
   */
  public function getRouteParameters() {
    if ($this->isExternal()) {
      throw new \UnexpectedValueException('External URLs do not have internal route parameters.');
    }

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
   * Returns the external path of the URL.
   *
   * Only to be used if self::$external is TRUE.
   *
   * @return string
   *   The external path.
   *
   * @throws \UnexpectedValueException
   *   Thrown when the path was requested for an internal URL.
   */
  public function getPath() {
    if (!$this->isExternal()) {
      throw new \UnexpectedValueException('Internal URLs do not have external paths.');
    }

    return $this->path;
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
      return $this->urlGenerator()->generateFromPath($this->getPath(), $this->getOptions());
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
    if ($this->isExternal()) {
      return array(
        'path' => $this->getPath(),
        'options' => $this->getOptions(),
      );
    }
    else {
      return array(
        'route_name' => $this->getRouteName(),
        'route_parameters' => $this->getRouteParameters(),
        'options' => $this->getOptions(),
      );
    }
  }

  /**
   * Returns the route information for a render array.
   *
   * @return array
   *   An associative array suitable for a render array.
   */
  public function toRenderArray() {
    if ($this->isExternal()) {
      return array(
        '#href' => $this->getPath(),
        '#options' => $this->getOptions(),
      );
    }
    else {
      return array(
        '#route_name' => $this->getRouteName(),
        '#route_parameters' => $this->getRouteParameters(),
        '#options' => $this->getOptions(),
        '#access_callback' => array(get_class(), 'renderAccess'),
      );
    }
  }

  /**
   * Returns the internal path (system path) for this route.
   *
   * This path will not include any prefixes, fragments, or query strings.
   *
   * @return string
   *   The internal path for this route.
   *
   * @throws \UnexpectedValueException.
   *   If this is an external URL with no corresponding system path.
   *
   * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
   *   System paths should not be used - use route names and parameters.
   */
  public function getInternalPath() {
    if ($this->isExternal()) {
      throw new \UnexpectedValueException('External URLs do not have internal representations.');
    }
    return $this->urlGenerator()->getPathFromRoute($this->getRouteName(), $this->getRouteParameters());
  }

  /**
   * Checks this Url object against applicable access check services.
   *
   * Determines whether the route is accessible or not.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   (optional) Run access checks for this account. Defaults to the current
   *   user.
   *
   * @return bool
   *   Returns TRUE if the user has access to the url, otherwise FALSE.
   */
  public function access(AccountInterface $account = NULL) {
    return $this->accessManager()->checkNamedRoute($this->getRouteName(), $this->getRouteParameters(), $account);
  }

  /**
   * Checks a Url render element against applicable access check services.
   *
   * @param array $element
   *   A render element as returned from \Drupal\Core\Url::toRenderArray().
   *
   * @return bool
   *   Returns TRUE if the current user has access to the url, otherwise FALSE.
   */
  public static function renderAccess(array $element) {
    return (new static($element['#route_name'], $element['#route_parameters'], $element['#options']))->access();
  }

  /**
   * @return \Drupal\Core\Access\AccessManagerInterface
   */
  protected function accessManager() {
    if (!isset($this->accessManager)) {
      $this->accessManager = \Drupal::service('access_manager');
    }
    return $this->accessManager;
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
