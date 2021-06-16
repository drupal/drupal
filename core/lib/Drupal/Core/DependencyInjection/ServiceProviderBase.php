<?php

namespace Drupal\Core\DependencyInjection;

/**
 * Base service provider implementation.
 *
 * @ingroup container
 */
abstract class ServiceProviderBase implements ServiceProviderInterface, ServiceModifierInterface {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
  }

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
  }

}
