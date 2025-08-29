<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\DrupalKernel;

use Composer\Autoload\ClassLoader;
use Drupal\Core\DrupalKernel;
use Drupal\Core\Site\Settings;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests Drupal\Core\DrupalKernel.
 */
#[CoversClass(DrupalKernel::class)]
#[Group('DrupalKernel')]
class DiscoverServiceProvidersTest extends UnitTestCase {

  /**
   * Tests discovery with user defined container yaml.
   *
   * @legacy-covers ::discoverServiceProviders
   */
  public function testDiscoverServiceCustom(): void {
    new Settings([
      'container_yamls' => [
        __DIR__ . '/fixtures/custom.yml',
      ],
    ]);

    $kernel = new DrupalKernel('prod', new ClassLoader());
    $kernel->discoverServiceProviders();

    $reflected_yamls = (new \ReflectionObject($kernel))->getProperty('serviceYamls');

    $expect = [
      'app' => [
        'core' => 'core/core.services.yml',
      ],
      'site' => [
        __DIR__ . '/fixtures/custom.yml',
      ],
    ];
    $this->assertSame($expect, $reflected_yamls->getValue($kernel));
  }

  /**
   * Tests the exception when container_yamls is not set.
   */
  public function testDiscoverServiceNoContainerYamls(): void {
    new Settings([]);
    $kernel = new DrupalKernel('prod', new ClassLoader());
    $kernel->discoverServiceProviders();

    $reflected_yamls = (new \ReflectionObject($kernel))->getProperty('serviceYamls');

    $expect = [
      'app' => [
        'core' => 'core/core.services.yml',
      ],
      'site' => [],
    ];
    $this->assertSame($expect, $reflected_yamls->getValue($kernel));
  }

}
