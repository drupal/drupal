<?php

namespace Drupal\module_test\Controller;

use Drupal\module_autoload_test\SomeClass;

/**
 * Controller routines for module_test routes.
 */
class ModuleTestController {

  /**
   * Returns dynamically invoked hook results for the 'module_test' module
   *
   * @return array
   *   Renderable array.
   */
  public function hookDynamicLoadingInvoke() {
    $result = \Drupal::moduleHandler()->invoke('module_test', 'test_hook');
    return $result['module_test'];
  }

  /**
   * Returns dynamically invoked hook results for all modules.
   *
   * @return array
   *   Renderable array.
   */
  public function hookDynamicLoadingInvokeAll() {
    $result = \Drupal::moduleHandler()->invokeAll('test_hook');
    return $result['module_test'];
  }

  /**
   * Returns the result of an autoloaded class's public method.
   *
   * @return array
   *   Renderable array.
   */
  public function testClassLoading() {
    $markup = NULL;
    if (class_exists('Drupal\module_autoload_test\SomeClass')) {
      $obj = new SomeClass();
      $markup = $obj->testMethod();
    }
    return ['#markup' => $markup];
  }

}
