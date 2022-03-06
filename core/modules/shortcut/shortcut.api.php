<?php

/**
 * @file
 * Hooks provided by the Shortcut module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Return the name of a default shortcut set for the provided user account.
 *
 * This hook allows modules to define default shortcut sets for a particular
 * user that differ from the site-wide default (for example, a module may want
 * to define default shortcuts on a per-role basis).
 *
 * The default shortcut set is used only when the user does not have any other
 * shortcut set explicitly assigned to them.
 *
 * Note that only one default shortcut set can exist per user, so when multiple
 * modules implement this hook, the last (i.e., highest weighted) module which
 * returns a valid shortcut set name will prevail.
 *
 * @param \Drupal\Core\Session\AccountInterface $account
 *   The user account whose default shortcut set is being requested.
 *
 * @return string
 *   The name of the shortcut set that this module recommends for that user, if
 *   there is one.
 */
function hook_shortcut_default_set(\Drupal\Core\Session\AccountInterface $account) {
  // Use a special set of default shortcuts for administrators only.
  $roles = \Drupal::entityTypeManager()->getStorage('user_role')->loadByProperties(['is_admin' => TRUE]);
  $user_admin_roles = array_intersect(array_keys($roles), $account->getRoles());
  if ($user_admin_roles) {
    return 'admin-shortcuts';
  }
}

/**
 * @} End of "addtogroup hooks".
 */
