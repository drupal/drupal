<?php

/**
 * @file
 * Contains \Drupal\Core\Render\ElementInfo.
 */

namespace Drupal\Core\Render;

use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the default element info implementation.
 */
class ElementInfo implements ElementInfoInterface {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   */
  protected $moduleHandler;

  /**
   * Stores the available element information
   *
   * @var array
   */
  protected $elementInfo;

  /**
   * Constructs a new ElementInfo instance.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function getInfo($type) {
    if (!isset($this->elementInfo)) {
      $this->elementInfo = $this->buildInfo();
    }
    return isset($this->elementInfo[$type]) ? $this->elementInfo[$type] : array();
  }

  /**
   * Builds up all element information.
   */
  protected function buildInfo() {
    $info = $this->moduleHandler->invokeAll('element_info');
    foreach ($info as $element_type => $element) {
      $info[$element_type]['#type'] = $element_type;
    }
    // Allow modules to alter the element type defaults.
    $this->moduleHandler->alter('element_info', $info);

    return $info;
  }

}
