<?php

/**
 * @file
 * Contains \Drupal\Core\Config\Context\GlobalConfigContext.
 */

namespace Drupal\Core\Config\Context;

/**
 * Defines the global configuration context object.
 *
 * The global configuration context allows config object data to be overridden
 * with values from the $conf global.
 */
class GlobalConfigContext extends ConfigContext {

  /**
   * Sets global override data.
   *
   * @return \Drupal\Core\Config\Context\ConfigContext
   *   The config context object.
   */
  public function setGlobalOverride() {
    global $conf;
    $this->init(self::OVERRIDE, $conf);
    return $this;
  }
}
