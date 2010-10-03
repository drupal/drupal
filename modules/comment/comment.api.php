<?php
// $Id: comment.api.php,v 1.17 2010/10/03 01:15:33 dries Exp $

/**
 * @file
 * Hooks provided by the Comment module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * The comment passed validation and is about to be saved.
 *
 * Modules may make changes to the comment before it is saved to the database.
 *
 * @param $comment
 *   The comment object.
 */
function hook_comment_presave($comment) {
  // Remove leading & trailing spaces from the comment subject.
  $comment->subject = trim($comment->subject);
}

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
 * Comments are being loaded from the database.
 *
 * @param $comments
 *  An array of comment objects indexed by cid.
 */
function hook_comment_load($comments) {
  $result = db_query('SELECT cid, foo FROM {mytable} WHERE cid IN (:cids)', array(':cids' => array_keys($comments)));
  foreach ($result as $record) {
    $comments[$record->cid]->foo = $record->foo;
  }
}

/**
 * The comment is being viewed. This hook can be used to add additional data to the comment before theming.
 *
 * @param $comment
 *   Passes in the comment the action is being performed on.
 * @param $view_mode
 *   View mode, e.g. 'full', 'teaser'...
 * @param $langcode
 *   The language code used for rendering.
 */
function hook_comment_view($comment, $view_mode, $langcode) {
  // how old is the comment
  $comment->time_ago = time() - $comment->changed;
}

/**
 * The comment was built; the module may modify the structured content.
 *
 * This hook is called after the content has been assembled in a structured array
 * and may be used for doing processing which requires that the complete comment
 * content structure has been built.
 *
 * If the module wishes to act on the rendered HTML of the comment rather than the
 * structured content array, it may use this hook to add a #post_render callback.
 * Alternatively, it could also implement hook_preprocess_comment(). See
 * drupal_render() and theme() documentation respectively for details.
 *
 * @param $build
 *   A renderable array representing the comment.
 *
 * @see comment_view()
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
