<?php

namespace Drupal\Core\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Sets the _deprecated_service_list parameter.
 *
 * @see \Drupal\Component\DependencyInjection\Container::get()
 */
class DeprecatedServicePass implements CompilerPassInterface {

  /**
   * {@inheritdoc}
   */
  public function process(ContainerBuilder $container) {
    $deprecated_services = [];
    foreach ($container->getDefinitions() as $service_id => $definition) {
      if ($definition->isDeprecated()) {
        $deprecated_services[$service_id] = $definition->getDeprecationMessage($service_id);
      }
    }
    foreach ($container->getAliases() as $service_id => $definition) {
      if ($definition->isDeprecated()) {
        $deprecated_services[$service_id] = $definition->getDeprecationMessage($service_id);
      }
    }
    $container->setParameter('_deprecated_service_list', $deprecated_services);
  }

}
