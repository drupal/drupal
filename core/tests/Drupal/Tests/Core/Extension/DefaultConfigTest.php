<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Extension;

use Drupal\Tests\UnitTestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * Tests default configuration of the Extension system.
 *
 * @group Extension
 */
class DefaultConfigTest extends UnitTestCase {

  /**
   * Tests that core.extension.yml is empty by default.
   *
   * The default configuration MUST NOT specify any extensions, because every
   * extension has to be installed in a regular way.
   *
   * Otherwise, the regular runtime application would operate with extensions
   * that were never installed. The default configuration of such extensions
   * would not exist. Installation hooks would never be executed.
   */
  public function testConfigIsEmpty(): void {
    $config = Yaml::parse(file_get_contents($this->root . '/core/config/install/core.extension.yml'));
    $expected = [
      'module' => [],
      'theme' => [],
      'profile' => NULL,
    ];
    $this->assertEquals($expected, $config);
  }

}
