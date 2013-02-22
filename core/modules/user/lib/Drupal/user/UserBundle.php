<?php

/**
 * @file
 * Contains Drupal\user\UserBundle.
 */

namespace Drupal\user;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * User dependency injection container.
 */
class UserBundle extends Bundle {

  /**
   * Overrides Symfony\Component\HttpKernel\Bundle\Bundle::build().
   */
  public function build(ContainerBuilder $container) {
    $container->register('access_check.permission', 'Drupal\user\Access\PermissionAccessCheck')
      ->addTag('access_check');
    $container->register('access_check.user.register', 'Drupal\user\Access\RegisterAccessCheck')
      ->addTag('access_check');
    $container
      ->register('user.data', 'Drupal\user\UserData')
      ->addArgument(new Reference('database'));
    $container->register('user.autocomplete_controller', 'Drupal\user\UserAutocompleteController')
      ->addArgument(new Reference('user.autocomplete'));
    $container->register('user.autocomplete', 'Drupal\user\UserAutocomplete')
      ->addArgument(new Reference('database'))
      ->addArgument(new Reference('config.factory'));
  }
}
