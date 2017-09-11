<?php

namespace Drupal\KernelTests\Core\DrupalKernel;

use Drupal\Core\DrupalKernel;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests DIC compilation to disk.
 *
 * @group DrupalKernel
 */
class DrupalKernelTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    // DrupalKernel relies on global $config_directories and requires those
    // directories to exist. Therefore, create the directories, but do not
    // invoke KernelTestBase::setUp(), since that would set up further
    // environment aspects, which would distort this test, because it tests
    // the DrupalKernel (re-)building itself.
    $this->root = static::getDrupalRoot();
    $this->bootEnvironment();
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
   *
   * @return \Drupal\Core\DrupalKernel
   *   New kernel for testing.
   */
  protected function getTestKernel(Request $request, array $modules_enabled = NULL) {
    // Manually create kernel to avoid replacing settings.
    $class_loader = require $this->root . '/autoload.php';
    $kernel = DrupalKernel::createFromRequest($request, $class_loader, 'testing');
    $this->setSetting('container_yamls', []);
    $this->setSetting('hash_salt', $this->databasePrefix);
    if (isset($modules_enabled)) {
      $kernel->updateModules($modules_enabled);
    }
    $kernel->boot();

    return $kernel;
  }

  /**
   * Tests DIC compilation.
   */
  public function testCompileDIC() {
    // @todo: write a memory based storage backend for testing.
    $modules_enabled = [
      'system' => 'system',
      'user' => 'user',
    ];

    $request = Request::createFromGlobals();
    $this->getTestKernel($request, $modules_enabled);

    // Instantiate it a second time and we should get the compiled Container
    // class.
    $kernel = $this->getTestKernel($request);
    $container = $kernel->getContainer();
    $refClass = new \ReflectionClass($container);
    $is_compiled_container = !$refClass->isSubclassOf('Symfony\Component\DependencyInjection\ContainerBuilder');
    $this->assertTrue($is_compiled_container);
    // Verify that the list of modules is the same for the initial and the
    // compiled container.
    $module_list = array_keys($container->get('module_handler')->getModuleList());
    $this->assertEqual(array_values($modules_enabled), $module_list);

    // Get the container another time, simulating a "production" environment.
    $container = $this->getTestKernel($request, NULL)
      ->getContainer();

    $refClass = new \ReflectionClass($container);
    $is_compiled_container = !$refClass->isSubclassOf('Symfony\Component\DependencyInjection\ContainerBuilder');
    $this->assertTrue($is_compiled_container);

    // Verify that the list of modules is the same for the initial and the
    // compiled container.
    $module_list = array_keys($container->get('module_handler')->getModuleList());
    $this->assertEqual(array_values($modules_enabled), $module_list);

    // Test that our synthetic services are there.
    $class_loader = $container->get('class_loader');
    $refClass = new \ReflectionClass($class_loader);
    $this->assertTrue($refClass->hasMethod('loadClass'), 'Container has a class loader');

    // We make this assertion here purely to show that the new container below
    // is functioning correctly, i.e. we get a brand new ContainerBuilder
    // which has the required new services, after changing the list of enabled
    // modules.
    $this->assertFalse($container->has('service_provider_test_class'));

    // Add another module so that we can test that the new module's bundle is
    // registered to the new container.
    $modules_enabled['service_provider_test'] = 'service_provider_test';
    $this->getTestKernel($request, $modules_enabled);

    // Instantiate it a second time and we should not get a ContainerBuilder
    // class because we are loading the container definition from cache.
    $kernel = $this->getTestKernel($request, $modules_enabled);
    $container = $kernel->getContainer();

    $refClass = new \ReflectionClass($container);
    $is_container_builder = $refClass->isSubclassOf('Symfony\Component\DependencyInjection\ContainerBuilder');
    $this->assertFalse($is_container_builder, 'Container is not a builder');

    // Assert that the new module's bundle was registered to the new container.
    $this->assertTrue($container->has('service_provider_test_class'), 'Container has test service');

    // Test that our synthetic services are there.
    $class_loader = $container->get('class_loader');
    $refClass = new \ReflectionClass($class_loader);
    $this->assertTrue($refClass->hasMethod('loadClass'), 'Container has a class loader');

    // Check that the location of the new module is registered.
    $modules = $container->getParameter('container.modules');
    $this->assertEqual($modules['service_provider_test'], [
      'type' => 'module',
      'pathname' => drupal_get_filename('module', 'service_provider_test'),
      'filename' => NULL,
    ]);

    // Check that the container itself is not among the persist IDs because it
    // does not make sense to persist the container itself.
    $persist_ids = $container->getParameter('persist_ids');
    $this->assertSame(FALSE, array_search('service_container', $persist_ids));
  }

  /**
   * Tests repeated loading of compiled DIC with different environment.
   */
  public function testRepeatedBootWithDifferentEnvironment() {
    $request = Request::createFromGlobals();
    $class_loader = require $this->root . '/autoload.php';

    $environments = [
      'testing1',
      'testing1',
      'testing2',
      'testing2',
    ];

    foreach ($environments as $environment) {
      $kernel = DrupalKernel::createFromRequest($request, $class_loader, $environment);
      $this->setSetting('container_yamls', []);
      $this->setSetting('hash_salt', $this->databasePrefix);
      $kernel->boot();
    }

    $this->pass('Repeatedly loaded compiled DIC with different environment');
  }

  /**
   * Tests setting of site path after kernel boot.
   */
  public function testPreventChangeOfSitePath() {
    // @todo: write a memory based storage backend for testing.
    $modules_enabled = [
      'system' => 'system',
      'user' => 'user',
    ];

    $request = Request::createFromGlobals();
    $kernel = $this->getTestKernel($request, $modules_enabled);
    $pass = FALSE;
    try {
      $kernel->setSitePath('/dev/null');
    }
    catch (\LogicException $e) {
      $pass = TRUE;
    }
    $this->assertTrue($pass, 'Throws LogicException if DrupalKernel::setSitePath() is called after boot');

    // Ensure no LogicException if DrupalKernel::setSitePath() is called with
    // identical path after boot.
    $path = $kernel->getSitePath();
    $kernel->setSitePath($path);
  }

}
