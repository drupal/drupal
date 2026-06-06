<?php

namespace Drupal\Core\ParamConverter;

/**
 * Interface for param converters providing a route requirement for parameters.
 */
interface ParamConverterRouteRequirementInterface {

  /**
   * Returns a regex requirement for a route parameter.
   *
   * @param mixed $definition
   *   The parameter definition provided in the route options.
   * @param string $name
   *   The name of the parameter.
   *
   * @return string|null
   *   A regex pattern (without delimiters or anchors) suitable for
   *   Route::setRequirement(), or NULL if no requirement can be determined.
   */
  public function getRouteRequirement($definition, $name): ?string;

}
