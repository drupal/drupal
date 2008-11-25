<?php
// $Id$

/**
 * @file
 * Hooks provided by the Comment module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Act on comments.
 *
 * This hook allows modules to extend the comments system.
 *
 * @param $a1
 *   Dependent on the action being performed.
 *   - For "validate","update","insert", passes in an array of form values submitted by the user.
 *   - For all other operations, passes in the comment the action is being performed on.
 * @param $op
 *   What kind of action is being performed. Possible values:
 *   - "insert": The comment is being inserted.
 *   - "update": The comment is being updated.
 *   - "view": The comment is being viewed. This hook can be used to add additional data to the comment before theming.
 *   - "validate": The user has just finished editing the comment and is
 *     trying to preview or submit it. This hook can be used to check or
 *     even modify the node. Errors should be set with form_set_error().
 *   - "publish": The comment is being published by the moderator.
 *   - "unpublish": The comment is being unpublished by the moderator.
 *   - "delete": The comment is being deleted by the moderator.
 * @return
 *   Dependent on the action being performed.
 *   - For all other operations, nothing.
 */
function hook_comment(&$a1, $op) {
  if ($op == 'insert' || $op == 'update') {
    $nid = $a1['nid'];
  }

  cache_clear_all_like(drupal_url(array('id' => $nid)));
}

/**
 * @} End of "addtogroup hooks".
 */
