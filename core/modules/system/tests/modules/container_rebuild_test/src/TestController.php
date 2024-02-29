<?php

namespace Drupal\container_rebuild_test;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DrupalKernelInterface;

class TestController extends ControllerBase {

  /**
   * Constructs a TestController.
   *
   * @param \Drupal\Core\DrupalKernelInterface $kernel
   *   The Drupal kernel.
   */
  public function __construct(protected DrupalKernelInterface $kernel) {
  }

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

  /**
   * Resets the container.
   *
   * @return array
   *   A render array.
   */
  public function containerReset() {
    $this->messenger()->addMessage(t('Before the container was reset.'));
    $this->kernel->resetContainer();
    // The container has been reset, therefore we need to get the new service.
    $this->messenger = NULL;
    $this->messenger()->addMessage(t('After the container was reset.'));
    return [];
  }

}
