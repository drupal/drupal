<?php

namespace Drupal\Core\Routing;

use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Default object representing the results of routing.
 */
class RouteMatch implements RouteMatchInterface {

  /**
   * The route name.
   *
   * @var string
   */
  protected $routeName;

  /**
   * The route.
   *
   * @var \Symfony\Component\Routing\Route
   */
  protected $route;

  /**
   * A key|value store of parameters.
   *
   * @var \Symfony\Component\HttpFoundation\ParameterBag
   */
  protected $parameters;

  /**
   * A key|value store of raw parameters.
   *
   * @var \Symfony\Component\HttpFoundation\InputBag
   */
  protected $rawParameters;

  /**
   * Constructs a RouteMatch object.
   *
   * @param string $route_name
   *   The name of the route.
   * @param \Symfony\Component\Routing\Route $route
   *   The route.
   * @param array $parameters
   *   The parameters array.
   * @param array $raw_parameters
   *   The raw $parameters array.
   */
  public function __construct($route_name, Route $route, array $parameters = [], array $raw_parameters = []) {
    $this->routeName = $route_name;
    $this->route = $route;

    // Pre-filter parameters.
    $route_params = $this->getParameterNames();
    $parameters = array_intersect_key($parameters, $route_params);
    $raw_parameters = array_intersect_key($raw_parameters, $route_params);
    $this->parameters = new ParameterBag($parameters);
    $this->rawParameters = new InputBag($raw_parameters);
  }

  /**
   * Creates a RouteMatch from a request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   A request object.
   *
   * @return \Drupal\Core\Routing\RouteMatchInterface
   *   A new RouteMatch object if there's a matched route for the request.
   *   A new NullRouteMatch object otherwise (e.g., on a 404 page or when
   *   invoked prior to routing).
   */
  public static function createFromRequest(Request $request) {
    if ($request->attributes->get(RouteObjectInterface::ROUTE_OBJECT)) {
      $raw_variables = [];
      if ($raw = $request->attributes->get('_raw_variables')) {
        $raw_variables = $raw->all();
      }
      return new static(
        $request->attributes->get(RouteObjectInterface::ROUTE_NAME),
        $request->attributes->get(RouteObjectInterface::ROUTE_OBJECT),
        $request->attributes->all(),
        $raw_variables);
    }
    else {
      return new NullRouteMatch();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteName() {
    return $this->routeName;
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteObject() {
    return $this->route;
  }

  /**
   * {@inheritdoc}
   */
  public function getParameter($parameter_name) {
    return $this->parameters->get($parameter_name);
  }

  /**
   * {@inheritdoc}
   */
  public function getParameters() {
    return $this->parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function getRawParameter($parameter_name) {
    return $this->rawParameters->get($parameter_name);
  }

  /**
   * {@inheritdoc}
   */
  public function getRawParameters() {
    return $this->rawParameters;
  }

  /**
   * Returns the names of all parameters for the currently matched route.
   *
   * @return array
   *   Route parameter names as both the keys and values.
   */
  protected function getParameterNames() {
    $names = [];
    if ($route = $this->getRouteObject()) {
      // Variables defined in path and host patterns are route parameters.
      $variables = $route->compile()->getVariables();
      $names = array_combine($variables, $variables);
      // Route defaults that do not start with a leading "_" are also
      // parameters, even if they are not included in path or host patterns.
      foreach ($route->getDefaults() as $name => $value) {
        if (!isset($names[$name]) && substr($name, 0, 1) !== '_') {
          $names[$name] = $name;
        }
      }
    }
    return $names;
  }

}
