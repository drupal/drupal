<?php

/**
 * @file
 * Contains Drupal\system\Tests\DrupalKernel\DrupalKernelTest.
 */

namespace Drupal\system\Tests\DrupalKernel;

use Drupal\Core\DrupalKernel;
use Drupal\Component\PhpStorage\MTimeProtectedFastFileStorage;
use Drupal\Component\PhpStorage\FileReadOnlyStorage;
use Drupal\simpletest\UnitTestBase;
use ReflectionClass;

/**
 * Tests compilation of the DIC.
 */
class DrupalKernelTest extends UnitTestBase {

  public static function getInfo() {
    return array(
      'name' => 'DrupalKernel tests',
      'description' => 'Tests DIC compilation to disk.',
      'group' => 'DrupalKernel',
    );
  }

  /**
   * Tests DIC compilation.
   */
  function testCompileDIC() {
    // Because we'll be instantiating a new kernel during this test, the
    // container stored in drupal_container() will be updated as a side effect.
    // We need to be able to restore it to the correct one at the end of this
    // test.
    $original_container = drupal_container();
    $classloader = drupal_classloader();
    global $conf;
    $conf['php_storage']['service_container']= array(
      'bin' => 'service_container',
      'class' => 'Drupal\Component\PhpStorage\MTimeProtectedFileStorage',
      'directory' => DRUPAL_ROOT . '/' . variable_get('file_public_path', conf_path() . '/files') . '/php',
      'secret' => $GLOBALS['drupal_hash_salt'],
    );
    // @todo: write a memory based storage backend for testing.
    $module_enabled = array(
      'system' => 'system',
      'user' => 'user',
    );
    $kernel = new DrupalKernel('testing', FALSE, $classloader);
    $kernel->updateModules($module_enabled);
    $kernel->boot();
    // Instantiate it a second time and we should get the compiled Container
    // class.
    $kernel = new DrupalKernel('testing', FALSE, $classloader);
    $kernel->updateModules($module_enabled);
    $kernel->boot();
    $container = $kernel->getContainer();
    $refClass = new ReflectionClass($container);
    $is_compiled_container =
      $refClass->getParentClass()->getName() == 'Symfony\Component\DependencyInjection\Container' &&
      !$refClass->isSubclassOf('Symfony\Component\DependencyInjection\ContainerBuilder');
    $this->assertTrue($is_compiled_container);

    // Reset the container.
    drupal_container(NULL, TRUE);

    // Now use the read-only storage implementation, simulating a "production"
    // environment.
    $conf['php_storage']['service_container']['class'] = 'Drupal\Component\PhpStorage\FileReadOnlyStorage';
    $kernel = new DrupalKernel('testing', FALSE, $classloader);
    $kernel->updateModules($module_enabled);
    $kernel->boot();
    $container = $kernel->getContainer();
    $refClass = new ReflectionClass($container);
    $is_compiled_container =
      $refClass->getParentClass()->getName() == 'Symfony\Component\DependencyInjection\Container' &&
      !$refClass->isSubclassOf('Symfony\Component\DependencyInjection\ContainerBuilder');
    $this->assertTrue($is_compiled_container);
    // Test that our synthetic services are there.
    $classloader = $container->get('class_loader');
    $refClass = new ReflectionClass($classloader);
    $this->assertTrue($refClass->hasMethod('getNamespaces'), 'Container has a classloader');

    // We make this assertion here purely to show that the new container below
    // is functioning correctly, i.e. we get a brand new ContainerBuilder
    // which has the required new services, after changing the list of enabled
    // modules.
    $this->assertFalse($container->has('bundle_test_class'));

    // Reset the container.
    drupal_container(NULL, TRUE);

    // Add another module so that we can test that the new module's bundle is
    // registered to the new container.
    $module_enabled['bundle_test'] = 'bundle_test';
    $kernel = new DrupalKernel('testing', FALSE, $classloader);
    $kernel->updateModules($module_enabled);
    $kernel->boot();
    // Instantiate it a second time and we should still get a ContainerBuilder
    // class because we are using the read-only PHP storage.
    $kernel = new DrupalKernel('testing', FALSE, $classloader);
    $kernel->updateModules($module_enabled);
    $kernel->boot();
    $container = $kernel->getContainer();
    $refClass = new ReflectionClass($container);
    $is_container_builder = $refClass->isSubclassOf('Symfony\Component\DependencyInjection\ContainerBuilder');
    $this->assertTrue($is_container_builder);
    // Assert that the new module's bundle was registered to the new container.
    $this->assertTrue($container->has('bundle_test_class'));
    // Test that our synthetic services are there.
    $classloader = $container->get('class_loader');
    $refClass = new ReflectionClass($classloader);
    $this->assertTrue($refClass->hasMethod('getNamespaces'), 'Container has a classloader');

    // Restore the original container.
    drupal_container($original_container);
  }
}
