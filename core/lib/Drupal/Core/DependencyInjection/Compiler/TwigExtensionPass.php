<?php

namespace Drupal\Core\DependencyInjection\Compiler;

use Drupal\Component\Utility\Crypt;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Adds the twig_extension_hash parameter to the container.
 *
 * Parameter twig_extension_hash is a crc32 hash of all extensions for Twig
 * template invalidation.
 */
class TwigExtensionPass implements CompilerPassInterface {

  /**
   * {@inheritdoc}
   */
  public function process(ContainerBuilder $container) {
    $twig_extension_hash = '';
    foreach (array_keys($container->findTaggedServiceIds('twig.extension')) as $service_id) {
      $class_name = $container->getDefinition($service_id)->getClass();
      $reflection = new \ReflectionClass($class_name);
      // We use the class names as hash in order to invalidate on new extensions
      // and crc32 for every time we change an existing file.
      $twig_extension_hash .= $class_name . hash_file('crc32', $reflection->getFileName());
    }

    $container->setParameter('twig_extension_hash', Crypt::hashBase64($twig_extension_hash));
  }

}
