<?php

/**
 * @file
 * Contains Drupal\Core\ParamConverter\ParamConverterInterface.
 */

namespace Drupal\Core\ParamConverter;

use Symfony\Component\Routing\Route;

/**
 * Interface for parameter converters.
 *
 * Classes implementing this interface are responsible for converting a path
 * parameter to the object it represents.
 *
 * Here is an example path: /admin/structure/block/manage/{block}
 *
 * In this case, '{block}' would be the path parameter which should be turned
 * into a block object representing the block in question.
 *
 * ParamConverters are defined as services tagged with 'paramconverter', and are
 * managed by the 'paramconverter_manager' service.
 *
 * @see menu
 * @see \Drupal\Core\ParamConverter\ParamConverterManagerInterface
 * @see \Drupal\Core\ParamConverter\EntityConverter
 */
interface ParamConverterInterface {

  /**
   * Converts path variables to their corresponding objects.
   *
   * @param mixed $value
   *   The raw value.
   * @param mixed $definition
   *   The parameter definition provided in the route options.
   * @param string $name
   *   The name of the parameter.
   * @param array $defaults
   *   The route defaults array.
   *
   * @return mixed|null
   *   The converted parameter value.
   */
  public function convert($value, $definition, $name, array $defaults);

  /**
   * Determines if the converter applies to a specific route and variable.
   *
   * @param mixed $definition
   *   The parameter definition provided in the route options.
   * @param string $name
   *   The name of the parameter.
   * @param \Symfony\Component\Routing\Route $route
   *   The route to consider attaching to.
   *
   * @return bool
   *   TRUE if the converter applies to the passed route and parameter, FALSE
   *   otherwise.
   */
  public function applies($definition, $name, Route $route);

}
