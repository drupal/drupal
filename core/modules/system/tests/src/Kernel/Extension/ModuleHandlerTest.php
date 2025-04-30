<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Kernel\Extension;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Extension\MissingDependencyException;
use Drupal\Core\Extension\ModuleUninstallValidatorException;
use Drupal\Core\Extension\ProfileExtensionList;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests ModuleHandler functionality.
 *
 * @group Extension
 */
class ModuleHandlerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system'];

  /**
   * The basic functionality of retrieving enabled modules.
   */
  public function testModuleList(): void {
    $module_list = ['system'];
    $database_module = \Drupal::database()->getProvider();
    if ($database_module !== 'core') {
      $module_list[] = $database_module;
    }
    sort($module_list);
    $this->assertModuleList($module_list, 'Initial');

    // Try to install a new module.
    $this->moduleInstaller()->install(['dependency_foo_test']);
    $module_list[] = 'dependency_foo_test';
    sort($module_list);
    $this->assertModuleList($module_list, 'After adding a module');

    // Try to mess with the module weights.
    module_set_weight('dependency_foo_test', 20);

    // Move dependency_foo_test to the end of the array.
    unset($module_list[array_search('dependency_foo_test', $module_list)]);
    $module_list[] = 'dependency_foo_test';
    $this->assertModuleList($module_list, 'After changing weights');

    // Test the fixed list feature.
    $fixed_list = [
      'system' => 'core/modules/system/system.module',
      'menu' => 'core/modules/menu/menu.module',
    ];
    $this->moduleHandler()->setModuleList($fixed_list);
    $new_module_list = array_combine(array_keys($fixed_list), array_keys($fixed_list));
    $this->assertModuleList($new_module_list, 'When using a fixed list');
  }

  /**
   * Assert that the extension handler returns the expected values.
   *
   * @param array $expected_values
   *   The expected values, sorted by weight and module name.
   * @param string $condition
   *   The condition being tested, such as 'After adding a module'.
   *
   * @internal
   */
  protected function assertModuleList(array $expected_values, string $condition): void {
    $expected_values = array_values(array_unique($expected_values));
    $enabled_modules = array_keys($this->container->get('module_handler')->getModuleList());
    $this->assertEquals($expected_values, $enabled_modules, "$condition: extension handler returns correct results");
  }

  /**
   * Tests dependency resolution.
   *
   * Intentionally using fake dependencies added via hook_system_info_alter()
   * for modules that normally do not have any dependencies.
   *
   * To simplify things further, all of the manipulated modules are either
   * purely UI-facing or live at the "bottom" of all dependency chains.
   *
   * @see module_test_system_info_alter()
   * @see https://www.drupal.org/files/issues/dep.gv__0.png
   */
  public function testDependencyResolution(): void {
    $this->enableModules(['module_test']);
    $this->assertTrue($this->moduleHandler()->moduleExists('module_test'), 'Test module is enabled.');

    // Ensure that modules are not enabled.
    $this->assertFalse($this->moduleHandler()->moduleExists('dblog'), 'dblog module is disabled.');
    $this->assertFalse($this->moduleHandler()->moduleExists('config'), 'Config module is disabled.');
    $this->assertFalse($this->moduleHandler()->moduleExists('help'), 'Help module is disabled.');

    // Create a missing fake dependency.
    // dblog will depend on Config, which depends on a non-existing module Foo.
    // Nothing should be installed.
    \Drupal::state()->set('module_test.dependency', 'missing dependency');

    try {
      $result = $this->moduleInstaller()->install(['dblog']);
      $this->fail('ModuleInstaller::install() throws an exception if dependencies are missing.');
    }
    catch (MissingDependencyException) {
      // Expected exception; just continue testing.
    }

    $this->assertFalse($this->moduleHandler()->moduleExists('dblog'), 'ModuleInstaller::install() aborts if dependencies are missing.');

    // Fix the missing dependency.
    // dblog module depends on Config. Config depends on Help module.
    \Drupal::state()->set('module_test.dependency', 'dependency');

    $result = $this->moduleInstaller()->install(['dblog']);
    $this->assertTrue($result, 'ModuleInstaller::install() returns the correct value.');

    // Verify that the fake dependency chain was installed.
    $this->assertTrue($this->moduleHandler()->moduleExists('config'));
    $this->assertTrue($this->moduleHandler()->moduleExists('help'));

    // Verify that the original module was installed.
    $this->assertTrue($this->moduleHandler()->moduleExists('dblog'), 'Module installation with dependencies succeeded.');

    // Verify that the modules were enabled in the correct order.
    $module_order = \Drupal::state()->get('module_test.install_order', []);
    $this->assertEquals(['help', 'config', 'dblog'], $module_order);

    // Uninstall all three modules explicitly, but in the incorrect order,
    // and make sure that ModuleInstaller::uninstall() uninstalled them in the
    // correct sequence.
    $result = $this->moduleInstaller()->uninstall(['config', 'help', 'dblog']);
    $this->assertTrue($result, 'ModuleInstaller::uninstall() returned TRUE.');

    /** @var \Drupal\Core\Update\UpdateHookRegistry $update_registry */
    $update_registry = \Drupal::service('update.update_hook_registry');
    foreach (['dblog', 'config', 'help'] as $module) {
      $this->assertEquals($update_registry::SCHEMA_UNINSTALLED, $update_registry->getInstalledVersion($module), "{$module} module was uninstalled.");
    }
    $uninstalled_modules = \Drupal::state()->get('module_test.uninstall_order', []);
    $this->assertEquals(['dblog', 'config', 'help'], $uninstalled_modules, 'Modules were uninstalled in the correct order.');

    // Enable dblog module again, which should enable both the Config module and
    // Help module. But, this time do it with Config module declaring a
    // dependency on a specific version of Help module in its info file. Make
    // sure that Drupal\Core\Extension\ModuleInstaller::install() still works.
    \Drupal::state()->set('module_test.dependency', 'version dependency');

    $result = $this->moduleInstaller()->install(['dblog']);
    $this->assertTrue($result, 'ModuleInstaller::install() returns the correct value.');

    // Verify that the fake dependency chain was installed.
    $this->assertTrue($this->moduleHandler()->moduleExists('config'));
    $this->assertTrue($this->moduleHandler()->moduleExists('help'));

    // Verify that the original module was installed.
    $this->assertTrue($this->moduleHandler()->moduleExists('dblog'), 'Module installation with version dependencies succeeded.');

    // Finally, verify that the modules were enabled in the correct order.
    $enable_order = \Drupal::state()->get('module_test.install_order', []);
    $this->assertSame(['help', 'config', 'dblog'], $enable_order);
  }

  /**
   * Tests uninstalling a module installed by a profile.
   */
  public function testUninstallProfileDependency(): void {
    $profile = 'testing_install_profile_dependencies';
    $dependency = 'dblog';
    $non_dependency = 'dependency_foo_test';
    $this->setInstallProfile($profile);
    // Prime the \Drupal\Core\Extension\ExtensionList::getPathname() static
    // cache with the location of the testing_install_profile_dependencies
    // profile as it is not the currently active profile and we don't yet have
    // any cached way to retrieve its location.
    // @todo Remove as part of https://www.drupal.org/node/2186491
    $profile_list = \Drupal::service('extension.list.profile');
    assert($profile_list instanceof ProfileExtensionList);
    $profile_list->setPathname($profile, 'core/profiles/tests/' . $profile . '/' . $profile . '.info.yml');
    $this->enableModules(['module_test', $profile]);

    $data = \Drupal::service('extension.list.module')->reset()->getList();
    $this->assertArrayHasKey($dependency, $data[$profile]->requires);
    $this->assertArrayNotHasKey($non_dependency, $data[$profile]->requires);

    $this->moduleInstaller()->install([$dependency, $non_dependency]);
    $this->assertTrue($this->moduleHandler()->moduleExists($dependency));

    // Uninstall the profile module that is not a dependent.
    /** @var \Drupal\Core\Update\UpdateHookRegistry $update_registry */
    $update_registry = \Drupal::service('update.update_hook_registry');
    $result = $this->moduleInstaller()->uninstall([$non_dependency]);
    $this->assertTrue($result, 'ModuleInstaller::uninstall() returns TRUE.');
    $this->assertFalse($this->moduleHandler()->moduleExists($non_dependency));
    $this->assertEquals($update_registry::SCHEMA_UNINSTALLED, $update_registry->getInstalledVersion($non_dependency), "$non_dependency module was uninstalled.");

    // Verify that the installation profile itself was not uninstalled.
    $uninstalled_modules = \Drupal::state()->get('module_test.uninstall_order', []);
    $this->assertContains($non_dependency, $uninstalled_modules, "$non_dependency module is in the list of uninstalled modules.");
    $this->assertNotContains($profile, $uninstalled_modules, 'The installation profile is not in the list of uninstalled modules.');

    // Try uninstalling the required module.
    try {
      $this->moduleInstaller()->uninstall([$dependency]);
      $this->fail('Expected ModuleUninstallValidatorException not thrown');
    }
    catch (ModuleUninstallValidatorException $e) {
      $this->assertEquals("The following reasons prevent the modules from being uninstalled: The 'Testing install profile dependencies' install profile requires 'Database Logging'", $e->getMessage());
    }

    // Try uninstalling the install profile.
    $this->assertSame('testing_install_profile_dependencies', $this->container->getParameter('install_profile'));
    $result = $this->moduleInstaller()->uninstall([$profile]);
    $this->assertTrue($result, 'ModuleInstaller::uninstall() returns TRUE.');
    $this->assertFalse($this->moduleHandler()->moduleExists($profile));
    $this->assertFalse($this->container->getParameter('install_profile'));

    // Try uninstalling the required module again.
    $result = $this->moduleInstaller()->uninstall([$dependency]);
    $this->assertTrue($result, 'ModuleInstaller::uninstall() returns TRUE.');
    $this->assertFalse($this->moduleHandler()->moduleExists($dependency));
  }

  /**
   * Tests that a profile can supply only real dependencies.
   */
  public function testProfileAllDependencies(): void {
    $profile = 'testing_install_profile_all_dependencies';
    $dependencies = ['dblog', 'dependency_foo_test'];
    $this->setInstallProfile($profile);
    // Prime the \Drupal\Core\Extension\ExtensionList::getPathname() static
    // cache with the location of the testing_install_profile_dependencies
    // profile as it is not the currently active profile and we don't yet have
    // any cached way to retrieve its location.
    // @todo Remove as part of https://www.drupal.org/node/2186491
    $profile_list = \Drupal::service('extension.list.profile');
    assert($profile_list instanceof ProfileExtensionList);
    $profile_list->setPathname($profile, 'core/profiles/tests/' . $profile . '/' . $profile . '.info.yml');
    $this->enableModules(['module_test', $profile]);

    $data = \Drupal::service('extension.list.module')->reset()->getList();
    foreach ($dependencies as $dependency) {
      $this->assertArrayHasKey($dependency, $data[$profile]->requires);
    }

    $this->moduleInstaller()->install($dependencies);
    foreach ($dependencies as $dependency) {
      $this->assertTrue($this->moduleHandler()->moduleExists($dependency));
    }

    // Try uninstalling the dependencies.
    $this->expectException(ModuleUninstallValidatorException::class);
    $this->expectExceptionMessage("The following reasons prevent the modules from being uninstalled: The 'Testing install profile all dependencies' install profile requires 'Database Logging'; The 'Testing install profile all dependencies' install profile requires 'Dependency foo test module'");
    $this->moduleInstaller()->uninstall($dependencies);
  }

  /**
   * Tests uninstalling a module that has content.
   */
  public function testUninstallContentDependency(): void {
    $this->enableModules(['module_test', 'entity_test', 'text', 'user', 'help']);
    $this->assertTrue($this->moduleHandler()->moduleExists('entity_test'), 'Test module is enabled.');
    $this->assertTrue($this->moduleHandler()->moduleExists('module_test'), 'Test module is enabled.');

    $this->installSchema('user', 'users_data');
    $entity_types = \Drupal::entityTypeManager()->getDefinitions();
    foreach ($entity_types as $entity_type) {
      if ($entity_type instanceof ContentEntityTypeInterface && 'entity_test' == $entity_type->getProvider()) {
        $this->installEntitySchema($entity_type->id());
      }
    }

    // Create a fake dependency.
    // entity_test will depend on help. This way help can not be uninstalled
    // when there is test content preventing entity_test from being uninstalled.
    \Drupal::state()->set('module_test.dependency', 'dependency');

    // Create an entity so that the modules can not be disabled.
    $entity = EntityTest::create(['name' => $this->randomString()]);
    $entity->save();

    // Uninstalling entity_test is not possible when there is content.
    try {
      $message = 'ModuleInstaller::uninstall() throws ModuleUninstallValidatorException upon uninstalling a module which does not pass validation.';
      $this->moduleInstaller()->uninstall(['entity_test']);
      $this->fail($message);
    }
    catch (ModuleUninstallValidatorException) {
      // Expected exception; just continue testing.
    }

    // Uninstalling help needs entity_test to be un-installable.
    try {
      $message = 'ModuleInstaller::uninstall() throws ModuleUninstallValidatorException upon uninstalling a module which does not pass validation.';
      $this->moduleInstaller()->uninstall(['help']);
      $this->fail($message);
    }
    catch (ModuleUninstallValidatorException) {
      // Expected exception; just continue testing.
    }

    // Deleting the entity.
    $entity->delete();

    /** @var \Drupal\Core\Update\UpdateHookRegistry $update_registry */
    $update_registry = \Drupal::service('update.update_hook_registry');
    $result = $this->moduleInstaller()->uninstall(['help']);
    $this->assertTrue($result, 'ModuleInstaller::uninstall() returns TRUE.');
    $this->assertEquals($update_registry::SCHEMA_UNINSTALLED, $update_registry->getInstalledVersion('entity_test'), "entity_test module was uninstalled.");
  }

  /**
   * Tests whether the correct module metadata is returned.
   */
  public function testModuleMetaData(): void {
    // Generate the list of available modules.
    $modules = $this->container->get('extension.list.module')->getList();
    // Check that the mtime field exists for the system module.
    $this->assertNotEmpty($modules['system']->info['mtime'], 'The system.info.yml file modification time field is present.');
    // Use 0 if mtime isn't present, to avoid an array index notice.
    $test_mtime = !empty($modules['system']->info['mtime']) ? $modules['system']->info['mtime'] : 0;
    // Ensure the mtime field contains a number that is greater than zero.
    $this->assertIsNumeric($test_mtime);
    $this->assertGreaterThan(0, $test_mtime);
  }

  /**
   * Tests whether module-provided stream wrappers are registered properly.
   */
  public function testModuleStreamWrappers(): void {
    // file_test.module provides (among others) a 'dummy' stream wrapper.
    // Verify that it is not registered yet to prevent false positives.
    $stream_wrappers = \Drupal::service('stream_wrapper_manager')->getWrappers();
    $this->assertFalse(isset($stream_wrappers['dummy']));
    $this->moduleInstaller()->install(['file_test']);
    // Verify that the stream wrapper is available even without calling
    // \Drupal::service('stream_wrapper_manager')->getWrappers() again.
    // If the stream wrapper is not available file_exists() will raise a notice.
    file_exists('dummy://');
    $stream_wrappers = \Drupal::service('stream_wrapper_manager')->getWrappers();
    $this->assertTrue(isset($stream_wrappers['dummy']));
    $this->assertTrue(isset($stream_wrappers['dummy1']));
    $this->assertTrue(isset($stream_wrappers['dummy2']));
  }

  /**
   * Tests whether the correct theme metadata is returned.
   */
  public function testThemeMetaData(): void {
    // Generate the list of available themes.
    $themes = \Drupal::service('extension.list.theme')->reset()->getList();
    // Check that the mtime field exists for the olivero theme.
    $this->assertNotEmpty($themes['olivero']->info['mtime'], 'The olivero.info.yml file modification time field is present.');
    // Use 0 if mtime isn't present, to avoid an array index notice.
    $test_mtime = !empty($themes['olivero']->info['mtime']) ? $themes['olivero']->info['mtime'] : 0;
    // Ensure the mtime field contains a number that is greater than zero.
    $this->assertIsNumeric($test_mtime);
    $this->assertGreaterThan(0, $test_mtime);
  }

  /**
   * Tests procedural preprocess functions.
   */
  public function testProceduralPreprocess(): void {
    $this->moduleInstaller()->install(['module_test_procedural_preprocess']);
    $preprocess_function = [];
    $preprocess_invoke = [];
    $prefix = 'module_test_procedural_preprocess';
    $hook = 'test';
    if ($this->moduleHandler()->hasImplementations('preprocess', [$prefix], TRUE)) {
      $function = "{$prefix}_preprocess";
      $preprocess_function[] = $function;
      $preprocess_invoke[$function] = ['module' => $prefix, 'hook' => 'preprocess'];
    }
    if ($this->moduleHandler()->hasImplementations('preprocess_' . $hook, [$prefix], TRUE)) {
      $function = "{$prefix}_preprocess_{$hook}";
      $preprocess_function[] = $function;
      $preprocess_invoke[$function] = ['module' => $prefix, 'hook' => 'preprocess_' . $hook];
    }

    foreach ($preprocess_function as $function) {
      $this->assertTrue($this->moduleHandler()->invoke(... $preprocess_invoke[$function], args: [TRUE]), 'Procedural hook_preprocess runs.');
    }
  }

  /**
   * Tests Oop preprocess functions.
   */
  public function testOopPreprocess(): void {
    $this->moduleInstaller()->install(['module_test_oop_preprocess']);
    $preprocess_function = [];
    $preprocess_invoke = [];
    $prefix = 'module_test_oop_preprocess';
    $hook = 'test';
    if ($this->moduleHandler()->hasImplementations('preprocess', [$prefix], TRUE)) {
      $function = "{$prefix}_preprocess";
      $preprocess_function[] = $function;
      $preprocess_invoke[$function] = ['module' => $prefix, 'hook' => 'preprocess'];
    }
    if ($this->moduleHandler()->hasImplementations('preprocess_' . $hook, [$prefix], TRUE)) {
      $function = "{$prefix}_preprocess_{$hook}";
      $preprocess_function[] = $function;
      $preprocess_invoke[$function] = ['module' => $prefix, 'hook' => 'preprocess_' . $hook];
    }

    foreach ($preprocess_function as $function) {
      $this->assertTrue($this->moduleHandler()->invoke(... $preprocess_invoke[$function], args: [TRUE]), 'Procedural hook_preprocess runs.');
    }
  }

  /**
   * Returns the ModuleHandler.
   *
   * @return \Drupal\Core\Extension\ModuleHandlerInterface
   *   The module handler service.
   */
  protected function moduleHandler() {
    return $this->container->get('module_handler');
  }

  /**
   * Returns the ModuleInstaller.
   *
   * @return \Drupal\Core\Extension\ModuleInstallerInterface
   *   The module installer service.
   */
  protected function moduleInstaller() {
    return $this->container->get('module_installer');
  }

}
