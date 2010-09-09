<?php
// $Id: comment.pages.inc,v 1.40 2010/09/09 23:01:48 dries Exp $

/**
 * @file
 * User page callbacks for the comment module.
 */

/**
 * This function is responsible for generating a comment reply form.
 * There are several cases that have to be handled, including:
 *   - replies to comments
 *   - replies to nodes
 *   - attempts to reply to nodes that can no longer accept comments
 *   - respecting access permissions ('access comments', 'post comments', etc.)
 *
 * The node or comment that is being replied to must appear above the comment
 * form to provide the user context while authoring the comment.
 *
 * @param $node
 *   Every comment belongs to a node. This is that node.
 *
 * @param $pid
 *   Some comments are replies to other comments. In those cases, $pid is the parent
 *   comment's cid.
 *
 * @return
 *   The rendered parent node or comment plus the new comment form.
 */
function comment_reply($node, $pid = NULL) {
  // Set the breadcrumb trail.
  drupal_set_breadcrumb(array(l(t('Home'), NULL), l($node->title, 'node/' . $node->nid)));
  $op = isset($_POST['op']) ? $_POST['op'] : '';
  $build = array();

  if (user_access('access comments')) {
    // The user is previewing a comment prior to submitting it.
    if ($op == t('Preview')) {
      if (user_access('post comments')) {
        $build['comment_form'] = drupal_get_form("comment_node_{$node->type}_form", (object) array('pid' => $pid, 'nid' => $node->nid));
      }
      else {
        drupal_set_message(t('You are not authorized to post comments.'), 'error');
        drupal_goto("node/$node->nid");
      }
    }
    else {
      // $pid indicates that this is a reply to a comment.
      if ($pid) {
        // Load the comment whose cid = $pid
        $comment = db_query('SELECT c.*, u.uid, u.name AS registered_name, u.signature, u.signature_format, u.picture, u.data FROM {comment} c INNER JOIN {users} u ON c.uid = u.uid WHERE c.cid = :cid AND c.status = :status', array(
          ':cid' => $pid,
          ':status' => COMMENT_PUBLISHED,
        ))->fetchObject();
        if ( $comment ) {
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
        $edit = array('nid' => $node->nid, 'pid' => $pid);
        $build['comment_form'] = drupal_get_form("comment_node_{$node->type}_form", (object) $edit);
      }
      else {
        drupal_set_message(t('You are not authorized to post comments.'), 'error');
        drupal_goto("node/$node->nid");
      }
    }
  }
  else {
    drupal_set_message(t('You are not authorized to view comments.'), 'error');
    drupal_goto("node/$node->nid");
  }

  return $build;
}

/**
 * Menu callback; publish specified comment.
 *
 * @param $cid
 *   A comment identifier.
 */
function comment_approve($cid) {
  if (!isset($_GET['token']) || !drupal_valid_token($_GET['token'], "comment/$cid/approve")) {
    return MENU_ACCESS_DENIED;
  }
  if ($comment = comment_load($cid)) {
    $comment->status = COMMENT_PUBLISHED;
    comment_save($comment);

    drupal_set_message(t('Comment approved.'));
    drupal_goto('node/' . $comment->nid);
  }
  return MENU_NOT_FOUND;
}
