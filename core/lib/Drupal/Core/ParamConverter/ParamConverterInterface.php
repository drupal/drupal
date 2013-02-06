<?php

/**
 * @file
 * Contains Drupal\Core\ParamConverter\ParamConverterInterface.
 */

namespace Drupal\Core\ParamConverter;

use Symfony\Component\Routing\Route;

/**
 * Interface for parameter converters.
 */
interface ParamConverterInterface {

  /**
   * Allows to convert variables to their corresponding objects.
   *
   * @param array &$variables
   *   Array of values to convert to their corresponding objects, if applicable.
   * @param \Symfony\Component\Routing\Route $route
   *   The route object.
   * @param array &$converted
   *   Array collecting the names of all variables which have been
   *   altered by a converter.
   */
  public function process(array &$variables, Route $route, array &$converted);
}
