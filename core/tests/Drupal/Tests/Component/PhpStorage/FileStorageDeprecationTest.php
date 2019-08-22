<?php

namespace Drupal\Tests\Component\PhpStorage;

use Drupal\Component\PhpStorage\FileStorage;
use PHPUnit\Framework\TestCase;

/**
 * Tests FileStorage deprecations.
 *
 * @coversDefaultClass \Drupal\Component\PhpStorage\FileStorage
 * @group legacy
 * @group Drupal
 * @group PhpStorage
 */
class FileStorageDeprecationTest extends TestCase {

  /**
   * @expectedDeprecation htaccessLines() is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use \Drupal\Component\FileSecurity\FileSecurity::htaccessLines() instead. See https://www.drupal.org/node/3075098
   */
  public function testHtAccessLines() {
    $this->assertNotEmpty(FileStorage::htaccessLines());
  }

}
