<?php

/**
 * @file
 * Contains \Drupal\toolbar\ToolbarBundle.
 */

namespace Drupal\toolbar;

use Drupal\Core\Cache\CacheFactory;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Registers toolbar module's services to the container.
 */
class ToolbarBundle extends Bundle {

  /**
   * Overrides \Symfony\Component\HttpKernel\Bundle\Bundle::build().
   */
  public function build(ContainerBuilder $container) {
    CacheFactory::registerBin($container, 'toolbar');
  }

}
