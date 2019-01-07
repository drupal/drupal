<?php

namespace Drupal\Tests\Core\Extension;

use Drupal\Tests\UnitTestCase;
use Drupal\Core\Extension\Extension;

/**
 * Tests Extension serialization.
 *
 * @coversDefaultClass \Drupal\Core\Extension\Extension
 * @group Extension
 */
class ExtensionSerializationTest extends UnitTestCase {

  /**
   * Tests dynamically assigned public properties kept when serialized.
   *
   * @covers ::__sleep
   * @covers ::__wakeup
   * @runInSeparateProcess
   */
  public function testPublicProperties() {
    define('DRUPAL_ROOT', '/dummy/app/root');
    $extension = new Extension('/dummy/app/root', 'module', 'core/modules/system/system.info.yml', 'system.module');
    // Assign a public property dynamically.
    $extension->test = 'foo';
    $extension = unserialize(serialize($extension));
    $this->assertSame('foo', $extension->test);
  }

}
