<?php

/**
 * @file
 * Contains \Drupal\devel\DevelBundle.
 */

namespace Drupal\devel;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\Bundle;


/**
 * Registers devel module's services to the container.
 */
class DevelBundle extends Bundle {

  /**
   * Implements \Symfony\Component\HttpKernel\Bundle\BundleInterface::build().
   */
  public function build(ContainerBuilder $container) {
    $container->register('devel.event_subscriber', 'Drupal\devel\EventSubscriber\DevelEventSubscriber')
      ->addArgument(new Reference('config.factory'))
      ->addTag('event_subscriber');
  }

}
