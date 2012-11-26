<?php

/**
 * @file
 * Contains Drupal\system\SystemBundle.
 */

namespace Drupal\system;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * System dependency injection container.
 */
class SystemBundle extends Bundle {

  /**
   * Overrides Bundle::build().
   */
  public function build(ContainerBuilder $container) {
    $container->register('access_check.cron', 'Drupal\system\Access\CronAccessCheck')
      ->addTag('access_check');
  }
}
