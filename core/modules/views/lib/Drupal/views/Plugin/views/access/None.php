<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\access\None.
 */

namespace Drupal\views\Plugin\views\access;

use Drupal\Core\Annotation\Translation;
use Drupal\Component\Annotation\Plugin;

/**
 * Access plugin that provides no access control at all.
 *
 * @ingroup views_access_plugins
 *
 * @Plugin(
 *   id = "none",
 *   title = @Translation("None"),
 *   help = @Translation("Will be available to all users.")
 * )
 */
class None extends AccessPluginBase {

  public function summaryTitle() {
    return t('Unrestricted');
  }

  /**
   * Implements Drupal\views\Plugin\views\access\AccessPluginBase::access().
   */
  public function access($account) {
    // No access control.
    return TRUE;
  }

  /**
   * Implements Drupal\views\Plugin\views\access\AccessPluginBase::get_access_callback().
   */
  public function get_access_callback() {
    // No access control.
    return TRUE;
  }

}
