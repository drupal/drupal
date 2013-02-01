<?php

/**
 * @file
 * Definition of Drupal\config\Tests\LocaleConfigOverride.
 */

namespace Drupal\config\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests locale config override.
 */
class LocaleConfigOverride extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('locale', 'config_test');

  public static function getInfo() {
    return array(
      'name' => 'Locale override',
      'description' => 'Confirm that locale overrides work',
      'group' => 'Configuration',
    );
  }

  function testLocaleConfigOverride() {
    $name = 'config_test.system';
    // Verify the default configuration values exist.
    $config = config($name);
    $this->assertIdentical($config->get('foo'), 'en bar');
  }
}
