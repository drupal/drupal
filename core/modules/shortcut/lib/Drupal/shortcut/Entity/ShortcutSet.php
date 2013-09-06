<?php

/**
 * @file
 * Contains \Drupal\shortcut\Entity\ShortcutSet.
 */

namespace Drupal\shortcut\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\Annotation\EntityType;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\shortcut\ShortcutSetInterface;

/**
 * Defines the Shortcut configuration entity.
 *
 * @EntityType(
 *   id = "shortcut_set",
 *   label = @Translation("Shortcut set"),
 *   module = "shortcut",
 *   controllers = {
 *     "storage" = "Drupal\shortcut\ShortcutSetStorageController",
 *     "access" = "Drupal\shortcut\ShortcutSetAccessController",
 *     "list" = "Drupal\shortcut\ShortcutSetListController",
 *     "form" = {
 *       "default" = "Drupal\shortcut\ShortcutSetFormController",
 *       "add" = "Drupal\shortcut\ShortcutSetFormController",
 *       "edit" = "Drupal\shortcut\ShortcutSetFormController",
 *       "customize" = "Drupal\shortcut\Form\SetCustomize",
 *       "delete" = "Drupal\shortcut\Form\ShortcutSetDeleteForm"
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
class ShortcutSet extends ConfigEntityBase implements ShortcutSetInterface {

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

  /**
   * {@inheritdoc}
   */
  public function postCreate(EntityStorageControllerInterface $storage_controller) {
    // Generate menu-compatible set name.
    if (!$this->getOriginalID()) {
      // Save a new shortcut set with links copied from the user's default set.
      $default_set = shortcut_default_set();
      // Generate a name to have no collisions with menu.
      // Size of menu_name is 32 so id could be 23 = 32 - strlen('shortcut-').
      $id = substr($this->id(), 0, 23);
      $this->set('id', $id);
      if ($default_set->id() != $id) {
        foreach ($default_set->links as $link) {
          $link = $link->createDuplicate();
          $link->enforceIsNew();
          $link->menu_name = $id;
          $link->save();
          $this->links[$link->uuid()] = $link;
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageControllerInterface $storage_controller) {
    // Just store the UUIDs.
    foreach ($this->links as $uuid => $link) {
      $this->links[$uuid] = $uuid;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageControllerInterface $storage_controller, $update = TRUE) {
    foreach ($this->links as $uuid) {
      if ($menu_link = entity_load_by_uuid('menu_link', $uuid)) {
        // Do not specifically associate these links with the shortcut module,
        // since other modules may make them editable via the menu system.
        // However, we do need to specify the correct menu name.
        $menu_link->menu_name = 'shortcut-' . $this->id();
        $menu_link->plid = 0;
        $menu_link->save();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function preDelete(EntityStorageControllerInterface $storage_controller, array $entities) {
    foreach ($entities as $entity) {
      $storage_controller->deleteAssignedShortcutSets($entity);
      // Next, delete the menu links for this set.
      menu_delete_links('shortcut-' . $entity->id());

    }
  }
}
