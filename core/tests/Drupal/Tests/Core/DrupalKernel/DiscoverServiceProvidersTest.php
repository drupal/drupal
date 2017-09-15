<?php

namespace Drupal\Tests\Core\DrupalKernel;

use Composer\Autoload\ClassLoader;
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
   * @covers ::discoverServiceProviders
   */
  public function testDiscoverServiceCustom() {
    new Settings([
      'container_yamls' => [
        __DIR__ . '/fixtures/custom.yml'
      ],
    ]);

    $kernel = new DrupalKernel('prod', new ClassLoader());
    $kernel->discoverServiceProviders();

    $expect = [
      'app' => [
        'core' => 'core/core.services.yml',
      ],
      'site' => [
        __DIR__ . '/fixtures/custom.yml',
      ],
    ];

    $this->assertAttributeSame($expect, 'serviceYamls', $kernel);
  }

  /**
   * Tests the exception when container_yamls is not set.
   */
  public function testDiscoverServiceNoContainerYamls() {
    new Settings([]);
    $kernel = new DrupalKernel('prod', new ClassLoader());
    $kernel->discoverServiceProviders();

    $expect = [
      'app' => [
        'core' => 'core/core.services.yml',
      ],
      'site' => [],
    ];
    $this->assertAttributeSame($expect, 'serviceYamls', $kernel);
  }

}
