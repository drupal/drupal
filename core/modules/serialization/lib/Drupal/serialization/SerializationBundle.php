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
    // Add Serializer with arguments to be replaced in the compiler pass.
    $container->register('serializer', 'Symfony\Component\Serializer\Serializer')
      ->addArgument(array())
      ->addArgument(array());

    $container->register('serializer.normalizer.complex_data', 'Drupal\serialization\Normalizer\ComplexDataNormalizer')->addTag('normalizer');
    $container->register('serializer.normalizer.list', 'Drupal\serialization\Normalizer\ListNormalizer')->addTag('normalizer');
    $container->register('serializer.normalizer.typed_data', 'Drupal\serialization\Normalizer\TypedDataNormalizer')->addTag('normalizer');

    $container->register('serializer.encoder.json', 'Drupal\serialization\Encoder\JsonEncoder')
      ->addTag('encoder', array('format' => array('json' => 'JSON')));
    $container->register('serializer.encoder.xml', 'Drupal\serialization\Encoder\XmlEncoder')
      ->addTag('encoder', array('format' => array('xml' => 'XML')));

    // Add a compiler pass for adding Normalizers and Encoders to Serializer.
    $container->addCompilerPass(new RegisterSerializationClassesCompilerPass());
  }
}
