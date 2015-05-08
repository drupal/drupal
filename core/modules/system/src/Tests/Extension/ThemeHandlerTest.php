<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Extension\ThemeHandlerTest.
 */

namespace Drupal\system\Tests\Extension;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Extension\ExtensionNameLengthException;
use Drupal\simpletest\KernelTestBase;

/**
 * Tests installing and uninstalling of themes.
 *
 * @group Extension
 */
class ThemeHandlerTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('system');

  public function containerBuild(ContainerBuilder $container) {
    parent::containerBuild($container);
    // Some test methods involve ModuleHandler operations, which attempt to
    // rebuild and dump routes.
    $container
      ->register('router.dumper', 'Drupal\Core\Routing\NullMatcherDumper');
  }

  protected function setUp() {
    parent::setUp();
    $this->installConfig(array('system'));
  }

  /**
   * Verifies that no themes are installed by default.
   */
  function testEmpty() {
    $this->assertFalse($this->extensionConfig()->get('theme'));

    $this->assertFalse(array_keys($this->themeHandler()->listInfo()));
    $this->assertFalse(array_keys(system_list('theme')));

    // Rebuilding available themes should always yield results though.
    $this->assertTrue($this->themeHandler()->rebuildThemeData()['stark'], 'ThemeHandler::rebuildThemeData() yields all available themes.');

    // theme_get_setting() should return global default theme settings.
    $this->assertIdentical(theme_get_setting('features.favicon'), TRUE);
  }

  /**
   * Tests installing a theme.
   */
  function testInstall() {
    $name = 'test_basetheme';

    $themes = $this->themeHandler()->listInfo();
    $this->assertFalse(isset($themes[$name]));

    $this->themeHandler()->install(array($name));

    $this->assertIdentical($this->extensionConfig()->get("theme.$name"), 0);

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
   * Tests installing a sub-theme.
   */
  function testInstallSubTheme() {
    $name = 'test_subtheme';
    $base_name = 'test_basetheme';

    $themes = $this->themeHandler()->listInfo();
    $this->assertFalse(array_keys($themes));

    $this->themeHandler()->install(array($name));

    $themes = $this->themeHandler()->listInfo();
    $this->assertTrue(isset($themes[$name]));
    $this->assertTrue(isset($themes[$base_name]));

    $this->themeHandler()->uninstall(array($name));

    $themes = $this->themeHandler()->listInfo();
    $this->assertFalse(isset($themes[$name]));
    $this->assertTrue(isset($themes[$base_name]));
  }

  /**
   * Tests installing a non-existing theme.
   */
  function testInstallNonExisting() {
    $name = 'non_existing_theme';

    $themes = $this->themeHandler()->listInfo();
    $this->assertFalse(array_keys($themes));

    try {
      $message = 'ThemeHandler::install() throws InvalidArgumentException upon installing a non-existing theme.';
      $this->themeHandler()->install(array($name));
      $this->fail($message);
    }
    catch (\InvalidArgumentException $e) {
      $this->pass(get_class($e) . ': ' . $e->getMessage());
    }

    $themes = $this->themeHandler()->listInfo();
    $this->assertFalse(array_keys($themes));
  }

  /**
   * Tests installing a theme with a too long name.
   */
  function testInstallNameTooLong() {
    $name = 'test_theme_having_veery_long_name_which_is_too_long';

    try {
      $message = 'ThemeHandler::install() throws ExtensionNameLengthException upon installing a theme with a too long name.';
      $this->themeHandler()->install(array($name));
      $this->fail($message);
    }
    catch (ExtensionNameLengthException $e) {
      $this->pass(get_class($e) . ': ' . $e->getMessage());
    }
  }

  /**
   * Tests uninstalling the default theme.
   */
  function testUninstallDefault() {
    $name = 'stark';
    $other_name = 'bartik';
    $this->themeHandler()->install(array($name, $other_name));
    $this->themeHandler()->setDefault($name);

    $themes = $this->themeHandler()->listInfo();
    $this->assertTrue(isset($themes[$name]));
    $this->assertTrue(isset($themes[$other_name]));

    try {
      $message = 'ThemeHandler::uninstall() throws InvalidArgumentException upon disabling default theme.';
      $this->themeHandler()->uninstall(array($name));
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
   * Tests uninstalling the admin theme.
   */
  function testUninstallAdmin() {
    $name = 'stark';
    $other_name = 'bartik';
    $this->themeHandler()->install(array($name, $other_name));
    $this->config('system.theme')->set('admin', $name)->save();

    $themes = $this->themeHandler()->listInfo();
    $this->assertTrue(isset($themes[$name]));
    $this->assertTrue(isset($themes[$other_name]));

    try {
      $message = 'ThemeHandler::uninstall() throws InvalidArgumentException upon disabling admin theme.';
      $this->themeHandler()->uninstall(array($name));
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
   * Tests uninstalling a sub-theme.
   */
  function testUninstallSubTheme() {
    $name = 'test_subtheme';
    $base_name = 'test_basetheme';

    $this->themeHandler()->install(array($name));
    $this->themeHandler()->uninstall(array($name));

    $themes = $this->themeHandler()->listInfo();
    $this->assertFalse(isset($themes[$name]));
    $this->assertTrue(isset($themes[$base_name]));
  }

  /**
   * Tests uninstalling a base theme before its sub-theme.
   */
  function testUninstallBaseBeforeSubTheme() {
    $name = 'test_basetheme';
    $sub_name = 'test_subtheme';

    $this->themeHandler()->install(array($sub_name));

    try {
      $message = 'ThemeHandler::install() throws InvalidArgumentException upon uninstalling base theme before sub theme.';
      $this->themeHandler()->uninstall(array($name));
      $this->fail($message);
    }
    catch (\InvalidArgumentException $e) {
      $this->pass(get_class($e) . ': ' . $e->getMessage());
    }

    $themes = $this->themeHandler()->listInfo();
    $this->assertTrue(isset($themes[$name]));
    $this->assertTrue(isset($themes[$sub_name]));

    // Verify that uninstalling both at the same time works.
    $this->themeHandler()->uninstall(array($name, $sub_name));

    $themes = $this->themeHandler()->listInfo();
    $this->assertFalse(isset($themes[$name]));
    $this->assertFalse(isset($themes[$sub_name]));
  }

  /**
   * Tests uninstalling a non-existing theme.
   */
  function testUninstallNonExisting() {
    $name = 'non_existing_theme';

    $themes = $this->themeHandler()->listInfo();
    $this->assertFalse(array_keys($themes));

    try {
      $message = 'ThemeHandler::uninstall() throws InvalidArgumentException upon uninstalling a non-existing theme.';
      $this->themeHandler()->uninstall(array($name));
      $this->fail($message);
    }
    catch (\InvalidArgumentException $e) {
      $this->pass(get_class($e) . ': ' . $e->getMessage());
    }

    $themes = $this->themeHandler()->listInfo();
    $this->assertFalse(array_keys($themes));
  }

  /**
   * Tests uninstalling a theme.
   */
  function testUninstall() {
    $name = 'test_basetheme';

    $this->themeHandler()->install(array($name));
    $this->assertTrue($this->config("$name.settings")->get());

    $this->themeHandler()->uninstall(array($name));

    $this->assertFalse(array_keys($this->themeHandler()->listInfo()));
    $this->assertFalse(array_keys(system_list('theme')));

    $this->assertFalse($this->config("$name.settings")->get());

    // Ensure that the uninstalled theme can be installed again.
    $this->themeHandler()->install(array($name));
    $themes = $this->themeHandler()->listInfo();
    $this->assertTrue(isset($themes[$name]));
    $this->assertEqual($themes[$name]->getName(), $name);
    $this->assertEqual(array_keys(system_list('theme')), array_keys($themes));
    $this->assertTrue($this->config("$name.settings")->get());
  }

  /**
   * Tests uninstalling a theme that is not installed.
   */
  function testUninstallNotInstalled() {
    $name = 'test_basetheme';

    try {
      $message = 'ThemeHandler::uninstall() throws InvalidArgumentException upon uninstalling a theme that is not installed.';
      $this->themeHandler()->uninstall(array($name));
      $this->fail($message);
    }
    catch (\InvalidArgumentException $e) {
      $this->pass(get_class($e) . ': ' . $e->getMessage());
    }
  }

  /**
   * Tests that theme info can be altered by a module.
   *
   * @see module_test_system_info_alter()
   */
  function testThemeInfoAlter() {
    $name = 'seven';
    $this->container->get('state')->set('module_test.hook_system_info_alter', TRUE);

    $this->themeHandler()->install(array($name));

    $themes = $this->themeHandler()->listInfo();
    $this->assertFalse(isset($themes[$name]->info['regions']['test_region']));

    // Rebuild module data so we know where module_test is located.
    // @todo Remove as part of https://www.drupal.org/node/2186491
    system_rebuild_module_data();
    $this->moduleInstaller()->install(array('module_test'), FALSE);
    $this->assertTrue($this->moduleHandler()->moduleExists('module_test'));

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

    $this->moduleInstaller()->uninstall(array('module_test'));
    $this->assertFalse($this->moduleHandler()->moduleExists('module_test'));

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
   * Returns the active configuration storage.
   *
   * @return \Drupal\Core\Config\ConfigStorageInterface
   */
  protected function configStorage() {
    return $this->container->get('config.storage');
  }

  /**
   * Returns the ModuleHandler.
   *
   * @return \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected function moduleHandler() {
    return $this->container->get('module_handler');
  }

  /**
   * Returns the ModuleInstaller.
   *
   * @return \Drupal\Core\Extension\ModuleInstallerInterface
   */
  protected function moduleInstaller() {
    return $this->container->get('module_installer');
  }

}
