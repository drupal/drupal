<?php

/**
 * @file
 * Contains \Drupal\module_test\Controller\ModuleTestController.
 */

namespace Drupal\module_test\Controller;

/**
 * Controller routines for module_test routes.
 */
class ModuleTestController {

  /**
   * @todo Remove module_test_hook_dynamic_loading_invoke().
   */
  public function hookDynamicLoadingInvoke() {
    return module_test_hook_dynamic_loading_invoke();
  }

  /**
   * @todo Remove module_test_hook_dynamic_loading_invoke_all().
   */
  public function hookDynamicLoadingInvokeAll() {
    return module_test_hook_dynamic_loading_invoke_all();
  }

  /**
   * @todo Remove module_test_class_loading().
   */
  public function testClassLoading() {
    return ['#markup' => module_test_class_loading()];
  }

}
