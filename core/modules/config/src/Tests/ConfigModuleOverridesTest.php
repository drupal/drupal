<?php

/**
 * @file
 * Definition of Drupal\config\Tests\ConfigModuleOverridesTest.
 */

namespace Drupal\config\Tests;

use Drupal\simpletest\KernelTestBase;

/**
 * Tests module overrides of configuration using event subscribers.
 *
 * @group config
 */
class ConfigModuleOverridesTest extends KernelTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = array('system', 'config', 'config_override_test');

  public function testSimpleModuleOverrides() {
    $GLOBALS['config_test_run_module_overrides'] = TRUE;
    $name = 'system.site';
    $overridden_name = 'ZOMG overridden site name';
    $non_overridden_name = 'ZOMG this name is on disk mkay';
    $overridden_slogan = 'Yay for overrides!';
    $non_overridden_slogan = 'Yay for defaults!';
    $config_factory = $this->container->get('config.factory');
    $config_factory
      ->getEditable($name)
      ->set('name', $non_overridden_name)
      ->set('slogan', $non_overridden_slogan)
      ->save();

    $this->assertTrue($config_factory->getOverrideState(), 'By default ConfigFactory has overrides enabled.');

    $old_state = $config_factory->getOverrideState();

    $config_factory->setOverrideState(FALSE);
    $this->assertFalse($config_factory->getOverrideState(), 'ConfigFactory can disable overrides.');
    $this->assertEqual($non_overridden_name, $config_factory->get('system.site')->get('name'));
    $this->assertEqual($non_overridden_slogan, $config_factory->get('system.site')->get('slogan'));

    $config_factory->setOverrideState(TRUE);
    $this->assertTrue($config_factory->getOverrideState(), 'ConfigFactory can enable overrides.');
    $this->assertEqual($overridden_name, $config_factory->get('system.site')->get('name'));
    $this->assertEqual($overridden_slogan, $config_factory->get('system.site')->get('slogan'));

    // Test overrides of completely new configuration objects. In normal runtime
    // this should only happen for configuration entities as we should not be
    // creating simple configuration objects on the fly.
    $config = $config_factory->get('config_override_test.new');
    $this->assertTrue($config->isNew(), 'The configuration object config_override_test.new is new');
    $this->assertIdentical($config->get('module'), 'override');
    $config_factory->setOverrideState(FALSE);
    $config = $this->config('config_override_test.new');
    $this->assertIdentical($config->get('module'), NULL);

    $config_factory->setOverrideState($old_state);
    unset($GLOBALS['config_test_run_module_overrides']);
  }
}
