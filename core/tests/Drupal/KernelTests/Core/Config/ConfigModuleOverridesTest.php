<?php

namespace Drupal\KernelTests\Core\Config;

use Drupal\KernelTests\KernelTestBase;

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
  protected static $modules = ['system', 'config', 'config_override_test'];

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

    $this->assertEqual($non_overridden_name, $config_factory->get('system.site')->getOriginal('name', FALSE));
    $this->assertEqual($non_overridden_slogan, $config_factory->get('system.site')->getOriginal('slogan', FALSE));
    $this->assertEqual($overridden_name, $config_factory->get('system.site')->get('name'));
    $this->assertEqual($overridden_slogan, $config_factory->get('system.site')->get('slogan'));

    // Test overrides of completely new configuration objects. In normal runtime
    // this should only happen for configuration entities as we should not be
    // creating simple configuration objects on the fly.
    $config = $config_factory->get('config_override_test.new');
    $this->assertTrue($config->isNew(), 'The configuration object config_override_test.new is new');
    $this->assertIdentical($config->get('module'), 'override');
    $this->assertIdentical($config->getOriginal('module', FALSE), NULL);

    unset($GLOBALS['config_test_run_module_overrides']);
  }

}
