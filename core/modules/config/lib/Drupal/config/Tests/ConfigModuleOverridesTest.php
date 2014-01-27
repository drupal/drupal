<?php

/**
 * @file
 * Definition of Drupal\config\Tests\ConfigModuleOverridesTest.
 */

namespace Drupal\config\Tests;

use Drupal\simpletest\DrupalUnitTestBase;

/**
 * Tests module overrides of configuration using event subscribers.
 */
class ConfigModuleOverridesTest extends DrupalUnitTestBase {

  public static $modules = array('system', 'config', 'config_override');

  public static function getInfo() {
    return array(
      'name' => 'Module overrides',
      'description' => 'Tests that modules can override configuration with event subscribers.',
      'group' => 'Configuration',
    );
  }

  public function testSimpleModuleOverrides() {
    $GLOBALS['config_test_run_module_overrides'] = TRUE;
    $name = 'system.site';
    $overridden_name = 'ZOMG overridden site name';
    $non_overridden_name = 'ZOMG this name is on disk mkay';
    $overridden_slogan = 'Yay for overrides!';
    $non_overridden_slogan = 'Yay for defaults!';
    $config_factory = $this->container->get('config.factory');
    $config_factory
      ->get($name)
      ->set('name', $non_overridden_name)
      ->set('slogan', $non_overridden_slogan)
      ->save();

    $config_factory->disableOverrides();
    $this->assertEqual($non_overridden_name, $config_factory->get('system.site')->get('name'));
    $this->assertEqual($non_overridden_slogan, $config_factory->get('system.site')->get('slogan'));

    $config_factory->enableOverrides();
    $this->assertEqual($overridden_name, $config_factory->get('system.site')->get('name'));
    $this->assertEqual($overridden_slogan, $config_factory->get('system.site')->get('slogan'));

    // Test overrides of completely new configuration objects. In normal runtime
    // this should only happen for configuration entities as we should not be
    // creating simple configuration objects on the fly.
    $config = \Drupal::config('config_override.new');
    $this->assertTrue($config->isNew(), 'The configuration object config_override.new is new');
    $this->assertIdentical($config->get('module'), 'override');
    \Drupal::configFactory()->disableOverrides();
    $config = \Drupal::config('config_override.new');
    $this->assertIdentical($config->get('module'), NULL);
    \Drupal::configFactory()->enableOverrides();

    unset($GLOBALS['config_test_run_module_overrides']);
  }
}
