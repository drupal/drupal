<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Cache\ApcuBackendUnitTest.
 */

namespace Drupal\system\Tests\Cache;

use Drupal\Core\Cache\ApcuBackend;

/**
 * Tests the APCu cache backend.
 *
 * @group Cache
 * @requires extension apc
 */
class ApcuBackendUnitTest extends GenericCacheBackendUnitTestBase {

  protected function checkRequirements() {
    $requirements = parent::checkRequirements();
    if (!extension_loaded('apc')) {
      $requirements[] = 'APC extension not found.';
    }
    else {
      if (version_compare(phpversion('apc'), '3.1.1', '<')) {
        $requirements[] = 'APC extension must be newer than 3.1.1 for APCIterator support.';
      }
      if (PHP_SAPI === 'cli' && !ini_get('apc.enable_cli')) {
        $requirements[] = 'apc.enable_cli must be enabled to run this test.';
      }
    }
    return $requirements;
  }

  protected function createCacheBackend($bin) {
    return new ApcuBackend($bin, $this->databasePrefix);
  }

  protected function tearDown() {
    foreach ($this->cachebackends as $bin => $cachebackend) {
      $this->cachebackends[$bin]->removeBin();
    }
    parent::tearDown();
  }

}
