<?php

/**
 * @file
 * Contains \Drupal\system\Tests\DrupalKernel\DrupalKernelSiteTest.
 */

namespace Drupal\system\Tests\DrupalKernel;

use Drupal\simpletest\DrupalUnitTestBase;

/**
 * Tests site-specific service overrides.
 *
 * @group DrupalKernel
 */
class DrupalKernelSiteTest extends DrupalUnitTestBase {

  /**
   * Tests services.yml in site directory.
   */
  public function testServicesYml() {
    $this->assertFalse($this->container->has('site.service.yml'));
    // A service provider class always has precedence over services.yml files.
    // DrupalUnitTestBase::buildContainer() swaps out many services with
    // in-memory implementations already, so those cannot be tested.
    $this->assertIdentical(get_class($this->container->get('cache.backend.database')), 'Drupal\Core\Cache\DatabaseBackendFactory');

    $class = __CLASS__;
    $doc = <<<EOD
services:
  # Add a new service.
  site.service.yml:
    class: $class
  # Swap out a core service.
  cache.backend.database:
    class: Drupal\Core\Cache\MemoryBackendFactory
EOD;
    file_put_contents($this->siteDirectory . '/services.yml', $doc);

    // Rebuild the container.
    $this->container->get('kernel')->rebuildContainer();

    $this->assertTrue($this->container->has('site.service.yml'));
    $this->assertIdentical(get_class($this->container->get('cache.backend.database')), 'Drupal\Core\Cache\MemoryBackendFactory');
  }

}
