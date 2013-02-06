<?php

/**
 * @file
 * Contains Drupal\Core\ParamConverter\ParamConverterManager.
 */

namespace Drupal\Core\ParamConverter;

use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Cmf\Component\Routing\Enhancer\RouteEnhancerInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Request;

use Drupal\Core\ParamConverter\ParamConverterInterface;

/**
 * Provides a service which allows to enhance (say alter) the arguments coming
 * from the URL.
 *
 * A typical use case for this would be upcasting a node id to a node entity.
 *
 * This class will not enhance any of the arguments itself, but allow other
 * services to register to do so.
 */
class ParamConverterManager implements RouteEnhancerInterface {

  /**
   * Converters managed by the ParamConverterManager.
   *
   * @var array
   */
  protected $converters;

  /**
   * Adds a converter to the paramconverter service.
   *
   * @see \Drupal\Core\DependencyInjection\Compiler\RegisterParamConvertersPass
   *
   * @param \Drupal\Core\ParamConverter\ParamConverterInterface $converter
   *   The converter to add.
   */
  public function addConverter(ParamConverterInterface $converter) {
    $this->converters[] = $converter;
    return $this;
  }

  /**
   * Implements \Symfony\Cmf\Component\Routing\Enhancer\ŖouteEnhancerIterface.
   *
   * Iterates over all registered converters and allows them to alter the
   * defaults.
   *
   * @param array $defaults
   *   The getRouteDefaults array.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return array
   *   The modified defaults.
   */
  public function enhance(array $defaults, Request $request) {
    // This array will collect the names of all variables which have been
    // altered by a converter.
    // This serves two purposes:
    // 1. It might prevent converters later in the pipeline to process
    //    a variable again.
    // 2. To check if upcasting was successfull after each converter had
    //    a go. See below.
    $converters = array();

    $route = $defaults[RouteObjectInterface::ROUTE_OBJECT];

    foreach ($this->converters as $converter) {
      $converter->process($defaults, $route, $converters);
    }

    // Check if all upcasting yielded a result.
    // If an upcast value is NULL do a 404.
    foreach ($converters as $variable) {
      if ($defaults[$variable] === NULL) {
        throw new NotFoundHttpException();
      }
    }

    return $defaults;
  }
}
