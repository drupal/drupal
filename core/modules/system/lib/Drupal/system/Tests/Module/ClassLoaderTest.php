<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Module\ClassLoaderTest.
 */

namespace Drupal\system\Tests\Module;

use Drupal\simpletest\WebTestBase;

/**
 * Tests class loading.
 */
class ClassLoaderTest extends WebTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Module class loader',
      'description' => 'Tests class loading for modules.',
      'group' => 'Module',
    );
  }

  /**
   * Tests that module-provided classes can be loaded when a module is enabled.
   */
  function testClassLoading() {
    $expected = 'Drupal\\module_autoload_test\\SomeClass::testMethod() was invoked.';

    module_enable(array('module_test', 'module_autoload_test'), FALSE);
    $this->resetAll();
    // Check twice to test an unprimed and primed system_list() cache.
    for ($i=0; $i<2; $i++) {
      $this->drupalGet('module-test/class-loading');
      $this->assertText($expected, 'Autoloader loads classes from an enabled module.');
    }

    module_disable(array('module_autoload_test'), FALSE);
    $this->resetAll();
    // The first request after a module has been disabled will result in that
    // module's namespace getting registered because the kernel registers all
    // namespaces in the existing 'container.modules' parameter before checking
    // whether the list of modules has changed and rebuilding the container.
    // @todo Fix the behavior so that the namespace is not registered even on the
    //   first request after disabling the module and revert this test to having
    //   the assertion inside the loop. See http://drupal.org/node/1846376
    for ($i=0; $i<2; $i++) {
      $this->drupalGet('module-test/class-loading');
    }
    $this->assertNoText($expected, 'Autoloader does not load classes from a disabled module.');
  }
}
