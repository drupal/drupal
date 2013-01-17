<?php

/**
 * @file
 * Contains Drupal\Core\Routing\UrlGenerator.
 */

namespace Drupal\Core\Routing;

use Symfony\Component\HttpKernel\Log\LoggerInterface;

use Symfony\Cmf\Component\Routing\ProviderBasedGenerator;
use Symfony\Cmf\Component\Routing\RouteProviderInterface;

use Drupal\Core\Path\AliasManagerInterface;

/**
 * A Generator creates URL strings based on a specified route.
 */
class UrlGenerator extends ProviderBasedGenerator {

  /**
   * The alias manager that will be used to alias generated URLs.
   *
   * @var AliasManagerInterface
   */
  protected $aliasManager;

  /**
   *  Constructs a new generator object.
   *
   * @param \Symfony\Cmf\Component\Routing\RouteProviderInterface $provider
   *   The route provider to be searched for routes.
   * @param \Drupal\Core\Path\AliasManagerInterface $alias_manager
   *   The alias manager responsible for path aliasing.
   * @param \Symfony\Component\HttpKernel\Log\LoggerInterface $logger
   *   An optional logger for recording errors.
   */
  public function __construct(RouteProviderInterface $provider, AliasManagerInterface $alias_manager, LoggerInterface $logger = NULL) {
    parent::__construct($provider, $logger);

    $this->aliasManager = $alias_manager;
  }

  /**
   * Implements Symfony\Component\Routing\Generator\UrlGeneratorInterface::generate();
   */
  public function generate($name, $parameters = array(), $absolute = FALSE) {
    $path = parent::generate($name, $parameters, $absolute);

    // This method is expected to return a path with a leading /, whereas
    // the alias manager has no leading /.
    $path = '/' . $this->aliasManager->getPathAlias(trim($path, '/'));

    return $path;
  }

}
