<?php

/**
 * @file
 * Contains \Drupal\Core\Routing\RouteCompiler.
 */

namespace Drupal\Core\Routing;

use Symfony\Component\Routing\RouteCompilerInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCompiler as SymfonyRouteCompiler;

/**
 * Compiler to generate derived information from a Route necessary for matching.
 */
class RouteCompiler extends SymfonyRouteCompiler implements RouteCompilerInterface {

  /**
   * Utility constant to use for regular expressions against the path.
   */
  const REGEX_DELIMITER = '#';

  /**
   * Compiles the current route instance.
   *
   * Because so much of the parent class is private, we need to call the parent
   * class's compile() method and then dissect its return value to build our
   * new compiled object.  If upstream gets refactored so we can subclass more
   * easily then this may not be necessary.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   A Route instance.
   *
   * @return \Drupal\Core\Routing\CompiledRoute
   *   A CompiledRoute instance.
   */
  public static function compile(Route $route) {

    $symfony_compiled = parent::compile($route);

    // The Drupal-specific compiled information.
    $stripped_path = static::getPathWithoutDefaults($route);
    $fit = static::getFit($stripped_path);
    $pattern_outline = static::getPatternOutline($stripped_path);
    $num_parts = count(explode('/', trim($pattern_outline, '/')));

    return new CompiledRoute(
      $fit,
      $pattern_outline,
      $num_parts,
      // These are the Symfony compiled parts.
      $symfony_compiled->getStaticPrefix(),
      $symfony_compiled->getRegex(),
      $symfony_compiled->getTokens(),
      $symfony_compiled->getPathVariables(),
      $symfony_compiled->getHostRegex(),
      $symfony_compiled->getHostTokens(),
      $symfony_compiled->getHostVariables(),
      $symfony_compiled->getVariables()
      );
  }

  /**
   * Returns the pattern outline.
   *
   * The pattern outline is the path pattern but normalized so that all
   * placeholders are equal strings and default values are removed.
   *
   * @param string $path
   *   The path for which we want the normalized outline.
   *
   * @return string
   *   The path pattern outline.
   */
  public static function getPatternOutline($path) {
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
  public static function getFit($path) {
    $parts = explode('/', trim($path, '/'));
    $number_parts = count($parts);
    // We store the highest index of parts here to save some work in the fit
    // calculation loop.
    $slashes = $number_parts - 1;
    // The fit value is a binary number which has 1 at every fixed path
    // position and 0 where there is a wildcard. We keep track of all such
    // patterns that exist so that we can minimize the number of path
    // patterns we need to check in the RouteProvider.
    $fit = 0;
    foreach ($parts as $k => $part) {
      if (strpos($part, '{') === FALSE) {
        $fit |=  1 << ($slashes - $k);
      }
    }

    return $fit;
  }

  /**
   * Returns the path of the route, without placeholders with a default value.
   *
   * When computing the path outline and fit, we want to skip default-value
   * placeholders.  If we didn't, the path would never match.  Note that this
   * only works for placeholders at the end of the path. Infix placeholders
   * with default values don't make sense anyway, so that should not be a
   * problem.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to have the placeholders removed from.
   *
   * @return string
   *   The path string, stripped of placeholders that have default values.
   */
  public static function getPathWithoutDefaults(Route $route) {
    $path = $route->getPath();
    $defaults = $route->getDefaults();

    // Remove placeholders with default values from the outline, so that they
    // will still match.
    $remove = array_map(function($a) {
      return '/{' . $a . '}';
    }, array_keys($defaults));
    $path = str_replace($remove, '', $path);

    return $path;
  }

}
