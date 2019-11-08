<?php

namespace Drupal\KernelTests\Core\Theme;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Extension\ExtensionNameLengthException;
use Drupal\Core\Extension\Exception\UnknownExtensionException;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests installing and uninstalling of themes.
 *
 * @group Extension
 */
class ThemeInstallerTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system'];

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    parent::register($container);
    // Some test methods involve ModuleHandler operations, which attempt to
    // rebuild and dump routes.
    $container
      ->register('router.dumper', 'Drupal\Core\Routing\NullMatcherDumper');
  }

  protected function setUp() {
    parent::setUp();
    $this->installConfig(['system']);
  }

  /**
   * Verifies that no themes are installed by default.
   */
  public function testEmpty() {
    $this->assertEmpty($this->extensionConfig()->get('theme'));

    $this->assertEmpty(array_keys($this->themeHandler()->listInfo()));
    $this->assertEmpty(array_keys(\Drupal::service('theme_handler')->listInfo()));

    // Rebuilding available themes should always yield results though.
    $this->assertNotEmpty($this->themeHandler()->rebuildThemeData()['stark'], 'ThemeHandler::rebuildThemeData() yields all available themes.');

    // theme_get_setting() should return global default theme settings.
    $this->assertIdentical(theme_get_setting('features.favicon'), TRUE);
  }

  /**
   * Tests installing a theme.
   */
  public function testInstall() {
    $name = 'test_basetheme';

    $themes = $this->themeHandler()->listInfo();
    $this->assertFalse(isset($themes[$name]));

    $this->themeInstaller()->install([$name]);

    $this->assertIdentical($this->extensionConfig()->get("theme.$name"), 0);

    $themes = $this->themeHandler()->listInfo();
    $this->assertTrue(isset($themes[$name]));
    $this->assertEqual($themes[$name]->getName(), $name);

    // Verify that test_basetheme.settings is active.
    $this->assertIdentical(theme_get_setting('features.favicon', $name), FALSE);
    $this->assertEqual(theme_get_setting('base', $name), 'only');
    $this->assertEqual(theme_get_setting('override', $name), 'base');
  }

  /**
   * Tests installing a sub-theme.
   */
  public function testInstallSubTheme() {
    $name = 'test_subtheme';
    $base_name = 'test_basetheme';

    $themes = $this->themeHandler()->listInfo();
    $this->assertEmpty(array_keys($themes));

    $this->themeInstaller()->install([$name]);

    $themes = $this->themeHandler()->listInfo();
    $this->assertTrue(isset($themes[$name]));
    $this->assertTrue(isset($themes[$base_name]));

    $this->themeInstaller()->uninstall([$name]);

    $themes = $this->themeHandler()->listInfo();
    $this->assertFalse(isset($themes[$name]));
    $this->assertTrue(isset($themes[$base_name]));
  }

  /**
   * Tests installing a non-existing theme.
   */
  public function testInstallNonExisting() {
    $name = 'non_existing_theme';

    $themes = $this->themeHandler()->listInfo();
    $this->assertEmpty(array_keys($themes));

    try {
      $message = 'ThemeInstaller::install() throws UnknownExtensionException upon installing a non-existing theme.';
      $this->themeInstaller()->install([$name]);
      $this->fail($message);
    }
    catch (UnknownExtensionException $e) {
      $this->pass(get_class($e) . ': ' . $e->getMessage());
    }

    $themes = $this->themeHandler()->listInfo();
    $this->assertEmpty(array_keys($themes));
  }

  /**
   * Tests installing a theme with a too long name.
   */
  public function testInstallNameTooLong() {
    $name = 'test_theme_having_veery_long_name_which_is_too_long';

    try {
      $message = 'ThemeInstaller::install() throws ExtensionNameLengthException upon installing a theme with a too long name.';
      $this->themeInstaller()->install([$name]);
      $this->fail($message);
    }
    catch (ExtensionNameLengthException $e) {
      $this->pass(get_class($e) . ': ' . $e->getMessage());
    }
  }

  /**
   * Tests uninstalling the default theme.
   */
  public function testUninstallDefault() {
    $name = 'stark';
    $other_name = 'bartik';
    $this->themeInstaller()->install([$name, $other_name]);
    $this->config('system.theme')->set('default', $name)->save();

    $themes = $this->themeHandler()->listInfo();
    $this->assertTrue(isset($themes[$name]));
    $this->assertTrue(isset($themes[$other_name]));

    try {
      $message = 'ThemeInstaller::uninstall() throws InvalidArgumentException upon disabling default theme.';
      $this->themeInstaller()->uninstall([$name]);
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
  public function testUninstallAdmin() {
    $name = 'stark';
    $other_name = 'bartik';
    $this->themeInstaller()->install([$name, $other_name]);
    $this->config('system.theme')->set('admin', $name)->save();

    $themes = $this->themeHandler()->listInfo();
    $this->assertTrue(isset($themes[$name]));
    $this->assertTrue(isset($themes[$other_name]));

    try {
      $message = 'ThemeInstaller::uninstall() throws InvalidArgumentException upon disabling admin theme.';
      $this->themeInstaller()->uninstall([$name]);
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
  public function testUninstallSubTheme() {
    $name = 'test_subtheme';
    $base_name = 'test_basetheme';

    $this->themeInstaller()->install([$name]);
    $this->themeInstaller()->uninstall([$name]);

    $themes = $this->themeHandler()->listInfo();
    $this->assertFalse(isset($themes[$name]));
    $this->assertTrue(isset($themes[$base_name]));
  }

  /**
   * Tests uninstalling a base theme before its sub-theme.
   */
  public function testUninstallBaseBeforeSubTheme() {
    $name = 'test_basetheme';
    $sub_name = 'test_subtheme';

    $this->themeInstaller()->install([$sub_name]);

    try {
      $message = 'ThemeInstaller::install() throws InvalidArgumentException upon uninstalling base theme before sub theme.';
      $this->themeInstaller()->uninstall([$name]);
      $this->fail($message);
    }
    catch (\InvalidArgumentException $e) {
      $this->pass(get_class($e) . ': ' . $e->getMessage());
    }

    $themes = $this->themeHandler()->listInfo();
    $this->assertTrue(isset($themes[$name]));
    $this->assertTrue(isset($themes[$sub_name]));

    // Verify that uninstalling both at the same time works.
    $this->themeInstaller()->uninstall([$name, $sub_name]);

    $themes = $this->themeHandler()->listInfo();
    $this->assertFalse(isset($themes[$name]));
    $this->assertFalse(isset($themes[$sub_name]));
  }

  /**
   * Tests uninstalling a non-existing theme.
   */
  public function testUninstallNonExisting() {
    $name = 'non_existing_theme';

    $themes = $this->themeHandler()->listInfo();
    $this->assertEmpty(array_keys($themes));

    try {
      $message = 'ThemeInstaller::uninstall() throws UnknownExtensionException upon uninstalling a non-existing theme.';
      $this->themeInstaller()->uninstall([$name]);
      $this->fail($message);
    }
    catch (UnknownExtensionException $e) {
      $this->pass(get_class($e) . ': ' . $e->getMessage());
    }

    $themes = $this->themeHandler()->listInfo();
    $this->assertEmpty(array_keys($themes));
  }

  /**
   * Tests uninstalling a theme.
   */
  public function testUninstall() {
    $name = 'test_basetheme';

    $this->themeInstaller()->install([$name]);
    $this->assertNotEmpty($this->config("$name.settings")->get());

    $this->themeInstaller()->uninstall([$name]);

    $this->assertEmpty(array_keys($this->themeHandler()->listInfo()));

    $this->assertEmpty($this->config("$name.settings")->get());

    // Ensure that the uninstalled theme can be installed again.
    $this->themeInstaller()->install([$name]);
    $themes = $this->themeHandler()->listInfo();
    $this->assertTrue(isset($themes[$name]));
    $this->assertEqual($themes[$name]->getName(), $name);
    $this->assertNotEmpty($this->config("$name.settings")->get());
  }

  /**
   * Tests uninstalling a theme that is not installed.
   */
  public function testUninstallNotInstalled() {
    $name = 'test_basetheme';

    try {
      $message = 'ThemeInstaller::uninstall() throws UnknownExtensionException upon uninstalling a theme that is not installed.';
      $this->themeInstaller()->uninstall([$name]);
      $this->fail($message);
    }
    catch (UnknownExtensionException $e) {
      $this->pass(get_class($e) . ': ' . $e->getMessage());
    }
  }

  /**
   * Tests that theme info can be altered by a module.
   *
   * @see module_test_system_info_alter()
   */
  public function testThemeInfoAlter() {
    $name = 'seven';
    $this->container->get('state')->set('module_test.hook_system_info_alter', TRUE);

    $this->themeInstaller()->install([$name]);

    $themes = $this->themeHandler()->listInfo();
    $this->assertFalse(isset($themes[$name]->info['regions']['test_region']));

    // Install module_test.
    $this->moduleInstaller()->install(['module_test'], FALSE);
    $this->assertTrue($this->moduleHandler()->moduleExists('module_test'));

    $themes = $this->themeHandler()->listInfo();
    $this->assertTrue(isset($themes[$name]->info['regions']['test_region']));

    // Legacy assertions.
    // @todo Remove once theme initialization/info has been modernized.
    // @see https://www.drupal.org/node/2228093
    $info = \Drupal::service('extension.list.theme')->getExtensionInfo($name);
    $this->assertTrue(isset($info['regions']['test_region']));
    $regions = system_region_list($name);
    $this->assertTrue(isset($regions['test_region']));
    $theme_list = \Drupal::service('theme_handler')->listInfo();
    $this->assertTrue(isset($theme_list[$name]->info['regions']['test_region']));

    $this->moduleInstaller()->uninstall(['module_test']);
    $this->assertFalse($this->moduleHandler()->moduleExists('module_test'));

    $themes = $this->themeHandler()->listInfo();
    $this->assertFalse(isset($themes[$name]->info['regions']['test_region']));

    // Legacy assertions.
    // @todo Remove once theme initialization/info has been modernized.
    // @see https://www.drupal.org/node/2228093
    $info = \Drupal::service('extension.list.theme')->getExtensionInfo($name);
    $this->assertFalse(isset($info['regions']['test_region']));
    $regions = system_region_list($name);
    $this->assertFalse(isset($regions['test_region']));
    $theme_list = \Drupal::service('theme_handler')->listInfo();
    $this->assertFalse(isset($theme_list[$name]->info['regions']['test_region']));
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
   * Returns the theme installer service.
   *
   * @return \Drupal\Core\Extension\ThemeInstallerInterface
   */
  protected function themeInstaller() {
    return $this->container->get('theme_installer');
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
