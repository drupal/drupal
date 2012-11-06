<?php

/**
 * @file
 * Definition of Drupal\ban\BanBundle.
 */

namespace Drupal\ban;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Defines the Ban bundle.
 */
class BanBundle extends Bundle {

  /**
   * Overrides Symfony\Component\HttpKernel\Bundle\Bundle::build().
   */
  public function build(ContainerBuilder $container) {
    $container->register('ban.ip_manager', 'Drupal\ban\BanIpManager')
      ->addArgument(new Reference('database'));
    $container->register('ban.subscriber', 'Drupal\ban\EventSubscriber\BanSubscriber')
      ->addArgument(new Reference('ban.ip_manager'))
      ->addTag('event_subscriber');
  }
}
