<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Config;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests module overrides of configuration using event subscribers.
 *
 * @group config
 */
class ConfigModuleOverridesTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'config', 'config_override_test'];

  /**
   * Tests simple module overrides of configuration using event subscribers.
   */
  public function testSimpleModuleOverrides(): void {
    $GLOBALS['config_test_run_module_overrides'] = TRUE;
    $name = 'system.site';
    $overridden_name = 'Wow overridden site name';
    $non_overridden_name = 'Wow this name is on disk mkay';
    $overridden_slogan = 'Yay for overrides!';
    $non_overridden_slogan = 'Yay for defaults!';
    $config_factory = $this->container->get('config.factory');
    $config_factory
      ->getEditable($name)
      ->set('name', $non_overridden_name)
      ->set('slogan', $non_overridden_slogan)
      // `name` and `slogan` are translatable, hence a `langcode` is required.
      // @see \Drupal\Core\Config\Plugin\Validation\Constraint\LangcodeRequiredIfTranslatableValuesConstraint
      ->set('langcode', 'en')
      ->save();

    $this->assertEquals($non_overridden_name, $config_factory->get('system.site')->getOriginal('name', FALSE));
    $this->assertEquals($non_overridden_slogan, $config_factory->get('system.site')->getOriginal('slogan', FALSE));
    $this->assertEquals($overridden_name, $config_factory->get('system.site')->get('name'));
    $this->assertEquals($overridden_slogan, $config_factory->get('system.site')->get('slogan'));

    // Test overrides of completely new configuration objects. In normal runtime
    // this should only happen for configuration entities as we should not be
    // creating simple configuration objects on the fly.
    $config = $config_factory->get('config_override_test.new');
    $this->assertTrue($config->isNew(), 'The configuration object config_override_test.new is new');
    $this->assertSame('override', $config->get('module'));
    $this->assertNull($config->getOriginal('module', FALSE));

    unset($GLOBALS['config_test_run_module_overrides']);
  }

}
