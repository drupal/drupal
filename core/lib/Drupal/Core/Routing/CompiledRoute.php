<?php

namespace Drupal\Core\Routing;

use Symfony\Component\Routing\Route;

/**
 * Description of CompiledRoute
 */
class CompiledRoute {

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
   * The Route object of which this object is the compiled version.
   *
   * @var Symfony\Component\Routing\Route
   */
  protected $route;

  protected $variables;
  protected $tokens;
  protected $staticPrefix;
  protected $regex;


  /**
    * Constructs a new CompiledRoute object.
    *
    * @param Route  $route
    *   A original Route instance.
    * @param int $fit
    *   The fitness of the route.
    * @param string $fit
    *   The pattern outline for this route.
    */
  public function __construct(Route $route, $fit, $pattern_outline) {
    $this->route = $route;
    $this->fit = $fit;
    $this->patternOutline = $pattern_outline;
  }

  /**
   * Returns the fit of this route
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
    *   A Route instance
    */
  public function getRoute() {
    return $this->route;
  }

  /**
    * Returns the pattern.
    *
    * @return string The pattern
    */
  public function getPattern() {
    return $this->route->getPattern();
  }

  /**
    * Returns the options.
    *
    * @return array The options
    */
  public function getOptions() {
    return $this->route->getOptions();
  }

  /**
    * Returns the defaults.
    *
    * @return array The defaults
    */
  public function getDefaults() {
    return $this->route->getDefaults();
  }

  /**
    * Returns the requirements.
    *
    * @return array The requirements
    */
  public function getRequirements() {
    return $this->route->getRequirements();
  }

}

