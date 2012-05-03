<?php

/**
 * @file
 * Hooks provided by the Comment module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Act on a comment being inserted or updated.
 *
 * This hook is invoked from comment_save() before the comment is saved to the
 * database.
 *
 * @param Drupal\comment\Comment $comment
 *   The comment object.
 */
function hook_comment_presave(Drupal\comment\Comment $comment) {
  // Remove leading & trailing spaces from the comment subject.
  $comment->subject = trim($comment->subject);
}

/**
 * Respond to creation of a new comment.
 *
 * @param Drupal\comment\Comment $comment
 *   The comment object.
 */
function hook_comment_insert(Drupal\comment\Comment $comment) {
  // Reindex the node when comments are added.
  search_touch_node($comment->nid);
}

/**
 * Respond to updates to a comment.
 *
 * @param Drupal\comment\Comment $comment
 *   The comment object.
 */
function hook_comment_update(Drupal\comment\Comment $comment) {
  // Reindex the node when comments are updated.
  search_touch_node($comment->nid);
}

/**
 * Act on comments being loaded from the database.
 *
 * @param array $comments
 *  An array of comment objects indexed by cid.
 */
function hook_comment_load(Drupal\comment\Comment $comments) {
  $result = db_query('SELECT cid, foo FROM {mytable} WHERE cid IN (:cids)', array(':cids' => array_keys($comments)));
  foreach ($result as $record) {
    $comments[$record->cid]->foo = $record->foo;
  }
}

/**
 * Act on a comment that is being assembled before rendering.
 *
 * @param Drupal\comment\Comment $comment
 *   Passes in the comment the action is being performed on.
 * @param $view_mode
 *   View mode, e.g. 'full', 'teaser'...
 * @param $langcode
 *   The language code used for rendering.
 *
 * @see hook_entity_view()
 */
function hook_comment_view(Drupal\comment\Comment $comment, $view_mode, $langcode) {
  // how old is the comment
  $comment->time_ago = time() - $comment->changed;
}

/**
 * Alter the results of comment_view().
 *
 * This hook is called after the content has been assembled in a structured
 * array and may be used for doing processing which requires that the complete
 * comment content structure has been built.
 *
 * If the module wishes to act on the rendered HTML of the comment rather than
 * the structured content array, it may use this hook to add a #post_render
 * callback. Alternatively, it could also implement hook_preprocess_comment().
 * See drupal_render() and theme() documentation respectively for details.
 *
 * @param $build
 *   A renderable array representing the comment.
 *
 * @see comment_view()
 * @see hook_entity_view_alter()
 */
function hook_comment_view_alter(&$build) {
  // Check for the existence of a field added by another module.
  if ($build['#view_mode'] == 'full' && isset($build['an_additional_field'])) {
    // Change its weight.
    $build['an_additional_field']['#weight'] = -10;
  }

  // Add a #post_render callback to act on the rendered HTML of the comment.
  $build['#post_render'][] = 'my_module_comment_post_render';
}

/**
 * Respond to a comment being published by a moderator.
 *
 * @param Drupal\comment\Comment $comment
 *   The comment the action is being performed on.
 */
function hook_comment_publish(Drupal\comment\Comment $comment) {
  drupal_set_message(t('Comment: @subject has been published', array('@subject' => $comment->subject)));
}

/**
 * Respond to a comment being unpublished by a moderator.
 *
 * @param Drupal\comment\Comment $comment
 *   The comment the action is being performed on.
 */
function hook_comment_unpublish(Drupal\comment\Comment $comment) {
  drupal_set_message(t('Comment: @subject has been unpublished', array('@subject' => $comment->subject)));
}

/**
 * Act before comment deletion.
 *
 * This hook is invoked from comment_delete_multiple() before
 * field_attach_delete() is called and before the comment is actually removed
 * from the database.
 *
 * @param Drupal\comment\Comment $comment
 *   The comment object for the comment that is about to be deleted.
 *
 * @see hook_comment_delete()
 * @see comment_delete_multiple()
 * @see entity_delete_multiple()
 */
function hook_comment_predelete(Drupal\comment\Comment $comment) {
  // Delete a record associated with the comment in a custom table.
  db_delete('example_comment_table')
    ->condition('cid', $comment->cid)
    ->execute();
}

/**
 * Respond to comment deletion.
 *
 * This hook is invoked from comment_delete_multiple() after
 * field_attach_delete() has called and after the comment has been removed from
 * the database.
 *
 * @param Drupal\comment\Comment $comment
 *   The comment object for the comment that has been deleted.
 *
 * @see hook_comment_predelete()
 * @see comment_delete_multiple()
 * @see entity_delete_multiple()
 */
function hook_comment_delete(Drupal\comment\Comment $comment) {
  drupal_set_message(t('Comment: @subject has been deleted', array('@subject' => $comment->subject)));
}

/**
 * @} End of "addtogroup hooks".
 */
