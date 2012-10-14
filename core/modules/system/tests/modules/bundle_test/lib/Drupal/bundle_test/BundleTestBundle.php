<?php

/**
 * @file
 * Definition of Drupal\bundle_test\BundleTestBundle.
 */

namespace Drupal\bundle_test;

use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Test bundle class.
 */
class BundleTestBundle extends Bundle
{
  public function build(ContainerBuilder $container) {
    $container->register('bundle_test_class', 'Drupal\bundle_test\TestClass')
      ->addTag('kernel.event_subscriber');

    // Override a default bundle used by core to a dummy class.
    $container->register('file.usage', 'Drupal\bundle_test\TestFileUsage');

    // @todo Remove when the 'kernel.event_subscriber' tag above is made to
    //   work: http://drupal.org/node/1706064.
    $container->get('dispatcher')->addSubscriber($container->get('bundle_test_class'));
  }
}
