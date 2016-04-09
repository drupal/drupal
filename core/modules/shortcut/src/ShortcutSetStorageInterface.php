<?php

namespace Drupal\shortcut;

use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines an interface for shortcut_set entity storage classes.
 */
interface ShortcutSetStorageInterface extends ConfigEntityStorageInterface {

  /**
   * Assigns a user to a particular shortcut set.
   *
   * @param \Drupal\shortcut\ShortcutSetInterface $shortcut_set
   *   An object representing the shortcut set.
   * @param $account
   *   A user account that will be assigned to use the set.
   */
  public function assignUser(ShortcutSetInterface $shortcut_set, $account);

  /**
   * Unassigns a user from any shortcut set they may have been assigned to.
   *
   * The user will go back to using whatever default set applies.
   *
   * @param $account
   *   A user account that will be removed from the shortcut set assignment.
   *
   * @return bool
   *   TRUE if the user was previously assigned to a shortcut set and has been
   *   successfully removed from it. FALSE if the user was already not assigned
   *   to any set.
   */
  public function unassignUser($account);

  /**
   * Delete shortcut sets assigned to users.
   *
   * @param \Drupal\shortcut\ShortcutSetInterface $entity
   *   Delete the user assigned sets belonging to this shortcut.
   */
  public function deleteAssignedShortcutSets(ShortcutSetInterface $entity);

  /**
   * Get the name of the set assigned to this user.
   *
   * @param \Drupal\user\Entity\User
   *   The user account.
   *
   * @return string
   *   The name of the shortcut set assigned to this user.
   */
  public function getAssignedToUser($account);

  /**
   * Get the number of users who have this set assigned to them.
   *
   * @param \Drupal\shortcut\ShortcutSetInterface $shortcut_set
   *   The shortcut to count the users assigned to.
   *
   * @return int
   *   The number of users who have this set assigned to them.
   */
  public function countAssignedUsers(ShortcutSetInterface $shortcut_set);

  /**
   * Gets the default shortcut set for a given user account.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account whose default shortcut set will be returned.
   *
   * @return \Drupal\shortcut\ShortcutSetInterface
   *   An object representing the default shortcut set.
   */
  public function getDefaultSet(AccountInterface $account);

}
