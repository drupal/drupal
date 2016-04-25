<?php

namespace Drupal\serialization;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;

/**
 * Serialization dependency injection container.
 */
class SerializationServiceProvider implements ServiceProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    // Add a compiler pass for adding Normalizers and Encoders to Serializer.
    $container->addCompilerPass(new RegisterSerializationClassesCompilerPass());
    // Add a compiler pass for adding concrete Resolvers to chain Resolver.
    $container->addCompilerPass(new RegisterEntityResolversCompilerPass());
  }
}
