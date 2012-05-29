<?php

namespace Drupal\Core\Routing;

use Symfony\Component\Routing\CompiledRoute as SymfonyCompiledRoute;
use Symfony\Component\Routing\Route;

/**
 * Description of CompiledRoute
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
    * Constructor.
    *
    * @param Route  $route
    *   A original Route instance.
    * @param int $fit
    *   The fitness of the route.
    * @param string $fit
    *   The pattern outline for this route.
    */
  public function __construct(Route $route, $fit, $pattern_outline) {
    // We're ignoring the rest of this stuff; really this should be just using
    // an interface, but the Symfony component doesn't have one. This is a bug
    // in Symfony.
    parent::__construct($route, '', '', array(), array());

    $this->fit = $fit;
    $this->patternOutline = $pattern_outline;
  }

  public function getFit() {
    return $this->fit;
  }

  public function getPatternOutline() {
    return $this->patternOutline;
  }

}

