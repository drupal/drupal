<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Extension;

use Drupal\Core\Extension\Extension;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Extension\Extension
 * @group Extension
 * @group legacy
 */
class LegacyExtensionTest extends UnitTestCase {

  /**
   * @covers ::__call
   */
  public function testDeprecatedCall() {
    $extension = new Extension($this->root, 'theme', 'core/themes/stark/stark.info.yml', 'stark.theme');
    $file = $extension->getFileInfo();
    $this->expectDeprecation('Drupal\Core\Extension\Extension::__call(\'getCTime\') is deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. Use \Drupal\Core\Extension\Extension::getFileInfo() instead. See https://www.drupal.org/node/3322608');
    $this->assertSame($file->getCTime(), $extension->getCTime());
    $this->expectDeprecation('Drupal\Core\Extension\Extension::__call(\'getMTime\') is deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. Use \Drupal\Core\Extension\Extension::getFileInfo() instead. See https://www.drupal.org/node/3322608');
    $this->assertSame($file->getMTime(), $extension->getMTime());
  }

}
