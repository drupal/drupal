<?php

/**
 * @file
 * Contains \Drupal\serialization\SerializationBundle.
 */

namespace Drupal\serialization;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Serialization dependency injection container.
 */
class SerializationBundle extends Bundle {

  /**
   * Overrides \Symfony\Component\HttpKernel\Bundle\Bundle::build().
   */
  public function build(ContainerBuilder $container) {
    // Add a compiler pass for adding Normalizers and Encoders to Serializer.
    $container->addCompilerPass(new RegisterSerializationClassesCompilerPass());
    // Add a compiler pass for adding concrete Resolvers to chain Resolver.
    $container->addCompilerPass(new RegisterEntityResolversCompilerPass());
  }
}
