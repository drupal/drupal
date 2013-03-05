<?php

/**
 * @file
 * Contains \Drupal\Core\Config\Context\FreeConfigContext.
 */

namespace Drupal\Core\Config\Context;

/**
 * Defines the override-free configuration context object.
 */
class FreeConfigContext extends ConfigContext {

  /**
   * Implements \Drupal\Core\Config\Context\ContextInterface::getOverrides().
   */
  public function getOverrides($config_name) {
    // Do nothing as this is override free.
    return FALSE;
  }

  /**
   * Implements \Drupal\Core\Config\Context\ContextInterface::setOverride().
   */
  public function setOverrides($config_name, $data) {
    // Do nothing as this is override free.
  }

}
