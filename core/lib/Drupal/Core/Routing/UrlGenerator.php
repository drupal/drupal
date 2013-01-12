<?php

/**
 * @file
 * Contains Drupal\Core\Routing\UrlGenerator.
 */

namespace Drupal\Core\Routing;

use Symfony\Cmf\Component\Routing\ProviderBasedGenerator;
use Symfony\Cmf\Component\Routing\RouteProviderInterface;

use Drupal\Core\Path\AliasManagerInterface;

/**
 * Description of UrlGenerator
 */
class UrlGenerator extends ProviderBasedGenerator {

  /**
   * The alias manager that will be used to alias generated URLs.
   *
   * @var AliasManagerInterface
   */
  protected $aliasManager;

  public function __construct(RouteProviderInterface $provider, AliasManagerInterface $alias_manager, LoggerInterface $logger = NULL) {
    parent::__construct($provider, $logger);

    $this->aliasManager = $alias_manager;
  }

  public function generate($name, $parameters = array(), $absolute = FALSE) {
    $path = parent::generate($name, $parameters, $absolute);

    // This method is expected to return a path with a leading /, whereas
    // the alias manager has no leading /.
    $path = '/' . $this->aliasManager->getPathAlias(trim($path, '/'));

    return $path;
  }

}
