<?php

/**
 * @file
 * Contains \Drupal\shortcut\Plugin\Core\Entity\Shortcut.
 */

namespace Drupal\shortcut\Plugin\Core\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\Annotation\EntityType;
use Drupal\Core\Annotation\Translation;
use Drupal\shortcut\ShortcutInterface;

/**
 * Defines the Shortcut configuration entity.
 *
 * @EntityType(
 *   id = "shortcut",
 *   label = @Translation("Shortcut set"),
 *   module = "shortcut",
 *   controllers = {
 *     "storage" = "Drupal\shortcut\ShortcutStorageController",
 *     "access" = "Drupal\shortcut\ShortcutAccessController",
 *     "list" = "Drupal\shortcut\ShortcutListController",
 *     "form" = {
 *       "default" = "Drupal\shortcut\ShortcutFormController"
 *     }
 *   },
 *   config_prefix = "shortcut.set",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   }
 * )
 */
class Shortcut extends ConfigEntityBase implements ShortcutInterface {

  /**
   * The machine name for the configuration entity.
   *
   * @var string
   */
  public $id;

  /**
   * The UUID for the configuration entity.
   *
   * @var string
   */
  public $uuid;

  /**
   * The human-readable name of the configuration entity.
   *
   * @var string
   */
  public $label;

  /**
   * An array of menu links.
   *
   * @var array
   */
  public $links = array();

  /**
   * Overrides \Drupal\Core\Entity\Entity::uri().
   */
  public function uri() {
    return array(
      'path' => 'admin/config/user-interface/shortcut/manage/' . $this->id(),
      'options' => array(
        'entity_type' => $this->entityType,
        'entity' => $this,
      ),
    );
  }

}
