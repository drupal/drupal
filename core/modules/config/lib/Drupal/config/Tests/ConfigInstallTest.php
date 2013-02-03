<?php

/**
 * @file
 * Definition of Drupal\config\Tests\ConfigInstallTest.
 */

namespace Drupal\config\Tests;

use Drupal\simpletest\DrupalUnitTestBase;

/**
 * Tests installation of configuration objects in installation functionality.
 */
class ConfigInstallTest extends DrupalUnitTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Installation functionality',
      'description' => 'Tests installation of configuration objects in installation functionality.',
      'group' => 'Configuration',
    );
  }

  function setUp() {
    parent::setUp();

    // Ensure the global variable being asserted by this test does not exist;
    // a previous test executed in this request/process might have set it.
    unset($GLOBALS['hook_config_test']);
  }

  /**
   * Tests module installation.
   */
  function testModuleInstallation() {
    $default_config = 'config_test.system';
    $default_configuration_entity = 'config_test.dynamic.default';
    $default_config_manifest = 'manifest.config_test.dynamic';
    $expected_manifest_data = array(
      'default' => array(
        'name' => 'config_test.dynamic.default',
      ),
    );
    $default_empty_config_manifest = 'manifest.config_test.empty_manifest';

    // Verify that default module config does not exist before installation yet.
    $config = config($default_config);
    $this->assertIdentical($config->isNew(), TRUE);
    $config = config($default_configuration_entity);
    $this->assertIdentical($config->isNew(), TRUE);

    // Verify that configuration entity manifests do not exist.
    $config = config($default_config_manifest);
    $this->assertIdentical($config->isNew(), TRUE);
    $config = config($default_empty_config_manifest);
    $this->assertIdentical($config->isNew(), TRUE);

    // Install the test module.
    $this->enableModules(array('config_test'));

    // Verify that default module config exists.
    $config = config($default_config);
    $this->assertIdentical($config->isNew(), FALSE);
    $config = config($default_configuration_entity);
    $this->assertIdentical($config->isNew(), FALSE);

    // Verify that configuration entity manifests have been created with
    // expected contents.
    $config = config($default_config_manifest);
    $this->assertIdentical($config->get(), $expected_manifest_data);
    $config = config($default_empty_config_manifest);
    $this->assertIdentical($config->get(), array());

    // Verify that configuration import callback was invoked for the dynamic
    // configuration entity.
    $this->assertTrue($GLOBALS['hook_config_import']);

    // Verify that config_test API hooks were invoked for the dynamic default
    // configuration entity.
    $this->assertFalse(isset($GLOBALS['hook_config_test']['load']));
    $this->assertTrue(isset($GLOBALS['hook_config_test']['presave']));
    $this->assertTrue(isset($GLOBALS['hook_config_test']['insert']));
    $this->assertFalse(isset($GLOBALS['hook_config_test']['update']));
    $this->assertFalse(isset($GLOBALS['hook_config_test']['predelete']));
    $this->assertFalse(isset($GLOBALS['hook_config_test']['delete']));
  }
}
