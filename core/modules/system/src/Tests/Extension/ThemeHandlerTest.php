<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Extension\ThemeHandlerTest.
 */

namespace Drupal\system\Tests\Extension;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Extension\ExtensionNameLengthException;
use Drupal\simpletest\DrupalUnitTestBase;

/**
 * Tests installing/enabling, disabling, and uninstalling of themes.
 */
class ThemeHandlerTest extends DrupalUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('system');

  public static function getInfo() {
    return array(
      'name' => 'Theme handler',
      'description' => 'Tests installing/enabling, disabling, and uninstalling of themes.',
      'group' => 'Extension',
    );
  }

  public function containerBuild(ContainerBuilder $container) {
    parent::containerBuild($container);
    // Some test methods involve ModuleHandler operations, which attempt to
    // rebuild and dump routes.
    $container
      ->register('router.dumper', 'Drupal\Core\Routing\NullMatcherDumper');
  }

  function setUp() {
    parent::setUp();
    $this->installConfig(array('system'));
  }

  /**
   * Verifies that no themes are installed/enabled/disabled by default.
   */
  function testEmpty() {
    $this->assertFalse($this->extensionConfig()->get('theme'));
    $this->assertFalse($this->extensionConfig()->get('disabled.theme'));

    $this->assertFalse(array_keys($this->themeHandler()->listInfo()));
    $this->assertFalse(array_keys(system_list('theme')));

    // Rebuilding available themes should always yield results though.
    $this->assertTrue($this->themeHandler()->rebuildThemeData()['stark'], 'ThemeHandler::rebuildThemeData() yields all available themes.');

    // theme_get_setting() should return global default theme settings.
    $this->assertIdentical(theme_get_setting('features.favicon'), TRUE);
  }

  /**
   * Tests enabling a theme.
   */
  function testEnable() {
    $name = 'test_basetheme';

    $themes = $this->themeHandler()->listInfo();
    $this->assertFalse(isset($themes[$name]));

    $this->themeHandler()->enable(array($name));

    $this->assertIdentical($this->extensionConfig()->get("theme.$name"), 0);
    $this->assertNull($this->extensionConfig()->get("disabled.theme.$name"));

    $themes = $this->themeHandler()->listInfo();
    $this->assertTrue(isset($themes[$name]));
    $this->assertEqual($themes[$name]->getName(), $name);

    $this->assertEqual(array_keys(system_list('theme')), array_keys($themes));

    // Verify that test_basetheme.settings is active.
    $this->assertIdentical(theme_get_setting('features.favicon', $name), FALSE);
    $this->assertEqual(theme_get_setting('base', $name), 'only');
    $this->assertEqual(theme_get_setting('override', $name), 'base');
  }

  /**
   * Tests enabling a sub-theme.
   */
  function testEnableSubTheme() {
    $name = 'test_subtheme';
    $base_name = 'test_basetheme';

    $themes = $this->themeHandler()->listInfo();
    $this->assertFalse(array_keys($themes));

    $this->themeHandler()->enable(array($name));

    $themes = $this->themeHandler()->listInfo();
    $this->assertTrue(isset($themes[$name]));
    $this->assertTrue(isset($themes[$base_name]));

    $this->themeHandler()->disable(array($name));

    $themes = $this->themeHandler()->listInfo();
    $this->assertFalse(isset($themes[$name]));
    $this->assertTrue(isset($themes[$base_name]));
  }

  /**
   * Tests enabling a non-existing theme.
   */
  function testEnableNonExisting() {
    $name = 'non_existing_theme';

    $themes = $this->themeHandler()->listInfo();
    $this->assertFalse(array_keys($themes));

    try {
      $message = 'ThemeHandler::enable() throws InvalidArgumentException upon enabling a non-existing theme.';
      $this->themeHandler()->enable(array($name));
      $this->fail($message);
    }
    catch (\InvalidArgumentException $e) {
      $this->pass(get_class($e) . ': ' . $e->getMessage());
    }

    $themes = $this->themeHandler()->listInfo();
    $this->assertFalse(array_keys($themes));
  }

  /**
   * Tests enabling a theme with a too long name.
   */
  function testEnableNameTooLong() {
    $name = 'test_theme_having_veery_long_name_which_is_too_long';

    try {
      $message = 'ThemeHandler::enable() throws ExtensionNameLengthException upon enabling a theme with a too long name.';
      $this->themeHandler()->enable(array($name));
      $this->fail($message);
    }
    catch (ExtensionNameLengthException $e) {
      $this->pass(get_class($e) . ': ' . $e->getMessage());
    }
  }

  /**
   * Tests disabling a theme.
   */
  function testDisable() {
    $name = 'test_basetheme';
    $this->themeHandler()->enable(array($name));

    // Prime the relevant drupal_static()s.
    $this->assertEqual(array_keys(system_list('theme')), array($name));
    $this->assertIdentical(theme_get_setting('features.favicon', $name), FALSE);

    $this->themeHandler()->disable(array($name));

    $this->assertIdentical($this->extensionConfig()->get('theme'), array());
    $this->assertIdentical($this->extensionConfig()->get("disabled.theme.$name"), 0);

    $this->assertFalse(array_keys($this->themeHandler()->listInfo()));
    $this->assertFalse(array_keys(system_list('theme')));

    // Verify that test_basetheme.settings no longer applies, even though the
    // configuration still exists.
    $this->assertIdentical(theme_get_setting('features.favicon', $name), TRUE);
    $this->assertNull(theme_get_setting('base', $name));
    $this->assertNull(theme_get_setting('override', $name));

    // The theme is not uninstalled, so its configuration must still exist.
    $this->assertTrue($this->config("$name.settings")->get());
  }

  /**
   * Tests disabling and enabling a theme.
   *
   * Verifies that
   * - themes can be re-enabled
   * - default configuration is not re-imported upon re-enabling an already
   *   installed theme.
   */
  function testDisableEnable() {
    $name = 'test_basetheme';

    $this->themeHandler()->enable(array($name));
    $this->themeHandler()->disable(array($name));

    $this->assertIdentical($this->config("$name.settings")->get('base'), 'only');
    $this->assertIdentical($this->config('system.date_format.fancy')->get('label'), 'Fancy date');

    // Default configuration never overwrites custom configuration, so just
    // changing values in existing configuration will cause ConfigInstaller to
    // simply skip those files. To ensure that no default configuration is
    // re-imported, the custom configuration has to be deleted.
    $this->configStorage()->delete("$name.settings");
    $this->configStorage()->delete('system.date_format.fancy');
    // Reflect direct storage operations in ConfigFactory.
    $this->container->get('config.factory')->reset();

    $this->themeHandler()->enable(array($name));

    $themes = $this->themeHandler()->listInfo();
    $this->assertTrue(isset($themes[$name]));
    $this->assertEqual($themes[$name]->getName(), $name);

    $this->assertEqual(array_keys(system_list('theme')), array_keys($themes));

    $this->assertFalse($this->config("$name.settings")->get());
    $this->assertNull($this->config('system.date_format.fancy')->get('label'));
  }

  /**
   * Tests disabling the default theme.
   */
  function testDisableDefault() {
    $name = 'stark';
    $other_name = 'bartik';
    $this->themeHandler()->enable(array($name, $other_name));
    $this->themeHandler()->setDefault($name);

    $themes = $this->themeHandler()->listInfo();
    $this->assertTrue(isset($themes[$name]));
    $this->assertTrue(isset($themes[$other_name]));

    try {
      $message = 'ThemeHandler::disable() throws InvalidArgumentException upon disabling default theme.';
      $this->themeHandler()->disable(array($name));
      $this->fail($message);
    }
    catch (\InvalidArgumentException $e) {
      $this->pass(get_class($e) . ': ' . $e->getMessage());
    }

    $themes = $this->themeHandler()->listInfo();
    $this->assertTrue(isset($themes[$name]));
    $this->assertTrue(isset($themes[$other_name]));
  }

  /**
   * Tests disabling the admin theme.
   */
  function testDisableAdmin() {
    $name = 'stark';
    $other_name = 'bartik';
    $this->themeHandler()->enable(array($name, $other_name));
    $this->config('system.theme')->set('admin', $name)->save();

    $themes = $this->themeHandler()->listInfo();
    $this->assertTrue(isset($themes[$name]));
    $this->assertTrue(isset($themes[$other_name]));

    try {
      $message = 'ThemeHandler::disable() throws InvalidArgumentException upon disabling admin theme.';
      $this->themeHandler()->disable(array($name));
      $this->fail($message);
    }
    catch (\InvalidArgumentException $e) {
      $this->pass(get_class($e) . ': ' . $e->getMessage());
    }

    $themes = $this->themeHandler()->listInfo();
    $this->assertTrue(isset($themes[$name]));
    $this->assertTrue(isset($themes[$other_name]));
  }

  /**
   * Tests disabling a sub-theme.
   */
  function testDisableSubTheme() {
    $name = 'test_subtheme';
    $base_name = 'test_basetheme';

    $this->themeHandler()->enable(array($name));
    $this->themeHandler()->disable(array($name));

    $themes = $this->themeHandler()->listInfo();
    $this->assertFalse(isset($themes[$name]));
    $this->assertTrue(isset($themes[$base_name]));
  }

  /**
   * Tests disabling a base theme before its sub-theme.
   */
  function testDisableBaseBeforeSubTheme() {
    $name = 'test_basetheme';
    $sub_name = 'test_subtheme';

    $this->themeHandler()->enable(array($sub_name));

    try {
      $message = 'ThemeHandler::disable() throws InvalidArgumentException upon disabling base theme before sub theme.';
      $this->themeHandler()->disable(array($name));
      $this->fail($message);
    }
    catch (\InvalidArgumentException $e) {
      $this->pass(get_class($e) . ': ' . $e->getMessage());
    }

    $themes = $this->themeHandler()->listInfo();
    $this->assertTrue(isset($themes[$name]));
    $this->assertTrue(isset($themes[$sub_name]));

    // Verify that disabling both at the same time works.
    $this->themeHandler()->disable(array($name, $sub_name));

    $themes = $this->themeHandler()->listInfo();
    $this->assertFalse(isset($themes[$name]));
    $this->assertFalse(isset($themes[$sub_name]));
  }

  /**
   * Tests disabling a non-existing theme.
   */
  function testDisableNonExisting() {
    $name = 'non_existing_theme';

    $themes = $this->themeHandler()->listInfo();
    $this->assertFalse(array_keys($themes));

    try {
      $message = 'ThemeHandler::disable() throws InvalidArgumentException upon disabling a non-existing theme.';
      $this->themeHandler()->disable(array($name));
      $this->fail($message);
    }
    catch (\InvalidArgumentException $e) {
      $this->pass(get_class($e) . ': ' . $e->getMessage());
    }

    $themes = $this->themeHandler()->listInfo();
    $this->assertFalse(array_keys($themes));
  }

  /**
   * Tests that theme info can be altered by a module.
   *
   * @see module_test_system_info_alter()
   */
  function testThemeInfoAlter() {
    $name = 'seven';
    $this->container->get('state')->set('module_test.hook_system_info_alter', TRUE);
    $module_handler = $this->container->get('module_handler');

    $this->themeHandler()->enable(array($name));

    $themes = $this->themeHandler()->listInfo();
    $this->assertFalse(isset($themes[$name]->info['regions']['test_region']));

    $module_handler->install(array('module_test'), FALSE);
    $this->assertTrue($module_handler->moduleExists('module_test'));

    $themes = $this->themeHandler()->listInfo();
    $this->assertTrue(isset($themes[$name]->info['regions']['test_region']));

    // Legacy assertions.
    // @todo Remove once theme initialization/info has been modernized.
    // @see https://drupal.org/node/2228093
    $info = system_get_info('theme', $name);
    $this->assertTrue(isset($info['regions']['test_region']));
    $regions = system_region_list($name);
    $this->assertTrue(isset($regions['test_region']));
    $system_list = system_list('theme');
    $this->assertTrue(isset($system_list[$name]->info['regions']['test_region']));

    $module_handler->uninstall(array('module_test'));
    $this->assertFalse($module_handler->moduleExists('module_test'));

    $themes = $this->themeHandler()->listInfo();
    $this->assertFalse(isset($themes[$name]->info['regions']['test_region']));

    // Legacy assertions.
    // @todo Remove once theme initialization/info has been modernized.
    // @see https://drupal.org/node/2228093
    $info = system_get_info('theme', $name);
    $this->assertFalse(isset($info['regions']['test_region']));
    $regions = system_region_list($name);
    $this->assertFalse(isset($regions['test_region']));
    $system_list = system_list('theme');
    $this->assertFalse(isset($system_list[$name]->info['regions']['test_region']));
  }

  /**
   * Returns the theme handler service.
   *
   * @return \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected function themeHandler() {
    return $this->container->get('theme_handler');
  }

  /**
   * Returns the system.theme config object.
   *
   * @return \Drupal\Core\Config\Config
   */
  protected function extensionConfig() {
    return $this->config('core.extension');
  }

  /**
   * Returns a given config object.
   *
   * @param string $name
   *   The name of the config object to load.
   *
   * @return \Drupal\Core\Config\Config
   */
  protected function config($name) {
    return $this->container->get('config.factory')->get($name);
  }

  /**
   * Returns the active configuration storage.
   *
   * @return \Drupal\Core\Config\ConfigStorageInterface
   */
  protected function configStorage() {
    return $this->container->get('config.storage');
  }

}
