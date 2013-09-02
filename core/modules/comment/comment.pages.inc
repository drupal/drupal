<?php

/**
 * @file
 * User page callbacks for the Comment module.
 */

use Drupal\Core\Entity\EntityInterface;
use Drupal\comment\Entity\Comment;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\Core\Language\Language;

/**
 * Form constructor for the comment reply form.
 *
 * There are several cases that have to be handled, including:
 *   - replies to comments
 *   - replies to entities
 *   - attempts to reply to entities that can no longer accept comments
 *   - respecting access permissions ('access comments', 'post comments', etc.)
 *
 * The entity or comment that is being replied to must appear above the comment
 * form to provide the user context while authoring the comment.
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   Every comment belongs to an entity. This is that entity.
 * @param string $field_name
 *   The field_name to which the commment belongs.
 * @param int $pid
 *   (optional) Some comments are replies to other comments. In those cases,
 *   $pid is the parent comment's comment ID. Defaults to NULL.
 *
 * @return array
 *   An associative array containing:
 *   - An array for rendering the entity or parent comment.
 *     - comment_entity: If the comment is a reply to the entity.
 *     - comment_parent: If the comment is a reply to another comment.
 *   - comment_form: The comment form as a renderable array.
 */
function comment_reply(EntityInterface $entity, $field_name, $pid = NULL) {
  // Store the entity uri.
  $uri = $entity->uri();
  $op = Drupal::request()->request->get('op');
  $build = array();

  // The user is previewing a comment prior to submitting it.
  if ($op == t('Preview')) {
    if (user_access('post comments')) {
      $build['comment_form'] = comment_add($entity, $field_name, $pid);
    }
    else {
      drupal_set_message(t('You are not authorized to post comments.'), 'error');
      $uri['options']['absolute'] = TRUE;
      return new RedirectResponse(url($uri['path'], $uri['options']));
    }
  }
  else {
    // $pid indicates that this is a reply to a comment.
    if ($pid) {
      if (user_access('access comments')) {
        // Load the parent comment.
        $comment = comment_load($pid);
        if ($comment->status->value == COMMENT_PUBLISHED) {
          $parts = explode('.', $comment->field_id);
          list(, $record_field_name) = $parts;
          // If that comment exists, make sure that the current comment and the
          // parent comment both belong to the same parent node.
          if ($comment->entity_id->value != $entity->id() ||
              $record_field_name != $field_name ||
              $comment->entity_type->value != $entity->entityType()) {
            // Attempting to reply to a comment not belonging to the current
            // entity.
            drupal_set_message(t('The comment you are replying to does not exist.'), 'error');
            $uri['options']['absolute'] = TRUE;
            return new RedirectResponse(url($uri['path'], $uri['options']));
          }
          // Display the parent comment.
          $build['comment_parent'] = comment_view($comment);
        }
        else {
          drupal_set_message(t('The comment you are replying to does not exist.'), 'error');
          $uri['options']['absolute'] = TRUE;
          return new RedirectResponse(url($uri['path'], $uri['options']));
        }
      }
      else {
        drupal_set_message(t('You are not authorized to view comments.'), 'error');
        $uri['options']['absolute'] = TRUE;
        return new RedirectResponse(url($uri['path'], $uri['options']));
      }
    }
    // This is the case where the comment is in response to a entity. Display
    // the entity.
    elseif ($entity->access('view')) {
      // We make sure the field value isn't set so we don't end up with a
      // redirect loop.
      $original_value = $entity->{$field_name}->status;
      $entity->{$field_name}->status = COMMENT_HIDDEN;
      $build['comment_entity'] = entity_view($entity, 'full');
      $entity->{$field_name}->status = $original_value;
    }

    // Should we show the reply box?
    $commenting_status = $entity->get($field_name)->status;
    if ($commenting_status != COMMENT_OPEN) {
      drupal_set_message(t("This discussion is closed: you can't post new comments."), 'error');
      $uri['options']['absolute'] = TRUE;
      return new RedirectResponse(url($uri['path'], $uri['options']));
    }
    elseif (user_access('post comments')) {
      $build['comment_form'] = comment_add($entity, $field_name, $pid);
    }
    else {
      drupal_set_message(t('You are not authorized to post comments.'), 'error');
      $uri['options']['absolute'] = TRUE;
      return new RedirectResponse(url($uri['path'], $uri['options']));
    }
  }

  return $build;
}
