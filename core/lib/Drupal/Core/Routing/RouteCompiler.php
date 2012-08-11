<?php

namespace Drupal\Core\Routing;

use Symfony\Component\Routing\RouteCompilerInterface;
use Symfony\Component\Routing\Route;

/**
 * Description of RouteCompiler
 */
class RouteCompiler implements RouteCompilerInterface {

  /**
   * The maximum number of path elements for a route pattern;
   */
  const MAX_PARTS = 9;

  /**
    * Compiles the current route instance.
    *
    * @param Route $route
    *   A Route instance
    *
    * @return CompiledRoute
    *   A CompiledRoute instance
    */
  public function compile(Route $route) {


    $fit = $this->getFit($route->getPattern());

    $pattern_outline = $this->getPatternOutline($route->getPattern());

    $num_parts = count(explode('/', trim($pattern_outline, '/')));

    return new CompiledRoute($route, $fit, $pattern_outline, $num_parts);

  }

  /**
   * Returns the pattern outline.
   *
   * The pattern outline is the path pattern but normalized so that all
   * placeholders are equal strings.
   *
   * @param string $path
   *   The path pattern to normalize to an outline.
   *
   * @return string
   *   The path pattern outline.
   */
  public function getPatternOutline($path) {
    return preg_replace('#\{\w+\}#', '%', $path);
  }

  /**
   * Determines the fitness of the provided path.
   *
   * @param string $path
   *   The path whose fitness we want.
   *
   * @return int
   *   The fitness of the path, as an integer.
   */
  public function getFit($path) {

    $parts = explode('/', trim($path, '/'), static::MAX_PARTS);
    $number_parts = count($parts);
    // We store the highest index of parts here to save some work in the fit
    // calculation loop.
    $slashes = $number_parts - 1;

    $fit = 0;
    foreach ($parts as $k => $part) {
      if (strpos($part, '{') === FALSE) {
        $fit |=  1 << ($slashes - $k);
      }
    }

    return $fit;
  }
}

