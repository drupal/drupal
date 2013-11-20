<?php

/**
 * @file
 * Contains \Drupal\migrate\Plugin\RequirementsInterface.
 */

namespace Drupal\migrate\Plugin;

/**
 * An interface to check for a migrate plugin requirements.
 */
interface RequirementsInterface {

  /**
   * Checks if requirements for this plugin are OK.
   *
   * @return boolean
   *   TRUE if it is possible to use the plugin, FALSE if not.
   */
  public function checkRequirements();

}
