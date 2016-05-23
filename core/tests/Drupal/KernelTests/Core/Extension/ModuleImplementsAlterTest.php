<?php

namespace Drupal\KernelTests\Core\Extension;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests hook_module_implements_alter().
 *
 * @group Module
 */
class ModuleImplementsAlterTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['system'];

  /**
   * Tests hook_module_implements_alter() adding an implementation.
   *
   * @see \Drupal\Core\Extension\ModuleHandler::buildImplementationInfo()
   * @see module_test_module_implements_alter()
   */
  function testModuleImplementsAlter() {

    // Get an instance of the module handler, to observe how it is going to be
    // replaced.
    $module_handler = \Drupal::moduleHandler();

    $this->assertTrue($module_handler === \Drupal::moduleHandler(),
      'Module handler instance is still the same.');

    // Install the module_test module.
    \Drupal::service('module_installer')->install(array('module_test'));

    // Assert that the \Drupal::moduleHandler() instance has been replaced.
    $this->assertFalse($module_handler === \Drupal::moduleHandler(),
      'The \Drupal::moduleHandler() instance has been replaced during \Drupal::moduleHandler()->install().');

    // Assert that module_test.module is now included.
    $this->assertTrue(function_exists('module_test_modules_installed'),
      'The file module_test.module was successfully included.');

    $this->assertTrue(array_key_exists('module_test', \Drupal::moduleHandler()->getModuleList()),
      'module_test is in the module list.');

    $this->assertTrue(in_array('module_test', \Drupal::moduleHandler()->getImplementations('modules_installed')),
      'module_test implements hook_modules_installed().');

    $this->assertTrue(in_array('module_test', \Drupal::moduleHandler()->getImplementations('module_implements_alter')),
      'module_test implements hook_module_implements_alter().');

    // Assert that module_test.implementations.inc is not included yet.
    $this->assertFalse(function_exists('module_test_altered_test_hook'),
      'The file module_test.implementations.inc is not included yet.');

    // Trigger hook discovery for hook_altered_test_hook().
    // Assert that module_test_module_implements_alter(*, 'altered_test_hook')
    // has added an implementation.
    $this->assertTrue(in_array('module_test', \Drupal::moduleHandler()->getImplementations('altered_test_hook')),
      'module_test implements hook_altered_test_hook().');

    // Assert that module_test.implementations.inc was included as part of the process.
    $this->assertTrue(function_exists('module_test_altered_test_hook'),
      'The file module_test.implementations.inc was included.');
  }

  /**
   * Tests what happens if hook_module_implements_alter() adds a non-existing
   * function to the implementations.
   *
   * @see \Drupal\Core\Extension\ModuleHandler::buildImplementationInfo()
   * @see module_test_module_implements_alter()
   */
  function testModuleImplementsAlterNonExistingImplementation() {

    // Install the module_test module.
    \Drupal::service('module_installer')->install(array('module_test'));

    try {
      // Trigger hook discovery.
      \Drupal::moduleHandler()->getImplementations('unimplemented_test_hook');
      $this->fail('An exception should be thrown for the non-existing implementation.');
    }
    catch (\RuntimeException $e) {
      $this->pass('An exception should be thrown for the non-existing implementation.');
      $this->assertEqual($e->getMessage(), 'An invalid implementation module_test_unimplemented_test_hook was added by hook_module_implements_alter()');
    }
  }

}
