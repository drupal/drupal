<?php

/**
 * @file
 * Definition of Drupal\views\TempStore\UserTempStore.
 */

namespace Drupal\views\TempStore;

/**
 * Defines a TempStore using either the user or the session as the owner ID.
 */
class UserTempStore extends TempStore {

  /**
   * Overrides TempStore::__construct().
   *
   * The $owner_id is given a default value of NULL.
   */
  function __construct($subsystem, $owner_id = NULL) {
    if (!isset($owner_id)) {
      // If the user is anonymous, fall back to the session ID.
      $owner_id = user_is_logged_in() ? $GLOBALS['user']->uid : session_id();
    }

    parent::__construct($subsystem, $owner_id);
  }

  /**
   * Overrides TempStore::set().
   */
  function set($key, $data) {
    // Ensure that a session cookie is set for anonymous users.
    if (!user_is_logged_in()) {
      // A session is written so long as $_SESSION is not empty. Force this.
      // @todo This feels really hacky. Is there a better way?
      // @see http://drupalcode.org/project/ctools.git/blob/refs/heads/8.x-1.x:/includes/object-cache.inc#l69
      $_SESSION['temp_store_use_session'] = TRUE;
    }

    parent::set($key, $data);
  }

}
