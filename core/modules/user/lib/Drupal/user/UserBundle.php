<?php

/**
 * @file
 * Contains Drupal\system\UserBundle.
 */

namespace Drupal\user;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * User dependency injection container.
 */
class UserBundle extends Bundle {

  /**
   * Overrides Bundle::build().
   */
  public function build(ContainerBuilder $container) {
    $container->register('access_check.user.register', 'Drupal\user\Access\RegisterAccessCheck')
      ->addTag('access_check');
  }
}
