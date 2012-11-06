<?php

/**
 * @file
 * Definition of Drupal\jsonld\JsonldBundle.
 */

namespace Drupal\jsonld;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\Serializer\Serializer;

/**
 * Jsonld dependency injection container.
 */
class JsonldBundle extends Bundle {

  /**
   * Overrides Symfony\Component\HttpKernel\Bundle\Bundle::build().
   */
  public function build(ContainerBuilder $container) {
    $priority = 5;

    $container->register('serializer.normalizer.jsonld', 'Drupal\jsonld\JsonldNormalizer')->addTag('normalizer', array('priority' => $priority));
    $container->register('serializer.encoder.jsonld', 'Drupal\jsonld\JsonldEncoder')->addTag('encoder', array('priority' => $priority));
    $container->register('serializer.normalizer.drupal_jsonld', 'Drupal\jsonld\DrupalJsonldNormalizer')->addTag('normalizer', array('priority' => $priority));
    $container->register('serializer.encoder.drupal_jsonld', 'Drupal\jsonld\DrupalJsonldEncoder')->addTag('encoder', array('priority' => $priority));
  }
}
