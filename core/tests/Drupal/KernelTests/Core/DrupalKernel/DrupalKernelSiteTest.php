<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\DrupalKernel;

use Drupal\Core\Site\Settings;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests site-specific service overrides.
 *
 * @group DrupalKernel
 */
class DrupalKernelSiteTest extends KernelTestBase {

  /**
   * Tests services.yml in site directory.
   */
  public function testServicesYml(): void {
    $container_yamls = Settings::get('container_yamls');
    $container_yamls[] = $this->siteDirectory . '/services.yml';
    $this->setSetting('container_yamls', $container_yamls);
    $this->assertFalse($this->container->has('site.service.yml'));
    // A service provider class always has precedence over services.yml files.
    // KernelTestBase::buildContainer() swaps out many services with in-memory
    // implementations already, so those cannot be tested.
    $this->assertSame('Drupal\\Core\\Cache\\DatabaseBackendFactory', get_class($this->container->get('cache.backend.database')));

    $class = __CLASS__;
    $doc = <<<EOD
services:
  _defaults:
    autowire: true
  Symfony\Component\HttpFoundation\RequestStack: ~
  Drupal\Component\Datetime\TimeInterface:
    class: Drupal\Component\Datetime\Time
  # Add a new service.
  site.service.yml:
    class: $class
    arguments: ['test']
  # Swap out a core service.
  cache.backend.database:
    class: Drupal\Core\Cache\MemoryBackendFactory
EOD;
    file_put_contents($this->siteDirectory . '/services.yml', $doc);

    // Rebuild the container.
    $this->container->get('kernel')->rebuildContainer();

    $this->assertTrue($this->container->has('site.service.yml'));
    $this->assertSame('Drupal\\Core\\Cache\\MemoryBackendFactory', get_class($this->container->get('cache.backend.database')));
  }

}
