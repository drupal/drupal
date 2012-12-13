<?php

/**
 * @file
 * Contains Drupal\config\Tests\ConfigEntityUnitTest.
 */

namespace Drupal\config\Tests;

use Drupal\simpletest\DrupalUnitTestBase;

/**
 * Unit tests for configuration controllers and objects.
 */
class ConfigEntityUnitTest extends DrupalUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('config_test');

  public static function getInfo() {
    return array(
      'name' => 'Configuration entity methods',
      'description' => 'Unit tests for configuration entity base methods.',
      'group' => 'Configuration',
    );
  }

  /**
   * Tests storage controller methods.
   */
  public function testStorageControllerMethods() {
    $controller = entity_get_controller('config_test');
    $info = entity_get_info('config_test');

    $expected = $info['config_prefix'] . '.';
    $this->assertIdentical($controller->getConfigPrefix(), $expected);
  }

}
