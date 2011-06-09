<?php

/**
 * @file Controller class for users.
 */

/**
 * Controller class for users.
 *
 * This extends the DrupalDefaultEntityController class, adding required
 * special handling for user objects.
 */
class UserController extends DrupalDefaultEntityController {

  function attachLoad(&$queried_users, $revision_id = FALSE) {
    // Build an array of user picture IDs so that these can be fetched later.
    $picture_fids = array();
    foreach ($queried_users as $key => $record) {
      $picture_fids[] = $record->picture;
      $queried_users[$key]->data = unserialize($record->data);
      $queried_users[$key]->roles = array();
      if ($record->uid) {
        $queried_users[$record->uid]->roles[DRUPAL_AUTHENTICATED_RID] = 'authenticated user';
      }
      else {
        $queried_users[$record->uid]->roles[DRUPAL_ANONYMOUS_RID] = 'anonymous user';
      }
    }

    // Add any additional roles from the database.
    $result = db_query('SELECT r.rid, r.name, ur.uid FROM {role} r INNER JOIN {users_roles} ur ON ur.rid = r.rid WHERE ur.uid IN (:uids)', array(':uids' => array_keys($queried_users)));
    foreach ($result as $record) {
      $queried_users[$record->uid]->roles[$record->rid] = $record->name;
    }

    // Add the full file objects for user pictures if enabled.
    if (!empty($picture_fids) && variable_get('user_pictures', 1) == 1) {
      $pictures = file_load_multiple($picture_fids);
      foreach ($queried_users as $account) {
        if (!empty($account->picture) && isset($pictures[$account->picture])) {
          $account->picture = $pictures[$account->picture];
        }
        else {
          $account->picture = NULL;
        }
      }
    }
    // Call the default attachLoad() method. This will add fields and call
    // hook_user_load().
    parent::attachLoad($queried_users, $revision_id);
  }
}
