<?php

namespace Drupal\serialization;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;

/**
 * Serialization dependency injection container.
 */
class SerializationServiceProvider implements ServiceProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    // Add a compiler pass for adding Normalizers and Encoders to Serializer.
    // Set a priority of 1 so that this pass runs before
    // see \Drupal\Core\DependencyInjection\Compiler\ContentNegotiationCompilerPass
    $container->addCompilerPass(new RegisterSerializationClassesCompilerPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 1);
    // Add a compiler pass for adding concrete Resolvers to chain Resolver.
    $container->addCompilerPass(new RegisterEntityResolversCompilerPass());
  }

}
