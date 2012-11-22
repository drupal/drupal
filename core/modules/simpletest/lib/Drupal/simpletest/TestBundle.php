<?php

namespace Drupal\simpletest;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class TestBundle extends Bundle {

  /**
   * @var \Drupal\simpletest\TestBase;
   */
  public static $currentTest;

  /**
   * Implements \Symfony\Component\HttpKernel\Bundle\BundleInterface::build().
   */
  function build(ContainerBuilder $container) {
    if (static::$currentTest && method_exists(static::$currentTest, 'containerBuild')) {
      static::$currentTest->containerBuild($container);
    }
  }

}
