<?php

/**
 * @file
 * Contains Drupal\url_alter_test\UrlAlterTestBundle.
 */

namespace Drupal\url_alter_test;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Test bundle class for url_alter_test.
 *
 * Used to register an event subscriber that resolves a path alias to a system
 * path based on an arbitrary set of rules.
 *
 * @see \Drupal\url_alter_test\PathSubscriber
 */
class UrlAlterTestBundle extends Bundle
{
  public function build(ContainerBuilder $container) {
    $container->register('url_alter_test.path_subscriber', 'Drupal\url_alter_test\PathSubscriber')
      ->addTag('event_subscriber');
  }
}
