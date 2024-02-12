<?php

namespace Drupal\Core\DependencyInjection\Compiler;

use Drupal\Core\Access\AccessCheckInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Adds services tagged 'access_check' to the access_manager service.
 */
class RegisterAccessChecksPass implements CompilerPassInterface {

  /**
   * {@inheritdoc}
   */
  public function process(ContainerBuilder $container) {
    if (!$container->hasDefinition('access_manager')) {
      return;
    }
    $services = [];
    $dynamic_access_check_services = [];
    // Add services tagged 'access_check' to the access_manager service.
    $access_manager = $container->getDefinition('access_manager.check_provider');
    foreach ($container->findTaggedServiceIds('access_check') as $id => $attributes) {
      $applies = [];
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
      $access_manager->addMethodCall('addCheckService', [$id, $method, $applies, $needs_incoming_request]);

      // Collect dynamic access checker services.
      $class = $container->getDefinition($id)->getClass();
      if (in_array(AccessCheckInterface::class, class_implements($class), TRUE)) {
        $dynamic_access_check_services[] = $id;
      }

      $services[$id] = new Reference($id);
    }
    $access_manager->addArgument(ServiceLocatorTagPass::register($container, $services));

    $container->setParameter('dynamic_access_check_services', $dynamic_access_check_services);
  }

}
