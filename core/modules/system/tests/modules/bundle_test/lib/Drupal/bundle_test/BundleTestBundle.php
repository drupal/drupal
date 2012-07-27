<?php

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
  }
}