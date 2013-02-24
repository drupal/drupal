<?php

/**
 * @file
 * User page callbacks for the Comment module.
 */

use Drupal\core\Entity\EntityInterface;
use Drupal\node\Plugin\Core\Entity\Node;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
  // Set the breadcrumb trail.
  $uri = $entity->uri();
  drupal_set_breadcrumb(array(
    l(t('Home'), NULL),
    l($entity->label(), $uri['path'], $uri['options']))
  );
  $op = isset($_POST['op']) ? $_POST['op'] : '';
  $build = array();

  // The user is previewing a comment prior to submitting it.
  if ($op == t('Preview')) {
    if (user_access('post comments')) {
      $build['comment_form'] = comment_add($entity, $field_name, $pid);
    }
    else {
      drupal_set_message(t('You are not authorized to post comments.'), 'error');
      $uri = $entity->uri();
      drupal_goto($uri['path']);
    }
  }
  else {
    // $pid indicates that this is a reply to a comment.
    if ($pid) {
      if (user_access('access comments')) {
        // Load the parent comment.
        $comment = comment_load($pid);
        if ($comment->status->value == COMMENT_PUBLISHED) {
          // If that comment exists, make sure that the current comment and the
          // parent comment both belong to the same parent node.
          if ($comment->entity_id->target_id != $entity->id() ||
              $comment->field_name->value != $field_name ||
              $comment->entity_type->value != $entity->entityType()) {
            // Attempting to reply to a comment not belonging to the current entity.
            drupal_set_message(t('The comment you are replying to does not exist.'), 'error');
            drupal_goto($uri['path']);
          }
          // Display the parent comment.
          $build['comment_parent'] = comment_view($comment);
        }
        else {
          drupal_set_message(t('The comment you are replying to does not exist.'), 'error');
          drupal_goto($uri['path']);
        }
      }
      else {
        drupal_set_message(t('You are not authorized to view comments.'), 'error');
        drupal_goto($uri['path']);
      }
    }
    // This is the case where the comment is in response to a entity. Display
    // the entity.
    // @todo replace this with entity access controls once generic access
    // controller lands.
    // @see http://drupal.org/node/1696660
    elseif (user_access('access content')) {
      // We make sure the field value isn't set so we don't end up with a redirect loop.
      $original = $entity->{$field_name};
      $entity->{$field_name} = array(
        LANGUAGE_NOT_SPECIFIED => array(array('status' => COMMENT_HIDDEN)),
      );
      $build['comment_entity'] = entity_view($entity, 'full');
      $entity->{$field_name} = $original;
    }

    // Should we show the reply box?
    $commenting_status = _comment_get_default_status(field_get_items($entity, $field_name));
    if ($commenting_status != COMMENT_OPEN) {
      drupal_set_message(t("This discussion is closed: you can't post new comments."), 'error');
      drupal_goto($uri['path']);
    }
    elseif (user_access('post comments')) {
      $build['comment_form'] = comment_add($entity, $field_name, $pid);
    }
    else {
      drupal_set_message(t('You are not authorized to post comments.'), 'error');
      drupal_goto($uri['path']);
    }
  }

  return $build;
}

/**
 * Page callback: Publishes the specified comment.
 *
 * @param $cid
 *   A comment identifier.
 *
 * @see comment_menu()
 */
function comment_approve($cid) {
  // @todo CSRF tokens are validated in page callbacks rather than access
  //   callbacks, because access callbacks are also invoked during menu link
  //   generation. Add token support to routing: http://drupal.org/node/755584.
  $token = drupal_container()->get('request')->query->get('token');
  if (!isset($token) || !drupal_valid_token($token, "comment/$cid/approve")) {
    throw new AccessDeniedHttpException();
  }

  if ($comment = comment_load($cid)) {
    $comment->status->value = COMMENT_PUBLISHED;
    comment_save($comment);

    drupal_set_message(t('Comment approved.'));
    $entity = entity_load($comment->entity_type->value, $comment->entity_id->target_id);
    if ($entity) {
      $uri = $entity->uri();
      drupal_goto($uri['path']);
    }
  }
  throw new NotFoundHttpException();
}

/**
 * Page callback: Legacy handler to redirect old comment reply urls to new url.
 *
 * @param \Drupal\node\Plugin\Core\Entity\Node $node
 *   The node to which the comments are attached.
 * @param int $pid
 *   (optional) Some comments are replies to other comments. In those cases,
 *   $pid is the parent comment's comment ID. Defaults to NULL.
 *
 * @throw \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
 *   When there are no comment fields attached to the node.
 */
function comment_node_redirect(Node $node, $pid = NULL) {
  $fields = comment_get_comment_fields('node');
  foreach ($fields as $field_name => $detail) {
    // Pick the first comment field found on the node.
    if (in_array($node->bundle(), $detail['bundles']['node'], TRUE)) {
      drupal_goto('comment/reply/node/' . $node->id() . '/' . $field_name);
    }
  }
  throw new NotFoundHttpException();
}
