<?php

namespace Drupal\container_rebuild_test;

use Drupal\Core\Controller\ControllerBase;

class TestController extends ControllerBase {

  /**
   * Displays the path to a module.
   *
   * @param string $module
   *   The module name.
   * @param string $function
   *   The function to check if it exists.
   *
   * @return string[]
   *   A render array.
   */
  public function showModuleInfo(string $module, string $function) {
    $module_handler = \Drupal::moduleHandler();
    $module_message = $module . ': ';
    if ($module_handler->moduleExists($module)) {
      $module_message .= \Drupal::moduleHandler()->getModule($module)->getPath();
    }
    else {
      $module_message .= 'not installed';
    }
    $function_message = $function . ': ' . var_export(function_exists($function), TRUE);

    return [
      '#theme' => 'item_list',
      '#items' => [$module_message, $function_message],
    ];
  }

}
