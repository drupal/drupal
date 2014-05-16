<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Cache\ApcuBackendUnitTest.
 */

namespace Drupal\system\Tests\Cache;

use Drupal\Core\Cache\ApcuBackend;

/**
 * Tests the APCu cache backend.
 */
class ApcuBackendUnitTest extends GenericCacheBackendUnitTestBase {

  public static function getInfo() {
    return array(
      'name' => 'APCu cache backend',
      'description' => 'Tests the APCu cache backend.',
      'group' => 'Cache',
    );
  }

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
    $this->backend = new ApcuBackend($bin, $this->databasePrefix);
    return $this->backend;
  }

  public function tearDown() {
    $this->backend->removeBin();
    parent::tearDown();
    $this->backend = NULL;
  }

}
