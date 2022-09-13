<?php

namespace Drupal\Core\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Sets the _serviceId property on all services.
 *
 * @deprecated in drupal:9.5.0 and is removed from drupal:11.0.0. The _serviceId
 *   property is no longer part of the container. Use
 *   \Drupal\Core\DrupalKernelInterface::getServiceIdMapping() instead.
 *
 * @see https://www.drupal.org/node/3292540
 * @see \Drupal\Core\DependencyInjection\DependencySerializationTrait
 */
class DependencySerializationTraitPass implements CompilerPassInterface {

  /**
   * {@inheritdoc}
   */
  public function process(ContainerBuilder $container) {
  }

}
