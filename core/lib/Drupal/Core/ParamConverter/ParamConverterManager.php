<?php

/**
 * @file
 * Contains Drupal\Core\ParamConverter\ParamConverterManager.
 */

namespace Drupal\Core\ParamConverter;

use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Cmf\Component\Routing\Enhancer\RouteEnhancerInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\HttpFoundation\Request;

/**
 * Manages converter services for converting request parameters to full objects.
 *
 * A typical use case for this would be upcasting (converting) a node id to a
 * node entity.
 */
class ParamConverterManager extends ContainerAware implements RouteEnhancerInterface {

  /**
   * An array of registered converter service ids.
   *
   * @var array
   */
  protected $converterIds = array();

  /**
   * Array of registered converter service ids sorted by their priority.
   *
   * @var array
   */
  protected $sortedConverterIds;

  /**
   * Array of loaded converter services keyed by their ids.
   *
   * @var array
   */
  protected $converters = array();

  /**
   * Registers a parameter converter with the manager.
   *
   * @param string $converter
   *   The parameter converter service id to register.
   * @param int $priority
   *   (optional) The priority of the converter. Defaults to 0.
   *
   * @return \Drupal\Core\ParamConverter\ParamConverterManager
   *   The called object for chaining.
   */
  public function addConverter($converter, $priority = 0) {
    if (empty($this->converterIds[$priority])) {
      $this->converterIds[$priority] = array();
    }
    $this->converterIds[$priority][] = $converter;
    unset($this->sortedConverterIds);
    return $this;
  }

  /**
   * Sorts the converter service ids and flattens them.
   *
   * @return array
   *   The sorted parameter converter service ids.
   */
  public function getConverterIds() {
    if (!isset($this->sortedConverterIds)) {
      krsort($this->converterIds);
      $this->sortedConverterIds = array();
      foreach ($this->converterIds as $resolvers) {
        $this->sortedConverterIds = array_merge($this->sortedConverterIds, $resolvers);
      }
    }
    return $this->sortedConverterIds;
  }

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
  public function getConverter($converter) {
    if (isset($this->converters[$converter])) {
      return $this->converters[$converter];
    }
    if (!in_array($converter, $this->getConverterIds())) {
      throw new \InvalidArgumentException(sprintf('No converter has been registered for %s', $converter));
    }
    return $this->converters[$converter] = $this->container->get($converter);
  }

  /**
   * Saves a list of applicable converters to each route.
   *
   * @param \Symfony\Component\Routing\RouteCollection $routes
   *   A collection of routes to apply converters to.
   */
  public function setRouteParameterConverters(RouteCollection $routes) {
    foreach ($routes->all() as $route) {
      if (!$parameters = $route->getOption('parameters')) {
        // Continue with the next route if no parameters have been defined.
        continue;
      }

      // Loop over all defined parameters and look up the right converter.
      foreach ($parameters as $name => &$definition) {
        if (isset($definition['converter'])) {
          // Skip parameters that already have a manually set converter.
          continue;
        }

        foreach ($this->getConverterIds() as $converter) {
          if ($this->getConverter($converter)->applies($definition, $name, $route)) {
            $definition['converter'] = $converter;
            break;
          }
        }
      }

      // Override the parameters array.
      $route->setOption('parameters', $parameters);
    }
  }

  /**
   * Invokes the registered converter for each defined parameter on a route.
   *
   * @param array $defaults
   *   The route defaults array.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   If one of the assigned converters returned NULL because the given
   *   variable could not be converted.
   *
   * @return array
   *   The modified defaults.
   */
  public function enhance(array $defaults, Request $request) {
    // Store a backup of the raw $defaults values corresponding to
    // variables in the route path pattern.
    $route = $defaults[RouteObjectInterface::ROUTE_OBJECT];
    $variables = array_flip($route->compile()->getVariables());
    $defaults['_raw_variables'] = new ParameterBag(array_intersect_key($defaults, $variables));

    // Skip this enhancer if there are no parameter definitions.
    if (!$parameters = $route->getOption('parameters')) {
      return $defaults;
    }

    // Invoke the registered converter for each parameter.
    foreach ($parameters as $name => $definition) {
      if (!isset($defaults[$name])) {
        // Do not try to convert anything that is already set to NULL.
        continue;
      }

      if (!isset($definition['converter'])) {
        // Continue if no converter has been specified.
        continue;
      }

      // If a converter returns NULL it means that the parameter could not be
      // converted in which case we throw a 404.
      $defaults[$name] = $this->getConverter($definition['converter'])->convert($defaults[$name], $definition, $name, $defaults, $request);
      if (!isset($defaults[$name])) {
        throw new NotFoundHttpException();
      }
    }

    return $defaults;
  }

}

