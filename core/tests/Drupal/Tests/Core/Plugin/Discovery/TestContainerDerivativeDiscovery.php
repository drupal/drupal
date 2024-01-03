<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Plugin\Discovery;

use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Defines container test derivative discovery.
 */
class TestContainerDerivativeDiscovery extends TestDerivativeDiscovery implements ContainerDeriverInterface {

  /**
   * Constructs a TestContainerDerivativeDiscovery object.
   *
   * @var \Symfony\Contracts\EventDispatcher\EventDispatcherInterface $example_service
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
