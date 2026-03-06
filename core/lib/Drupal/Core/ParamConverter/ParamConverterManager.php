<?php

namespace Drupal\Core\ParamConverter;

use Drupal\Core\Routing\RouteObjectInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Contracts\Service\ServiceCollectionInterface;

/**
 * Manages converter services for converting request parameters to full objects.
 *
 * A typical use case for this would be upcasting (converting) a node id to a
 * node entity.
 */
class ParamConverterManager implements ParamConverterManagerInterface {

  /**
   * Constructs a new ParamConverterManager.
   *
   * @param \Symfony\Contracts\Service\ServiceCollectionInterface $converters
   *   The param converter services, keyed by service ID.
   */
  public function __construct(
    #[AutowireLocator('paramconverter')]
    protected ServiceCollectionInterface $converters,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getConverter($converter) {
    if ($this->converters->has($converter)) {
      return $this->converters->get($converter);
    }

    throw new \InvalidArgumentException(sprintf('No converter has been registered for %s', $converter));
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

        foreach ($this->converters->getIterator() as $converter => $service) {
          if ($service->applies($definition, $name, $route)) {
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
