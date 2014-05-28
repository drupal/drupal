<?php

namespace Drupal\simpletest;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;

class TestServiceProvider implements ServiceProviderInterface {

  /**
   * @var \Drupal\simpletest\TestBase;
   */
  public static $currentTest;

  /**
   * {@inheritdoc}
   */
  function register(ContainerBuilder $container) {
    if (static::$currentTest && method_exists(static::$currentTest, 'containerBuild')) {
      static::$currentTest->containerBuild($container);
    }
  }
}
