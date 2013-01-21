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
      $entity->set('links', menu_links_clone($default_set->links, $id));
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
      foreach ($entity->links as &$link) {
        // Do not specifically associate these links with the shortcut module,
        // since other modules may make them editable via the menu system.
        // However, we do need to specify the correct menu name.
        $link['menu_name'] = 'shortcut-' . $entity->id();
        $link['plid'] = 0;
        menu_link_save($link);
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
