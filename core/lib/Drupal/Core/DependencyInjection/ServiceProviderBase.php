<?php

/**
 * @file
 * Contains \Drupal\Core\DependencyInjection\ServiceProviderBase.
 */

namespace Drupal\Core\DependencyInjection;

/**
 * Base service provider implementation.
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
