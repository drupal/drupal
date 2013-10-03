<?php

use Drupal\Core\Entity\EntityInterface;

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
 * This hook is invoked from $comment->save() before the comment is saved to the
 * database.
 *
 * @param \Drupal\comment\Comment $comment
 *   The comment object.
 */
function hook_comment_presave(Drupal\comment\Comment $comment) {
  // Remove leading & trailing spaces from the comment subject.
  $comment->subject->value = trim($comment->subject->value);
}

/**
 * Respond to creation of a new comment.
 *
 * @param \Drupal\comment\Comment $comment
 *   The comment object.
 */
function hook_comment_insert(Drupal\comment\Comment $comment) {
  // Reindex the node when comments are added.
  if ($comment->entity_type->value == 'node') {
    node_reindex_node_search($comment->entity_id->value);
  }
}

/**
 * Respond to updates to a comment.
 *
 * @param \Drupal\comment\Comment $comment
 *   The comment object.
 */
function hook_comment_update(Drupal\comment\Comment $comment) {
  // Reindex the node when comments are updated.
  if ($comment->entity_type->value == 'node') {
    node_reindex_node_search($comment->entity_id->value);
  }
}

/**
 * Act on a newly created comment.
 *
 * This hook runs after a new comment object has just been instantiated. It can
 * be used to set initial values, e.g. to provide defaults.
 *
 * @param \Drupal\comment\Entity\Comment $comment
 *   The comment object.
 */
function hook_comment_create(\Drupal\comment\Entity\Comment $comment) {
  if (!isset($comment->foo)) {
    $comment->foo = 'some_initial_value';
  }
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
 * @param \Drupal\comment\Entity\Comment $comment $comment
 *   Passes in the comment the action is being performed on.
 * @param \Drupal\entity\Entity\EntityDisplay $display
 *   The entity_display object holding the display options configured for the
 *   comment components.
 * @param $view_mode
 *   View mode, e.g. 'full', 'teaser'...
 * @param $langcode
 *   The language code used for rendering.
 *
 * @see hook_entity_view()
 */
function hook_comment_view(\Drupal\comment\Entity\Comment $comment, \Drupal\entity\Entity\EntityDisplay $display, $view_mode, $langcode) {
  // Only do the extra work if the component is configured to be displayed.
  // This assumes a 'mymodule_addition' extra field has been defined for the
  // node type in hook_field_extra_fields().
  if ($display->getComponent('mymodule_addition')) {
    $comment->content['mymodule_addition'] = array(
      '#markup' => mymodule_addition($comment),
      '#theme' => 'mymodule_my_additional_field',
    );
  }
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
 * callback. Alternatively, it could also implement hook_preprocess_HOOK() for
 * comment.html.twig. See drupal_render() documentation for details.
 *
 * @param $build
 *   A renderable array representing the comment.
 * @param \Drupal\comment\Entity\Comment $comment
 *   The comment being rendered.
 * @param \Drupal\entity\Entity\EntityDisplay $display
 *   The entity_display object holding the display options configured for the
 *   comment components.
 *
 * @see comment_view()
 * @see hook_entity_view_alter()
 */
function hook_comment_view_alter(&$build, \Drupal\comment\Entity\Comment $comment, \Drupal\entity\Entity\EntityDisplay $display) {
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
 * @param \Drupal\comment\Comment $comment
 *   The comment the action is being performed on.
 */
function hook_comment_publish(Drupal\comment\Comment $comment) {
  drupal_set_message(t('Comment: @subject has been published', array('@subject' => $comment->subject->value)));
}

/**
 * Respond to a comment being unpublished by a moderator.
 *
 * @param \Drupal\comment\Comment $comment
 *   The comment the action is being performed on.
 */
function hook_comment_unpublish(Drupal\comment\Comment $comment) {
  drupal_set_message(t('Comment: @subject has been unpublished', array('@subject' => $comment->subject->value)));
}

/**
 * Act before comment deletion.
 *
 * This hook is invoked from entity_delete_multiple() before field values are
 * deleted and before the comment is actually removed from the database.
 *
 * @param \Drupal\comment\Comment $comment
 *   The comment object for the comment that is about to be deleted.
 *
 * @see hook_comment_delete()
 * @see entity_delete_multiple()
 */
function hook_comment_predelete(Drupal\comment\Comment $comment) {
  // Delete a record associated with the comment in a custom table.
  db_delete('example_comment_table')
    ->condition('cid', $comment->id())
    ->execute();
}

/**
 * Respond to comment deletion.
 *
 * This hook is invoked from entity_delete_multiple() after field values are
 * deleted and after the comment has been removed from the database.
 *
 * @param \Drupal\comment\Comment $comment
 *   The comment object for the comment that has been deleted.
 *
 * @see hook_comment_predelete()
 * @see entity_delete_multiple()
 */
function hook_comment_delete(Drupal\comment\Comment $comment) {
  drupal_set_message(t('Comment: @subject has been deleted', array('@subject' => $comment->subject->value)));
}

/**
 * @} End of "addtogroup hooks".
 */
