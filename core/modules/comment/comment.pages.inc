<?php

/**
 * @file
 * User page callbacks for the Comment module.
 */

use Drupal\node\Node;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
 * @param Drupal\node\Node $node
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
function comment_reply(Node $node, $pid = NULL) {
  // Set the breadcrumb trail.
  drupal_set_breadcrumb(array(l(t('Home'), NULL), l($node->title, 'node/' . $node->nid)));
  $op = isset($_POST['op']) ? $_POST['op'] : '';
  $build = array();

  // The user is previewing a comment prior to submitting it.
  if ($op == t('Preview')) {
    if (user_access('post comments')) {
      $comment = entity_create('comment', array('nid' => $node->nid, 'pid' => $pid));
      $build['comment_form'] = drupal_get_form("comment_node_{$node->type}_form", $comment);
    }
    else {
      drupal_set_message(t('You are not authorized to post comments.'), 'error');
      drupal_goto("node/$node->nid");
    }
  }
  else {
    // $pid indicates that this is a reply to a comment.
    if ($pid) {
      if (user_access('access comments')) {
        // Load the parent comment.
        $comment = comment_load($pid);
        if ($comment->status == COMMENT_PUBLISHED) {
          // If that comment exists, make sure that the current comment and the
          // parent comment both belong to the same parent node.
          if ($comment->nid != $node->nid) {
            // Attempting to reply to a comment not belonging to the current nid.
            drupal_set_message(t('The comment you are replying to does not exist.'), 'error');
            drupal_goto("node/$node->nid");
          }
          // Display the parent comment
          $comment->node_type = 'comment_node_' . $node->type;
          field_attach_load('comment', array($comment->cid => $comment));
          $comment->name = $comment->uid ? $comment->registered_name : $comment->name;
          $build['comment_parent'] = comment_view($comment, $node);
        }
        else {
          drupal_set_message(t('The comment you are replying to does not exist.'), 'error');
          drupal_goto("node/$node->nid");
        }
      }
      else {
        drupal_set_message(t('You are not authorized to view comments.'), 'error');
        drupal_goto("node/$node->nid");
      }
    }
    // This is the case where the comment is in response to a node. Display the node.
    elseif (user_access('access content')) {
      $build['comment_node'] = node_view($node);
    }

    // Should we show the reply box?
    if ($node->comment != COMMENT_NODE_OPEN) {
      drupal_set_message(t("This discussion is closed: you can't post new comments."), 'error');
      drupal_goto("node/$node->nid");
    }
    elseif (user_access('post comments')) {
      $comment = entity_create('comment', array('nid' => $node->nid, 'pid' => $pid));
      $build['comment_form'] = drupal_get_form("comment_node_{$node->type}_form", $comment);
    }
    else {
      drupal_set_message(t('You are not authorized to post comments.'), 'error');
      drupal_goto("node/$node->nid");
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
  $token = request()->query->get('token');
  if (!isset($token) || !drupal_valid_token($token, "comment/$cid/approve")) {
    drupal_access_denied();
  }

  if ($comment = comment_load($cid)) {
    $comment->status = COMMENT_PUBLISHED;
    comment_save($comment);

    drupal_set_message(t('Comment approved.'));
    drupal_goto('node/' . $comment->nid);
  }
  throw new NotFoundHttpException();
}
