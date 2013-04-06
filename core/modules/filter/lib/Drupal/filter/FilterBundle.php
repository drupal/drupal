<?php

/**
 * @file
 * Contains \Drupal\filter\FilterBundle.
 */

namespace Drupal\filter;

use Drupal\Core\Cache\CacheFactory;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Registers filter module's services to the container.
 */
class FilterBundle extends Bundle {

  /**
   * Overrides Symfony\Component\HttpKernel\Bundle\Bundle::build().
   */
  public function build(ContainerBuilder $container) {
    CacheFactory::registerBin($container, 'filter');

    $container->register('access_check.filter_disable', 'Drupal\filter\Access\FormatDisableCheck')
      ->addTag('access_check');
  }

}
