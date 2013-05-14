<?php

/**
 * @file
 * Contains \Drupal\filter\Plugin\Core\Entity\FilterFormatInterface.
 */

namespace Drupal\filter;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a filter format entity.
 */
interface FilterFormatInterface extends ConfigEntityInterface {

  /**
   * Helper callback for uasort() to sort filters by status, weight, module, and name.
   *
   * @see Drupal\filter\FilterFormatStorageController::preSave()
   * @see filter_list_format()
   */
  public static function sortFilters($a, $b);

}
