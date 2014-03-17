<?php

/**
 * @file
 * Contains Drupal\system\Tests\DrupalKernel\DrupalKernelTest.
 */

namespace Drupal\system\Tests\DrupalKernel;

use Drupal\Core\DrupalKernel;
use Drupal\Component\PhpStorage\MTimeProtectedFastFileStorage;
use Drupal\Component\PhpStorage\FileReadOnlyStorage;
use Drupal\simpletest\DrupalUnitTestBase;

/**
 * Tests compilation of the DIC.
 */
class DrupalKernelTest extends DrupalUnitTestBase {

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
    // invoke DrupalUnitTestBase::setUp(), since that would set up further
    // environment aspects, which would distort this test, because it tests
    // the DrupalKernel (re-)building itself.
    $this->prepareConfigDirectories();

    $this->settingsSet('php_storage', array('service_container' => array(
      'bin' => 'service_container',
      'class' => 'Drupal\Component\PhpStorage\MTimeProtectedFileStorage',
      'directory' => DRUPAL_ROOT . '/' . $this->public_files_directory . '/php',
      'secret' => drupal_get_hash_salt(),
    )));
  }

  /**
   * Tests DIC compilation.
   */
  function testCompileDIC() {
    $classloader = drupal_classloader();
    // @todo: write a memory based storage backend for testing.
    $module_enabled = array(
      'system' => 'system',
      'user' => 'user',
    );
    $kernel = new DrupalKernel('testing', $classloader);
    $kernel->updateModules($module_enabled);
    $kernel->boot();
    // Instantiate it a second time and we should get the compiled Container
    // class.
    $kernel = new DrupalKernel('testing', $classloader);
    $kernel->updateModules($module_enabled);
    $kernel->boot();
    $container = $kernel->getContainer();
    $refClass = new \ReflectionClass($container);
    $is_compiled_container =
      $refClass->getParentClass()->getName() == 'Drupal\Core\DependencyInjection\Container' &&
      !$refClass->isSubclassOf('Symfony\Component\DependencyInjection\ContainerBuilder');
    $this->assertTrue($is_compiled_container);

    // Now use the read-only storage implementation, simulating a "production"
    // environment.
    $php_storage = settings()->get('php_storage');
    $php_storage['service_container']['class'] = 'Drupal\Component\PhpStorage\FileReadOnlyStorage';
    $this->settingsSet('php_storage', $php_storage);
    $kernel = new DrupalKernel('testing', $classloader);
    $kernel->updateModules($module_enabled);
    $kernel->boot();
    $container = $kernel->getContainer();
    $refClass = new \ReflectionClass($container);
    $is_compiled_container =
      $refClass->getParentClass()->getName() == 'Drupal\Core\DependencyInjection\Container' &&
      !$refClass->isSubclassOf('Symfony\Component\DependencyInjection\ContainerBuilder');
    $this->assertTrue($is_compiled_container);
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
    $module_enabled['service_provider_test'] = 'service_provider_test';
    $kernel = new DrupalKernel('testing', $classloader);
    $kernel->updateModules($module_enabled);
    $kernel->boot();
    // Instantiate it a second time and we should still get a ContainerBuilder
    // class because we are using the read-only PHP storage.
    $kernel = new DrupalKernel('testing', $classloader);
    $kernel->updateModules($module_enabled);
    $kernel->boot();
    $container = $kernel->getContainer();
    $refClass = new \ReflectionClass($container);
    $is_container_builder = $refClass->isSubclassOf('Symfony\Component\DependencyInjection\ContainerBuilder');
    $this->assertTrue($is_container_builder);
    // Assert that the new module's bundle was registered to the new container.
    $this->assertTrue($container->has('service_provider_test_class'));
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
