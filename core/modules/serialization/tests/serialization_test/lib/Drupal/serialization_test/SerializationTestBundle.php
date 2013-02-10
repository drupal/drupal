<?php

/**
 * @file
 * Contains \Drupal\serialization_test\SerializationTestBundle.
 */

namespace Drupal\serialization_test;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * SerializationTest dependency injection container.
 */
class SerializationTestBundle extends Bundle {

  /**
   * Overrides Symfony\Component\HttpKernel\Bundle\Bundle::build().
   */
  public function build(ContainerBuilder $container) {
    $container->register('serializer.normalizer.serialization_test', 'Drupal\serialization_test\SerializationTestNormalizer')->addTag('normalizer');
    $container->register('serializer.encoder.serialization_test', 'Drupal\serialization_test\SerializationTestEncoder')
      ->addTag('encoder', array('format' => array('serialization_test' => 'Serialization test')));
  }
}
