<?php

namespace Drupal\Core\ParamConverter;

use Drupal\Core\Routing\RouteObjectInterface;
use Symfony\Component\Routing\RouteCollection;

/**
 * Manages converter services for converting request parameters to full objects.
 *
 * A typical use case for this would be upcasting (converting) a node id to a
 * node entity.
 */
class ParamConverterManager implements ParamConverterManagerInterface {

  /**
   * Array of loaded converter services keyed by their ids.
   *
   * @var array
   */
  protected $converters = [];

  /**
   * {@inheritdoc}
   */
  public function addConverter(ParamConverterInterface $param_converter, $id) {
    $this->converters[$id] = $param_converter;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getConverter($converter) {
    if (isset($this->converters[$converter])) {
      return $this->converters[$converter];
    }
    else {
      throw new \InvalidArgumentException(sprintf('No converter has been registered for %s', $converter));
    }
  }

  /**
   * {@inheritdoc}
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

        foreach (array_keys($this->converters) as $converter) {
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
   * {@inheritdoc}
   */
  public function convert(array $defaults) {
    /** @var \Symfony\Component\Routing\Route $route */
    $route = $defaults[RouteObjectInterface::ROUTE_OBJECT];

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
      // converted.
      $value = $defaults[$name];
      $defaults[$name] = $this->getConverter($definition['converter'])->convert($value, $definition, $name, $defaults);
      if (!isset($defaults[$name])) {
        $message = 'The "%s" parameter was not converted for the path "%s" (route name: "%s")';
        $route_name = $defaults[RouteObjectInterface::ROUTE_NAME];
        throw new ParamNotConvertedException(sprintf($message, $name, $route->getPath(), $route_name), 0, NULL, $route_name, [$name => $value]);
      }
    }

    return $defaults;
  }

}
