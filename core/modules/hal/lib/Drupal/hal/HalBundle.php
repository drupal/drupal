<?php

/**
 * @file
 * Contains \Drupal\hal\HalBundle.
 */

namespace Drupal\hal;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * HAL dependency injection container.
 */
class HalBundle extends Bundle {

  /**
   * Overrides \Symfony\Component\HttpKernel\Bundle\Bundle::build().
   */
  public function build(ContainerBuilder $container) {
    $priority = 10;

    $container->register('serializer.normalizer.entity_reference_item.hal', 'Drupal\hal\Normalizer\EntityReferenceItemNormalizer')
      ->addMethodCall('setLinkManager', array(new Reference('rest.link_manager')))
      ->addTag('normalizer', array('priority' => $priority));
    $container->register('serializer.normalizer.field_item.hal', 'Drupal\hal\Normalizer\FieldItemNormalizer')
      ->addMethodCall('setLinkManager', array(new Reference('rest.link_manager')))
      ->addTag('normalizer', array('priority' => $priority));
    $container->register('serializer.normalizer.field.hal', 'Drupal\hal\Normalizer\FieldNormalizer')
      ->addMethodCall('setLinkManager', array(new Reference('rest.link_manager')))
      ->addTag('normalizer', array('priority' => $priority));
    $container->register('serializer.normalizer.entity.hal', 'Drupal\hal\Normalizer\EntityNormalizer')
      ->addMethodCall('setLinkManager', array(new Reference('rest.link_manager')))
      ->addTag('normalizer', array('priority' => $priority));

    $container->register('serializer.encoder.hal', 'Drupal\hal\Encoder\JsonEncoder')
      ->addTag('encoder', array(
        'priority' => $priority,
        'format' => array(
          'hal_json' => 'HAL (JSON)',
        ),
      ));

    $container->register('hal.subscriber', 'Drupal\hal\HalSubscriber')
      ->addTag('event_subscriber');
  }
}
