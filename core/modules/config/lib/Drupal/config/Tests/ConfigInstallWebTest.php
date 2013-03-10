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
class ConfigInstallWebTest extends WebTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Install, disable and uninstall functionality',
      'description' => 'Tests installation and removal of configuration objects in install, disable and uninstall functionality.',
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
   * Tests module re-installation.
   */
  function testIntegrationModuleReinstallation() {
    $default_config = 'config_integration_test.settings';
    $default_configuration_entity = 'config_test.dynamic.config_integration_test';

    // Install the config_test module we're integrating with.
    module_enable(array('config_test'));

    // Verify the configuration does not exist prior to installation.
    $config_static = config($default_config);
    $this->assertIdentical($config_static->isNew(), TRUE);
    $config_entity = config($default_configuration_entity);
    $this->assertIdentical($config_entity->isNew(), TRUE);

    // Install the integration module.
    module_enable(array('config_integration_test'));

    // Verify that default module config exists.
    $config_static = config($default_config);
    $this->assertIdentical($config_static->isNew(), FALSE);
    $this->assertIdentical($config_static->get('foo'), 'default setting');
    $config_entity = config($default_configuration_entity);
    $this->assertIdentical($config_entity->isNew(), FALSE);
    $this->assertIdentical($config_entity->get('label'), 'Default integration config label');

    // Customize both configuration objects.
    $config_static->set('foo', 'customized setting')->save();
    $config_entity->set('label', 'Customized integration config label')->save();

    // @todo FIXME: Setting config keys WITHOUT SAVING retains the changed config
    //   object in memory. Every new call to config() MUST revert in-memory changes
    //   that haven't been saved!
    //   In other words: This test passes even without this reset, but it shouldn't.
    $this->container->get('config.factory')->reset();

    // Disable and enable the integration module.
    module_disable(array('config_integration_test'));
    module_enable(array('config_integration_test'));

    // Verify that customized config exists.
    $config_static = config($default_config);
    $this->assertIdentical($config_static->isNew(), FALSE);
    $this->assertIdentical($config_static->get('foo'), 'customized setting');
    $config_entity = config($default_configuration_entity);
    $this->assertIdentical($config_entity->isNew(), FALSE);
    $this->assertIdentical($config_entity->get('label'), 'Customized integration config label');

    // Disable and uninstall the integration module.
    module_disable(array('config_integration_test'));
    module_uninstall(array('config_integration_test'));

    // Verify the integration module's config was uninstalled.
    $config_static = config($default_config);
    $this->assertIdentical($config_static->isNew(), TRUE);

    // Verify the integration config still exists.
    $config_entity = config($default_configuration_entity);
    $this->assertIdentical($config_entity->isNew(), FALSE);
    $this->assertIdentical($config_entity->get('label'), 'Customized integration config label');

    // Reinstall the integration module.
    module_enable(array('config_integration_test'));

    // Verify the integration module's config was re-installed.
    $config_static = config($default_config);
    $this->assertIdentical($config_static->isNew(), FALSE);
    $this->assertIdentical($config_static->get('foo'), 'default setting');

    // Verify the customized integration config still exists.
    $config_entity = config($default_configuration_entity);
    $this->assertIdentical($config_entity->isNew(), FALSE);
    $this->assertIdentical($config_entity->get('label'), 'Customized integration config label');
  }

}
