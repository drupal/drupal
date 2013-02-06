<?php

/**
 * @file
 * Contains \Drupal\filter\Plugin\Core\Entity\FilterFormat.
 */

namespace Drupal\filter\Plugin\Core\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Represents a text format.
 *
 * @Plugin(
 *   id = "filter_format",
 *   label = @Translation("Text format"),
 *   module = "filter",
 *   controller_class = "Drupal\filter\FilterFormatStorageController",
 *   config_prefix = "filter.format",
 *   entity_keys = {
 *     "id" = "format",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "status" = "status"
 *   }
 * )
 */
class FilterFormat extends ConfigEntityBase {

  /**
   * Unique machine name of the format.
   *
   * @todo Rename to $id.
   *
   * @var string
   */
  public $format;

  /**
   * Unique label of the text format.
   *
   * Since text formats impact a site's security, two formats with the same
   * label but different filter configuration would impose a security risk.
   * Therefore, each text format label must be unique.
   *
   * @todo Rename to $label.
   *
   * @var string
   */
  public $name;

  /**
   * The UUID for this entity.
   *
   * @var string
   */
  public $uuid;

  /**
   * Weight of this format in the text format selector.
   *
   * The first/lowest text format that is accessible for a user is used as
   * default format.
   *
   * @var int
   */
  public $weight = 0;

  /**
   * List of user role IDs to grant access to use this format on initial creation.
   *
   * This property is always empty and unused for existing text formats.
   *
   * Default configuration objects of modules and installation profiles are
   * allowed to specify a list of user role IDs to grant access to.
   *
   * This property only has an effect when a new text format is created and the
   * list is not empty. By default, no user role is allowed to use a new format.
   *
   * @var array
   */
  protected $roles;

  /**
   * Whether processed text of this format can be cached.
   *
   * @var bool
   */
  public $cache = 0;

  /**
   * Configured filters for this text format.
   *
   * An associative array of filters assigned to the text format, keyed by the
   * ID of each filter (prefixed with module name) and using the properties:
   * - module: The name of the module providing the filter.
   * - status: (optional) A Boolean indicating whether the filter is
   *   enabled in the text format. Defaults to disabled.
   * - weight: (optional) The weight of the filter in the text format. If
   *   omitted, the default value is determined in the following order:
   *   - if any, the currently stored weight is retained.
   *   - if any, the default weight from hook_filter_info() is taken over.
   *   - otherwise, a default weight of 10, which usually sorts it last.
   * - settings: (optional) An array of configured settings for the filter.
   *   See hook_filter_info() for details.
   *
   * @var array
   */
  public $filters = array();

  /**
   * Overrides \Drupal\Core\Entity\Entity::id().
   */
  public function id() {
    return $this->format;
  }

  /**
   * Helper callback for uasort() to sort filters by status, weight, module, and name.
   *
   * @see Drupal\filter\FilterFormatStorageController::preSave()
   * @see filter_list_format()
   */
  public static function sortFilters($a, $b) {
    if ($a['status'] != $b['status']) {
      return !empty($a['status']) ? -1 : 1;
    }
    if ($a['weight'] != $b['weight']) {
      return ($a['weight'] < $b['weight']) ? -1 : 1;
    }
    if ($a['module'] != $b['module']) {
      return strnatcasecmp($a['module'], $b['module']);
    }
    return strnatcasecmp($a['name'], $b['name']);
  }

}
