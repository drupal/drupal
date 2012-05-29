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
    * Constructor.
    *
    * @param Route  $route
    *   A original Route instance.
    * @param int $fit
    *   The fitness of the route.
    * @param string $regex        The regular expression to use to match this route
    * @param array  $tokens       An array of tokens to use to generate URL for this route
    * @param array  $variables    An array of variables
    */
  public function __construct(Route $route, $fit) {
    // We're ignoring the rest of this stuff; really this should be just using
    // an interface, but the Symfony component doesn't have one. This is a bug
    // in Symfony.
    parent::__construct($route, '', '', array(), array());

    $this->fit = $fit;
  }

  public function getFit() {
    return $this->fit;
  }

}

