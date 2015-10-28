<?php

/**
 * @file
 * Contains \Drupal\Core\ParamConverter\ParamConverterManagerInterface.
 */

namespace Drupal\Core\ParamConverter;

use Symfony\Component\Routing\RouteCollection;

/**
 * Provides an interface for a parameter converter manager.
 */
interface ParamConverterManagerInterface {

  /**
   * Registers a parameter converter with the manager.
   *
   * @param \Drupal\Core\ParamConverter\ParamConverterInterface $param_converter
   *   The added param converter instance.
   * @param string $id
   *   The parameter converter service id to register.
   *
   * @return $this
   */
  public function addConverter(ParamConverterInterface $param_converter, $id);

  /**
   * Lazy-loads converter services.
   *
   * @param string $id
   *   The service id of converter service to load.
   *
   * @return \Drupal\Core\ParamConverter\ParamConverterInterface
   *   The loaded converter service identified by the given service id.
   *
   * @throws \InvalidArgumentException
   *   If the given service id is not a registered converter.
   */
  public function getConverter($id);

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
   *
   * @return array
   *   The modified defaults.
   *
   * @throws \Drupal\Core\ParamConverter\ParamNotConvertedException
   *   If one of the assigned converters returned NULL because the given
   *   variable could not be converted.
   */
  public function convert(array $defaults);

}
