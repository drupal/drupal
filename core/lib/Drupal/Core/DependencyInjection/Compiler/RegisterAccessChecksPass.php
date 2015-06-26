<?php

/**
 * @file
 * Contains \Drupal\Core\DependencyInjection\Compiler\RegisterAccessChecksPass.
 */

namespace Drupal\Core\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * Adds services tagged 'access_check' to the access_manager service.
 */
class RegisterAccessChecksPass implements CompilerPassInterface {

  /**
   * Implements CompilerPassInterface::process().
   *
   * Adds services tagged 'access_check' to the access_manager service.
   */
  public function process(ContainerBuilder $container) {
    if (!$container->hasDefinition('access_manager')) {
      return;
    }
    $access_manager = $container->getDefinition('access_manager.check_provider');
    foreach ($container->findTaggedServiceIds('access_check') as $id => $attributes) {
      $applies = array();
      $method = 'access';
      $needs_incoming_request = FALSE;
      foreach ($attributes as $attribute) {
        if (isset($attribute['applies_to'])) {
          $applies[] = $attribute['applies_to'];
        }
        if (isset($attribute['method'])) {
          $method = $attribute['method'];
        }
        if (!empty($attribute['needs_incoming_request'])) {
          $needs_incoming_request = TRUE;
        }
      }
      $access_manager->addMethodCall('addCheckService', array($id, $method, $applies, $needs_incoming_request));
    }
  }
}
