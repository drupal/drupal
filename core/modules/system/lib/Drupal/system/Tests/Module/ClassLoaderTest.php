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
  protected $profile = 'testing';

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
      $this->assertText($expected, t('Autoloader loads classes from an enabled module.'));
    }

    module_disable(array('module_autoload_test'), FALSE);
    $this->resetAll();
    // Check twice to test an unprimed and primed system_list() cache.
    for ($i=0; $i<2; $i++) {
      $this->drupalGet('module-test/class-loading');
      $this->assertNoText($expected, t('Autoloader does not load classes from a disabled module.'));
    }
  }
}
