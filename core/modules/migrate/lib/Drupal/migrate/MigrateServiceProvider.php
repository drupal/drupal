<?php

/**
 * @file
 * Contains \Drupal\migrate\MigrateServiceProvider.
 */

namespace Drupal\migrate;

use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;

/**
 * Swaps the original 'password' service in order to handle password hashing for
 * user migrations that have passwords hashed to MD5.
 *
 * @see \Drupal\migrate\MigratePassword
 * @see \Drupal\Core\Password\PhpassHashedPassword
 */
class MigrateServiceProvider implements ServiceModifierInterface {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $container->setDefinition('password_original', $container->getDefinition('password'));
    $container->setDefinition('password', $container->getDefinition('password_migrate'));
  }

}
