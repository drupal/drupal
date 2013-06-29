<?php

/**
 * @file
 * Contains \Drupal\Core\DependencyInjection\Compiler\RegisterAuthenticationPass.
 */

namespace Drupal\Core\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * Adds services tagged 'authentication_provider'.
 */
class RegisterAuthenticationPass implements CompilerPassInterface {

  /**
   * Adds authentication providers to the authentication manager.
   *
   * Check for services tagged with 'authentication_provider' and add them to
   * the authentication manager.
   *
   * @see \Drupal\Core\Authentication\AuthenticationManager
   * @see \Drupal\Core\Authentication\AuthenticationProviderInterface
   */
  public function process(ContainerBuilder $container) {
    if (!$container->hasDefinition('authentication')) {
      return;
    }
    // Get the authentication manager.
    $matcher = $container->getDefinition('authentication');
    // Iterate all autentication providers and add them to the manager.
    foreach ($container->findTaggedServiceIds('authentication_provider') as $id => $attributes) {
      $priority = isset($attributes[0]['priority']) ? $attributes[0]['priority'] : 0;
      $matcher->addMethodCall('addProvider', array(
        $id,
        new Reference($id),
        $priority,
      ));
    }
  }
}
