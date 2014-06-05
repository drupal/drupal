<?php

/**
 * @file
 * Contains Drupal\system\Tests\DrupalKernel\DrupalKernelTest.
 */

namespace Drupal\system\Tests\DrupalKernel;

use Drupal\Core\DrupalKernel;
use Drupal\Core\Site\Settings;
use Drupal\simpletest\DrupalUnitTestBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests compilation of the DIC.
 */
class DrupalKernelTest extends DrupalUnitTestBase {

  /**
   * @var \Composer\Autoload\ClassLoader
   */
  protected $classloader;

  public static function getInfo() {
    return array(
      'name' => 'DrupalKernel tests',
      'description' => 'Tests DIC compilation to disk.',
      'group' => 'DrupalKernel',
    );
  }

  function setUp() {
    // DrupalKernel relies on global $config_directories and requires those
    // directories to exist. Therefore, create the directories, but do not
    // invoke KernelTestBase::setUp(), since that would set up further
    // environment aspects, which would distort this test, because it tests
    // the DrupalKernel (re-)building itself.
    $this->prepareConfigDirectories();

    $this->settingsSet('php_storage', array('service_container' => array(
      'bin' => 'service_container',
      'class' => 'Drupal\Component\PhpStorage\MTimeProtectedFileStorage',
      'directory' => DRUPAL_ROOT . '/' . $this->public_files_directory . '/php',
      'secret' => drupal_get_hash_salt(),
    )));

    $this->classloader = drupal_classloader();
  }

  /**
   * Build a kernel for testings.
   *
   * Because the bootstrap is in DrupalKernel::boot and that involved loading
   * settings from the filesystem we need to go to extra lengths to build a kernel
   * for testing.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   A request object to use in booting the kernel.
   * @param array $modules_enabled
   *   A list of modules to enable on the kernel.
   * @param bool $read_only
   *   Build the kernel in a read only state.
   * @return DrupalKernel
   */
  protected function getTestKernel(Request $request, array $modules_enabled = NULL, $read_only = FALSE) {
    // Manually create kernel to avoid replacing settings.
    $kernel = DrupalKernel::createFromRequest($request, drupal_classloader(), 'testing');
    $this->settingsSet('hash_salt', $this->databasePrefix);
    if (isset($modules_enabled)) {
      $kernel->updateModules($modules_enabled);
    }
    $kernel->boot();

    if ($read_only) {
      $php_storage = Settings::get('php_storage');
      $php_storage['service_container']['class'] = 'Drupal\Component\PhpStorage\FileReadOnlyStorage';
      $this->settingsSet('php_storage', $php_storage);
    }
    return $kernel;
  }

  /**
   * Tests DIC compilation.
   */
  function testCompileDIC() {
    // @todo: write a memory based storage backend for testing.
    $modules_enabled = array(
      'system' => 'system',
      'user' => 'user',
    );

    $request = Request::createFromGlobals();
    $this->getTestKernel($request, $modules_enabled)
      // Trigger Kernel dump.
      ->getContainer();

    // Instantiate it a second time and we should get the compiled Container
    // class.
    $kernel = $this->getTestKernel($request);
    $container = $kernel->getContainer();
    $refClass = new \ReflectionClass($container);
    $is_compiled_container =
      $refClass->getParentClass()->getName() == 'Drupal\Core\DependencyInjection\Container' &&
      !$refClass->isSubclassOf('Symfony\Component\DependencyInjection\ContainerBuilder');
    $this->assertTrue($is_compiled_container);
    // Verify that the list of modules is the same for the initial and the
    // compiled container.
    $module_list = array_keys($container->get('module_handler')->getModuleList());
    $this->assertEqual(array_values($modules_enabled), $module_list);

    // Now use the read-only storage implementation, simulating a "production"
    // environment.
    $container = $this->getTestKernel($request, NULL, TRUE)
      ->getContainer();

    $refClass = new \ReflectionClass($container);
    $is_compiled_container =
      $refClass->getParentClass()->getName() == 'Drupal\Core\DependencyInjection\Container' &&
      !$refClass->isSubclassOf('Symfony\Component\DependencyInjection\ContainerBuilder');
    $this->assertTrue($is_compiled_container);

    // Verify that the list of modules is the same for the initial and the
    // compiled container.
    $module_list = array_keys($container->get('module_handler')->getModuleList());
    $this->assertEqual(array_values($modules_enabled), $module_list);

    // Test that our synthetic services are there.
    $classloader = $container->get('class_loader');
    $refClass = new \ReflectionClass($classloader);
    $this->assertTrue($refClass->hasMethod('loadClass'), 'Container has a classloader');

    // We make this assertion here purely to show that the new container below
    // is functioning correctly, i.e. we get a brand new ContainerBuilder
    // which has the required new services, after changing the list of enabled
    // modules.
    $this->assertFalse($container->has('service_provider_test_class'));

    // Add another module so that we can test that the new module's bundle is
    // registered to the new container.
    $modules_enabled['service_provider_test'] = 'service_provider_test';
    $this->getTestKernel($request, $modules_enabled, TRUE);

    // Instantiate it a second time and we should still get a ContainerBuilder
    // class because we are using the read-only PHP storage.
    $kernel = $this->getTestKernel($request, $modules_enabled, TRUE);
    $container = $kernel->getContainer();

    $refClass = new \ReflectionClass($container);
    $is_container_builder = $refClass->isSubclassOf('Symfony\Component\DependencyInjection\ContainerBuilder');
    $this->assertTrue($is_container_builder, 'Container is a builder');

    // Assert that the new module's bundle was registered to the new container.
    $this->assertTrue($container->has('service_provider_test_class'), 'Container has test service');

    // Test that our synthetic services are there.
    $classloader = $container->get('class_loader');
    $refClass = new \ReflectionClass($classloader);
    $this->assertTrue($refClass->hasMethod('loadClass'), 'Container has a classloader');

    // Check that the location of the new module is registered.
    $modules = $container->getParameter('container.modules');
    $this->assertEqual($modules['service_provider_test'], array(
      'type' => 'module',
      'pathname' => drupal_get_filename('module', 'service_provider_test'),
      'filename' => NULL,
    ));
  }

}
