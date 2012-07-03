<?php

/**
 * @file
 * Definition of Drupal\config\Tests\ConfigInstallTest.
 */

namespace Drupal\config\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests installation of configuration objects in installation functionality.
 */
class ConfigInstallTest extends WebTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Installation functionality',
      'description' => 'Tests installation of configuration objects in installation functionality.',
      'group' => 'Configuration',
    );
  }

  /**
   * Tests module installation.
   */
  function testModuleInstallation() {
    $default_config = 'config_test.system';
    $default_thingie = 'config_test.dynamic.default';

    // Verify that default module config does not exist before installation yet.
    $config = config($default_config);
    $this->assertIdentical($config->isNew(), TRUE);
    $config = config($default_thingie);
    $this->assertIdentical($config->isNew(), TRUE);

    // Install the test module.
    module_enable(array('config_test'));

    // Verify that default module config exists.
    $config = config($default_config);
    $this->assertIdentical($config->isNew(), FALSE);
    $config = config($default_thingie);
    $this->assertIdentical($config->isNew(), FALSE);

    // Verify that configuration import callback was invoked for the dynamic
    // thingie.
    $this->assertTrue($GLOBALS['hook_config_import']);
  }
}
