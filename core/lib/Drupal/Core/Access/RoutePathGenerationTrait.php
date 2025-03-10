<?php

namespace Drupal\Core\Access;

use Symfony\Component\Routing\Route;

/**
 * Provides a method for generating route paths.
 */
trait RoutePathGenerationTrait {

  /**
   * Generates a route path by replacing placeholders with their values.
   *
   * Placeholders without corresponding values in the parameters array
   * are removed from the resulting path.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route object containing the path with placeholders.
   * @param array $parameters
   *   An associative array of parameters to replace in the route path,
   *   where the keys are placeholders and the values are the replacement
   *   values.
   *   @code
   *   Example:
   *   [
   *     'parameter1' => 'value1',
   *   ]
   *   @endcode
   *   This will transform a route path such as
   *   '/route/path/{parameter1}{parameter2}' into '/route/path/value1'.
   *
   * @return string
   *   The generated path with all placeholders either replaced by their
   *   corresponding values or removed if no matching parameter exists.
   */
  public function generateRoutePath(Route $route, array $parameters): string {
    $path = ltrim($route->getPath(), '/');

    // Replace path parameters with their corresponding values from the
    // parameters array.
    foreach ($parameters as $param => $value) {
      if (NULL !== $value) {
        $path = str_replace("{{$param}}", $value, $path);
      }
    }

    // Remove placeholders that were not replaced.
    $path = preg_replace('/\/{[^}]+}/', '', $path);

    // Remove trailing slashes (multiple slashes may result from the removal of
    // unreplaced placeholders).
    $path = rtrim($path, '/');

    return $path;
  }

}
