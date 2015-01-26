<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\DrupalKernel\DiscoverServiceProvidersTest.
 */

namespace Drupal\Tests\Core\DrupalKernel;

use Drupal\Core\DrupalKernel;
use Drupal\Core\Site\Settings;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\DrupalKernel
 * @group DrupalKernel
 */
class DiscoverServiceProvidersTest extends UnitTestCase {

  /**
   * Tests discovery with user defined container yaml.
   *
   * @covers ::discoverServiceProviders()
   */
  public function testDiscoverServiceCustom() {
    new Settings(array(
      'container_yamls' => array(
        __DIR__ . '/fixtures/custom.yml'
      ),
    ));

    $kernel = new DrupalKernel('prod', new \Composer\Autoload\ClassLoader());
    $kernel->discoverServiceProviders();

    $expect = array(
      'app' => array(
        'core' => 'core/core.services.yml',
      ),
      'site' => array(
        __DIR__ . '/fixtures/custom.yml',
      ),
    );

    $this->assertAttributeSame($expect, 'serviceYamls', $kernel);
  }

  /**
   * Tests the exception when container_yamls is not set.
   *
   * @covers ::discoverServiceProviders()
   * @expectedException \Exception
   */
  public function testDiscoverServiceNoContainerYamls() {
    new Settings([]);
    $kernel = new DrupalKernel('prod', new \Composer\Autoload\ClassLoader());
    $kernel->discoverServiceProviders();
  }

}
