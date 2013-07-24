<?php

/**
 * @file
 * Contains \Drupal\shortcut\ShortcutSetStorageController.
 */

namespace Drupal\shortcut;

use Drupal\Core\Config\Entity\ConfigStorageController;
use Drupal\shortcut\ShortcutSetInterface;

/**
 * Defines a storage controller for shortcut entities.
 */
class ShortcutSetStorageController extends ConfigStorageController implements ShortcutSetStorageControllerInterface {

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
   * {@inheritdoc}
   */
  public function deleteAssignedShortcutSets(ShortcutSetInterface $entity) {
    // First, delete any user assignments for this set, so that each of these
    // users will go back to using whatever default set applies.
    db_delete('shortcut_set_users')
      ->condition('set_name', $entity->id())
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function assignUser(ShortcutSetInterface $shortcut_set, $account) {
    db_merge('shortcut_set_users')
      ->key(array('uid' => $account->id()))
      ->fields(array('set_name' => $shortcut_set->id()))
      ->execute();
    drupal_static_reset('shortcut_current_displayed_set');
  }

  /**
   * {@inheritdoc}
   */
  public function unassignUser($account) {
    $deleted = db_delete('shortcut_set_users')
      ->condition('uid', $account->id())
      ->execute();
    return (bool) $deleted;
  }

  /**
   * {@inheritdoc}
   */
  public function getAssignedToUser($account) {
    $query = db_select('shortcut_set_users', 'ssu');
    $query->fields('ssu', array('set_name'));
    $query->condition('ssu.uid', $account->id());
    return $query->execute()->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function countAssignedUsers(ShortcutSetInterface $shortcut_set) {
    return db_query('SELECT COUNT(*) FROM {shortcut_set_users} WHERE set_name = :name', array(':name' => $shortcut_set->id()))->fetchField();
  }
}
