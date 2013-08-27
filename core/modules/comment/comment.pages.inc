<?php

/**
 * @file
 * User page callbacks for the Comment module.
 */

use Drupal\Core\Entity\EntityInterface;
use Drupal\comment\Entity\Comment;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Form constructor for the comment reply form.
 *
 * There are several cases that have to be handled, including:
 *   - replies to comments
 *   - replies to nodes
 *   - attempts to reply to nodes that can no longer accept comments
 *   - respecting access permissions ('access comments', 'post comments', etc.)
 *
 * The node or comment that is being replied to must appear above the comment
 * form to provide the user context while authoring the comment.
 *
 * @param \Drupal\Core\Entity\EntityInterface $node
 *   Every comment belongs to a node. This is that node.
 * @param $pid
 *   (optional) Some comments are replies to other comments. In those cases,
 *   $pid is the parent comment's comment ID. Defaults to NULL.
 *
 * @return array
 *   An associative array containing:
 *   - An array for rendering the node or parent comment.
 *     - comment_node: If the comment is a reply to the node.
 *     - comment_parent: If the comment is a reply to another comment.
 *   - comment_form: The comment form as a renderable array.
 */
function comment_reply(EntityInterface $node, $pid = NULL) {
  // Set the breadcrumb trail.
  drupal_set_breadcrumb(array(l(t('Home'), NULL), l($node->label(), 'node/' . $node->id())));
  $op = Drupal::request()->request->get('op');
  $build = array();

  // The user is previewing a comment prior to submitting it.
  if ($op == t('Preview')) {
    if (user_access('post comments')) {
      $build['comment_form'] = comment_add($node, $pid);
    }
    else {
      drupal_set_message(t('You are not authorized to post comments.'), 'error');
      return new RedirectResponse(url('node/' . $node->id(), array('absolute' => TRUE)));
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
          if ($comment->nid->target_id != $node->id()) {
            // Attempting to reply to a comment not belonging to the current nid.
            drupal_set_message(t('The comment you are replying to does not exist.'), 'error');
            return new RedirectResponse(url('node/' . $node->id(), array('absolute' => TRUE)));
          }
          // Display the parent comment
          $build['comment_parent'] = comment_view($comment);
        }
        else {
          drupal_set_message(t('The comment you are replying to does not exist.'), 'error');
          return new RedirectResponse(url('node/' . $node->id(), array('absolute' => TRUE)));
        }
      }
      else {
        drupal_set_message(t('You are not authorized to view comments.'), 'error');
        return new RedirectResponse(url('node/' . $node->id(), array('absolute' => TRUE)));
      }
    }
    // This is the case where the comment is in response to a node. Display the node.
    elseif (user_access('access content')) {
      $build['comment_node'] = node_view($node);
    }

    // Should we show the reply box?
    if ($node->comment->value != COMMENT_NODE_OPEN) {
      drupal_set_message(t("This discussion is closed: you can't post new comments."), 'error');
      return new RedirectResponse(url('node/' . $node->id(), array('absolute' => TRUE)));
    }
    elseif (user_access('post comments')) {
      $build['comment_form'] = comment_add($node, $pid);
    }
    else {
      drupal_set_message(t('You are not authorized to post comments.'), 'error');
      return new RedirectResponse(url('node/' . $node->id(), array('absolute' => TRUE)));
    }
  }

  return $build;
}
