<?php

/**
 * @file
 * Contains \Drupal\shortcut\ShortcutStorageController.
 */

namespace Drupal\shortcut;

use Drupal\Core\Config\Entity\ConfigStorageController;
use Drupal\Core\Entity\EntityInterface;

/**
 * Defines a storage controller for shortcut entities.
 */
class ShortcutStorageController extends ConfigStorageController {

  /**
   * Overrides \Drupal\config\ConfigStorageController::attachLoad().
   */
  protected function attachLoad(&$queried_entities, $revision_id = FALSE) {
    parent::attachLoad($queried_entities, $revision_id);

    foreach ($queried_entities as $id => $entity) {
      $links = menu_load_links('shortcut-' . $id);
      foreach ($links as $menu_link) {
        $entity->links[$menu_link->uuid()] = $menu_link;
      }
    }
  }

  /**
   * Overrides \Drupal\config\ConfigStorageController::create().
   */
  public function create(array $values) {
    $entity = parent::create($values);

    // Generate menu-compatible set name.
    if (!$entity->getOriginalID()) {
      // Save a new shortcut set with links copied from the user's default set.
      $default_set = shortcut_default_set();
      // Generate a name to have no collisions with menu.
      // Size of menu_name is 32 so id could be 23 = 32 - strlen('shortcut-').
      $id = substr($entity->id(), 0, 23);
      $entity->set('id', $id);
      if ($default_set->id() != $id) {
        foreach ($default_set->links as $link) {
          $link = $link->createDuplicate();
          $link->enforceIsNew();
          $link->menu_name = $id;
          $link->save();
          $entity->links[$link->uuid()] = $link;
        }
      }
    }

    return $entity;
  }

  /**
   * Overrides \Drupal\config\ConfigStorageController::preSave().
   */
  public function preSave(EntityInterface $entity) {
    // Just store the UUIDs.
    foreach ($entity->links as $uuid => $link) {
      $entity->links[$uuid] = $uuid;
    }

    parent::preSave($entity);
  }

  /**
   * Overrides \Drupal\config\ConfigStorageController::postSave().
   */
  function postSave(EntityInterface $entity, $update) {
    // Process links in shortcut set.
    foreach ($entity->links as $uuid) {
      if ($menu_link = entity_load_by_uuid('menu_link', $uuid)) {
        // Do not specifically associate these links with the shortcut module,
        // since other modules may make them editable via the menu system.
        // However, we do need to specify the correct menu name.
        $menu_link->menu_name = 'shortcut-' . $entity->id();
        $menu_link->plid = 0;
        $menu_link->save();
      }
    }

    parent::postSave($entity, $update);
  }

  /**
   * Overrides \Drupal\Core\Entity\ConfigStorageController::preDelete().
   */
  protected function preDelete($entities) {
    foreach ($entities as $entity) {
      // First, delete any user assignments for this set, so that each of these
      // users will go back to using whatever default set applies.
      db_delete('shortcut_set_users')
        ->condition('set_name', $entity->id())
        ->execute();

      // Next, delete the menu links for this set.
      menu_delete_links('shortcut-' . $entity->id());
    }

    parent::preDelete($entities);
  }

}
