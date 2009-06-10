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
 * @param $form_values
 *   Passes in an array of form values submitted by the user.
 * @return
 *   Nothing.
 */
function hook_comment_insert($form_values) {
  // Reindex the node when comments are added.
  search_touch_node($form_values['nid']);
}

/**
 *  The user has just finished editing the comment and is trying to
 *  preview or submit it. This hook can be used to check or
 *  even modify the comment. Errors should be set with form_set_error().
 *
 * @param $form_values
 *   Passes in an array of form values submitted by the user.
 * @return
 *   Nothing.
 */
function hook_comment_validate(&$form_values) {
  // if the subject is the same as the comment.
  if ($form_values['subject'] == $form_values['comment']) {
    form_set_error('comment', t('you should write more text than in the subject'));
  }
}

/**
 * The comment is being updated.
 *
 * @param $form_values
 *   Passes in an array of form values submitted by the user.
 * @return
 *   Nothing.
 */
function hook_comment_update($form_values) {
  // Reindex the node when comments are updated.
  search_touch_node($form_values['nid']);
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
