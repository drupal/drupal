<?php

/**
 * @file
 * Contains \Drupal\session_test\SessionTestBundle.
 */

namespace Drupal\session_test;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Defines the SessionTest bundle.
 */
class SessionTestBundle extends Bundle {

  /**
   * Overrides \Symfony\Component\HttpKernel\Bundle\Bundle::build().
   */
  public function build(ContainerBuilder $container) {
    $container->register('session_test.subscriber', 'Drupal\session_test\EventSubscriber\SessionTestSubscriber')
      ->addTag('event_subscriber');
  }
}
