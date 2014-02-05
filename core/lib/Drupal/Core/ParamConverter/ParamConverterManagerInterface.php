<?php

/**
 * @file
 * Contains \Drupal\Core\ParamConverter\ParamConverterManagerInterface.
 */

namespace Drupal\Core\ParamConverter;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouteCollection;

/**
 * Provides an interface for a parameter converter manager.
 */
interface ParamConverterManagerInterface {

  /**
   * Registers a parameter converter with the manager.
   *
   * @param string $converter
   *   The parameter converter service id to register.
   * @param int $priority
   *   (optional) The priority of the converter. Defaults to 0.
   *
   * @return $this
   */
  public function addConverter($converter, $priority = 0);

  /**
   * Sorts the converter service ids and flattens them.
   *
   * @return array
   *   The sorted parameter converter service ids.
   */
    public function getConverterIds();

  /**
   * Lazy-loads converter services.
   *
   * @param string $converter
   *   The service id of converter service to load.
   *
   * @return \Drupal\Core\ParamConverter\ParamConverterInterface
   *   The loaded converter service identified by the given service id.
   *
   * @throws \InvalidArgumentException
   *   If the given service id is not a registered converter.
   */
  public function getConverter($converter);

  /**
   * Saves a list of applicable converters to each route.
   *
   * @param \Symfony\Component\Routing\RouteCollection $routes
   *   A collection of routes to apply converters to.
   */
  public function setRouteParameterConverters(RouteCollection $routes);

  /**
   * Invokes the registered converter for each defined parameter on a route.
   *
   * @param array $defaults
   *   The route defaults array.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @throws \Drupal\Core\ParamConverter\ParamNotConvertedException
   *   If one of the assigned converters returned NULL because the given
   *   variable could not be converted.
   *
   * @return array
   *   The modified defaults.
   */
  public function convert(array $defaults, Request $request);

}
