<?php

namespace Drupal\Core\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Sets the _serviceId property on all services.
 *
 * @see \Drupal\Core\DependencyInjection\DependencySerializationTrait
 */
class DependencySerializationTraitPass implements CompilerPassInterface {

  /**
   * {@inheritdoc}
   */
  public function process(ContainerBuilder $container) {
    foreach ($container->getDefinitions() as $service_id => $definition) {
      // Only add the property to services that are public (as private services
      // can not be reloaded through Container::get()) and are objects.
      if (!$definition->hasTag('parameter_service') && $definition->isPublic()) {
        $definition->setProperty('_serviceId', $service_id);
      }
    }
  }

}
