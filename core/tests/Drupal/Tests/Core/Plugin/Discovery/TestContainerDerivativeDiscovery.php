<?php

namespace Drupal\Tests\Core\Plugin\Discovery;

use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Defines container test derivative discovery.
 */
class TestContainerDerivativeDiscovery extends TestDerivativeDiscovery implements ContainerDeriverInterface {

  /**
   * Constructs a TestContainerDerivativeDiscovery object.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface $example_service
   *   Some service.
   */
  public function __construct(EventDispatcherInterface $example_service) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static($container->get('example_service'));
  }

}
