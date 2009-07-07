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
 * The comment is being inserted.
 *
 * @param $comment
 *   The comment object.
 */
function hook_comment_insert($comment) {
  // Reindex the node when comments are added.
  search_touch_node($comment->nid);
}

/**
 * The comment is being updated.
 *
 * @param $comment
 *   The comment object.
 */
function hook_comment_update($comment) {
  // Reindex the node when comments are updated.
  search_touch_node($comment->nid);
}

/**
 * The comment is being viewed. This hook can be used to add additional data to the comment before theming.
 *
 * @param $comment
 *   Passes in the comment the action is being performed on.
 * @return
 *   Nothing.
 */
function hook_comment_view($comment) {
  // how old is the comment
  $comment->time_ago = time() - $comment->timestamp;
}

/**
 * The comment is being published by the moderator.
 *
 * @param $comment
 *   Passes in the comment the action is being performed on.
 * @return
 *   Nothing.
 */
function hook_comment_publish($comment) {
  drupal_set_message(t('Comment: @subject has been published', array('@subject' => $comment->subject)));
}

/**
 * The comment is being unpublished by the moderator.
 *
 * @param $comment
 *   Passes in the comment the action is being performed on.
 * @return
 *   Nothing.
 */
function hook_comment_unpublish($comment) {
  drupal_set_message(t('Comment: @subject has been unpublished', array('@subject' => $comment->subject)));
}

/**
 * The comment is being deleted by the moderator.
 *
 * @param $comment
 *   Passes in the comment the action is being performed on.
 * @return
 *   Nothing.
 */
function hook_comment_delete($comment) {
  drupal_set_message(t('Comment: @subject has been deleted', array('@subject' => $comment->subject)));
}

/**
 * @} End of "addtogroup hooks".
 */
