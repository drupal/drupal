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
   * Overrides \Drupal\config\ConfigStorageController::save().
   */
  public function save(EntityInterface $entity) {
    // Generate menu-compatible set name.
    if (!$entity->getOriginalID()) {
      // Save a new shortcut set with links copied from the user's default set.
      $default_set = shortcut_default_set();
      // Generate a name to have no collisions with menu.
      // Size of menu_name is 32 so id could be 23 = 32 - strlen('shortcut-').
      $id = substr($entity->id(), 0, 23);
      $entity->set('id', $id);
      $entity->set('links', $default_set->links);
      foreach ($entity->links as $link) {
        $link = $link->createDuplicate();
        $link->menu_name = $id;
        unset($link->mlid);
        $link->save();
      }
    }

    // Just store the UUIDs.
    if (isset($entity->links)) {
      foreach ($entity->links as $uuid => $link) {
        $entity->links[$uuid] = $uuid;
      }
    }

    return parent::save($entity);
  }

  /**
   * Overrides \Drupal\config\ConfigStorageController::postSave().
   */
  function postSave(EntityInterface $entity, $update) {
    // Process links in shortcut set.
    // If links were provided for the set, save them.
    if (isset($entity->links)) {
      foreach ($entity->links as $uuid) {
        $menu_link = entity_load_by_uuid('menu_link', $uuid);
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
