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
   *  @param int $num_parts
   *   The number of parts in the path.
   *  @param string $regex
   *   The regular expression to match placeholders out of this path.
   */
  public function __construct(Route $route, $fit, $pattern_outline, $num_parts, $regex) {
    $this->route = $route;
    $this->fit = $fit;
    $this->patternOutline = $pattern_outline;
    $this->numParts = $num_parts;
    $this->regex = $regex;
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
   * Returns the placeholder regex.
   *
   * @return string
   *   The regex to locate placeholders in this pattern.
   */
  public function getRegex() {
    return $this->regex;
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

