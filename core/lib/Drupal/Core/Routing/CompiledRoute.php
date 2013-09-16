<?php

/**
 * @file
 * Definition of Drupal\Core\Routing\CompiledRoute.
 */

namespace Drupal\Core\Routing;

use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\CompiledRoute as SymfonyCompiledRoute;

/**
 * A compiled route contains derived information from a route object.
 */
class CompiledRoute extends SymfonyCompiledRoute {

  /**
   * The fitness of this route.
   *
   * @var int
   */
  protected $fit;

  /**
   * The pattern outline of this route.
   *
   * @var string
   */
  protected $patternOutline;

  /**
   * The number of parts in the path of this route.
   *
   * @var int
   */
  protected $numParts;

  /**
   * The Route object of which this object is the compiled version.
   *
   * @var Symfony\Component\Routing\Route
   */
  protected $route;

  /**
   * Constructs a new compiled route object.
   *
   * This is a ridiculously long set of constructor parameters, but as this
   * object is little more than a collection of values it's not a serious
   * problem. The parent Symfony class does the same, as well, making it
   * difficult to override differently.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   A original Route instance.
   * @param int $fit
   *   The fitness of the route.
   * @param string $fit
   *   The pattern outline for this route.
   * @param int $num_parts
   *   The number of parts in the path.
   * @param string $staticPrefix
   *   The static prefix of the compiled route
   * @param string $regex
   *   The regular expression to use to match this route
   * @param array $tokens
   *   An array of tokens to use to generate URL for this route
   * @param array $pathVariables
   *   An array of path variables
   * @param string|null $hostRegex
   *   Host regex
   * @param array $hostTokens
   *   Host tokens
   * @param array $hostVariables
   *   An array of host variables
   * @param array $variables
   *   An array of variables (variables defined in the path and in the host patterns)
   */
  public function __construct(Route $route, $fit, $pattern_outline, $num_parts, $staticPrefix, $regex, array $tokens, array $pathVariables, $hostRegex = null, array $hostTokens = array(), array $hostVariables = array(), array $variables = array()) {
    parent::__construct($staticPrefix, $regex, $tokens, $pathVariables, $hostRegex, $hostTokens, $hostVariables, $variables);

    $this->route = $route;
    $this->fit = $fit;
    $this->patternOutline = $pattern_outline;
    $this->numParts = $num_parts;
  }

  /**
   * Returns the fit of this route.
   *
   * See RouteCompiler for a definition of how the fit is calculated.
   *
   * @return int
   *   The fit of the route.
   */
  public function getFit() {
    return $this->fit;
  }

  /**
   * Returns the number of parts in this route's path.
   *
   * The string "foo/bar/baz" has 3 parts, regardless of how many of them are
   * placeholders.
   *
   * @return int
   *   The number of parts in the path.
   */
  public function getNumParts() {
    return $this->numParts;
  }

  /**
   * Returns the pattern outline of this route.
   *
   * The pattern outline of a route is the path pattern of the route, but
   * normalized such that all placeholders are replaced with %.
   *
   * @return string
   *   The normalized path pattern.
   */
  public function getPatternOutline() {
    return $this->patternOutline;
  }

  /**
   * Returns the Route instance.
   *
   * @return Route
   *   A Route instance.
   */
  public function getRoute() {
    return $this->route;
  }

  /**
   * Returns the path.
   *
   * @return string
   *   The path.
   */
  public function getPath() {
    return $this->route->getPath();
  }

  /**
   * Returns the options.
   *
   * @return array
   *   The options.
   */
  public function getOptions() {
    return $this->route->getOptions();
  }

  /**
   * Returns the defaults.
   *
   * @return array
   *   The defaults.
   */
  public function getDefaults() {
    return $this->route->getDefaults();
  }

  /**
   * Returns the requirements.
   *
   * @return array
   *   The requirements.
   */
  public function getRequirements() {
    return $this->route->getRequirements();
  }

}
