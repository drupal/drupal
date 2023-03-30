<?php

namespace Drupal\KernelTests\Core\Theme;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Extension\ExtensionNameLengthException;
use Drupal\Core\Extension\MissingDependencyException;
use Drupal\Core\Extension\ModuleUninstallValidatorException;
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
  protected static $modules = ['system'];

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

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
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
    $this->assertTrue(theme_get_setting('features.favicon'));
  }

  /**
   * Tests installing a theme.
   */
  public function testInstall() {
    $name = 'test_basetheme';

    $themes = $this->themeHandler()->listInfo();
    $this->assertFalse(isset($themes[$name]));

    $this->themeInstaller()->install([$name]);

    $this->assertSame(0, $this->extensionConfig()->get("theme.{$name}"));

    $themes = $this->themeHandler()->listInfo();
    $this->assertTrue(isset($themes[$name]));
    $this->assertEquals($name, $themes[$name]->getName());

    // Verify that test_basetheme.settings is active.
    $this->assertFalse(theme_get_setting('features.favicon', $name));
    $this->assertEquals('only', theme_get_setting('base', $name));
    $this->assertEquals('base', theme_get_setting('override', $name));
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
    catch (\Exception $e) {
      $this->assertInstanceOf(UnknownExtensionException::class, $e);
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
    catch (\Exception $e) {
      $this->assertInstanceOf(ExtensionNameLengthException::class, $e);
    }
  }

  /**
   * Tests installing a theme with unmet module dependencies.
   *
   * @dataProvider providerTestInstallThemeWithUnmetModuleDependencies
   */
  public function testInstallThemeWithUnmetModuleDependencies($theme_name, $installed_modules, $message) {
    $this->container->get('module_installer')->install($installed_modules);
    $themes = $this->themeHandler()->listInfo();
    $this->assertEmpty($themes);
    $this->expectException(MissingDependencyException::class);
    $this->expectExceptionMessage($message);
    $this->themeInstaller()->install([$theme_name]);
  }

  /**
   * Tests trying to install a deprecated theme.
   *
   * @covers \Drupal\Core\Extension\ThemeInstaller::install
   *
   * @group legacy
   */
  public function testInstallDeprecated() {
    $this->expectDeprecation("The theme 'deprecated_theme_test' is deprecated. See https://example.com/deprecated");
    $this->themeInstaller()->install(['deprecated_theme_test']);
    $this->assertTrue(\Drupal::service('theme_handler')->themeExists('deprecated_theme_test'));
  }

  /**
   * Data provider for testInstallThemeWithUnmetModuleDependencies().
   */
  public function providerTestInstallThemeWithUnmetModuleDependencies() {
    return [
      'theme with uninstalled module dependencies' => [
        'test_theme_depending_on_modules',
        [],
        "Unable to install theme: 'test_theme_depending_on_modules' due to unmet module dependencies: 'test_module_required_by_theme, test_another_module_required_by_theme'.",
      ],
      'theme with a base theme with uninstalled module dependencies' => [
        'test_theme_with_a_base_theme_depending_on_modules',
        [],
        "Unable to install theme: 'test_theme_with_a_base_theme_depending_on_modules' due to unmet module dependencies: 'test_module_required_by_theme, test_another_module_required_by_theme'.",
      ],
      'theme and base theme have uninstalled module dependencies' => [
        'test_theme_mixed_module_dependencies',
        [],
        "Unable to install theme: 'test_theme_mixed_module_dependencies' due to unmet module dependencies: 'help, test_module_required_by_theme, test_another_module_required_by_theme'.",
      ],
      'theme with already installed module dependencies, base theme module dependencies are not installed' => [
        'test_theme_mixed_module_dependencies',
        ['help'],
        "Unable to install theme: 'test_theme_mixed_module_dependencies' due to unmet module dependencies: 'test_module_required_by_theme, test_another_module_required_by_theme'.",
      ],
      'theme with module dependencies not installed, base theme module dependencies are already installed, ' => [
        'test_theme_mixed_module_dependencies',
        ['test_module_required_by_theme', 'test_another_module_required_by_theme'],
        "Unable to install theme: 'test_theme_mixed_module_dependencies' due to unmet module dependencies: 'help'.",
      ],
      'theme depending on a module that does not exist' => [
        'test_theme_depending_on_nonexisting_module',
        [],
        "Unable to install theme: 'test_theme_depending_on_nonexisting_module' due to unmet module dependencies: 'test_module_non_existing",
      ],
      'theme depending on an installed but incompatible module' => [
        'test_theme_depending_on_constrained_modules',
        ['test_module_compatible_constraint', 'test_module_incompatible_constraint'],
        "Unable to install theme: Test Module Theme Depends on with Incompatible Constraint (>=8.x-2.x) (incompatible with version 8.x-1.8)",
      ],
    ];
  }

  /**
   * Tests installing a theme with module dependencies that are met.
   */
  public function testInstallThemeWithMetModuleDependencies() {
    $name = 'test_theme_depending_on_modules';
    $themes = $this->themeHandler()->listInfo();
    $this->assertArrayNotHasKey($name, $themes);
    $this->container->get('module_installer')->install(['test_module_required_by_theme', 'test_another_module_required_by_theme']);
    $this->themeInstaller()->install([$name]);
    $themes = $this->themeHandler()->listInfo();
    $this->assertArrayHasKey($name, $themes);
    $this->expectException(ModuleUninstallValidatorException::class);
    $this->expectExceptionMessage('The following reasons prevent the modules from being uninstalled: Required by the theme: Test Theme Depending on Modules');
    $this->container->get('module_installer')->uninstall(['test_module_required_by_theme']);
  }

  /**
   * Tests uninstalling the default theme.
   */
  public function testUninstallDefault() {
    $name = 'stark';
    $other_name = 'olivero';
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
    catch (\Exception $e) {
      $this->assertInstanceOf(\InvalidArgumentException::class, $e);
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
    $other_name = 'olivero';
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
    catch (\Exception $e) {
      $this->assertInstanceOf(\InvalidArgumentException::class, $e);
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
    catch (\Exception $e) {
      $this->assertInstanceOf(\InvalidArgumentException::class, $e);
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
    catch (\Exception $e) {
      $this->assertInstanceOf(UnknownExtensionException::class, $e);
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
    $this->assertEquals($name, $themes[$name]->getName());
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
    catch (\Exception $e) {
      $this->assertInstanceOf(UnknownExtensionException::class, $e);
    }
  }

  /**
   * Tests that theme info can be altered by a module.
   *
   * @see module_test_system_info_alter()
   */
  public function testThemeInfoAlter() {
    $name = 'stark';
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
