<?php
// $Id$

/**
 * @file
 * Admin page callbacks for the comment module.
 */

/**
 * Menu callback; present an administrative comment listing.
 */
function comment_admin($type = 'new') {
  $edit = $_POST;

  if (isset($edit['operation']) && ($edit['operation'] == 'delete') && isset($edit['comments']) && $edit['comments']) {
    return drupal_get_form('comment_multiple_delete_confirm');
  }
  else {
    return drupal_get_form('comment_admin_overview', $type, arg(4));
  }
}

/**
 * Form builder; Builds the comment overview form for the admin.
 *
 * @param $type
 *   Not used.
 * @param $arg
 *   Current path's fourth component deciding the form type (Published comments/Approval queue)
 * @return
 *   The form structure.
 * @ingroup forms
 * @see comment_admin_overview_validate()
 * @see comment_admin_overview_submit()
 * @see theme_comment_admin_overview()
 */
function comment_admin_overview($type = 'new', $arg) {
  // build an 'Update options' form
  $form['options'] = array(
    '#type' => 'fieldset', '#title' => t('Update options'),
    '#prefix' => '<div class="container-inline">', '#suffix' => '</div>'
  );
  $options = array();
  foreach (comment_operations($arg == 'approval' ? 'publish' : 'unpublish') as $key => $value) {
    $options[$key] = $value[0];
  }
  $form['options']['operation'] = array('#type' => 'select', '#options' => $options, '#default_value' => 'publish');
  $form['options']['submit'] = array('#type' => 'submit', '#value' => t('Update'));

  // load the comments that we want to display
  $status = ($arg == 'approval') ? COMMENT_NOT_PUBLISHED : COMMENT_PUBLISHED;
  $form['header'] = array('#type' => 'value', '#value' => array(
    theme('table_select_header_cell'),
    array('data' => t('Subject'), 'field' => 'subject'),
    array('data' => t('Author'), 'field' => 'name'),
    array('data' => t('Posted in'), 'field' => 'node_title'),
    array('data' => t('Time'), 'field' => 'timestamp', 'sort' => 'desc'),
    array('data' => t('Operations'))
  ));
  $result = pager_query('SELECT c.subject, c.nid, c.cid, c.comment, c.timestamp, c.status, c.name, c.homepage, u.name AS registered_name, u.uid, n.title as node_title FROM {comments} c INNER JOIN {users} u ON u.uid = c.uid INNER JOIN {node} n ON n.nid = c.nid WHERE c.status = %d'. tablesort_sql($form['header']['#value']), 50, 0, NULL, $status);

  // build a table listing the appropriate comments
  $destination = drupal_get_destination();
  while ($comment = db_fetch_object($result)) {
    $comments[$comment->cid] = '';
    $comment->name = $comment->uid ? $comment->registered_name : $comment->name;
    $form['subject'][$comment->cid] = array('#value' => l($comment->subject, 'node/'. $comment->nid, array('attributes' => array('title' => truncate_utf8($comment->comment, 128)), 'fragment' => 'comment-'. $comment->cid)));
    $form['username'][$comment->cid] = array('#value' => theme('username', $comment));
    $form['node_title'][$comment->cid] = array('#value' => l($comment->node_title, 'node/'. $comment->nid));
    $form['timestamp'][$comment->cid] = array('#value' => format_date($comment->timestamp, 'small'));
    $form['operations'][$comment->cid] = array('#value' => l(t('edit'), 'comment/edit/'. $comment->cid, array('query' => $destination)));
  }
  $form['comments'] = array('#type' => 'checkboxes', '#options' => isset($comments) ? $comments: array());
  $form['pager'] = array('#value' => theme('pager', NULL, 50, 0));
  return $form;
}

/**
 * Validate comment_admin_overview form submissions.
 *
 * We can't execute any 'Update options' if no comments were selected.
 */
function comment_admin_overview_validate($form, &$form_state) {
  $form_state['values']['comments'] = array_diff($form_state['values']['comments'], array(0));
  if (count($form_state['values']['comments']) == 0) {
    form_set_error('', t('Please select one or more comments to perform the update on.'));
  }
}

/**
 * Process comment_admin_overview form submissions.
 *
 * Execute the chosen 'Update option' on the selected comments, such as
 * publishing, unpublishing or deleting.
 */
function comment_admin_overview_submit($form, &$form_state) {
  $operations = comment_operations();
  if (!empty($operations[$form_state['values']['operation']][1])) {
    // extract the appropriate database query operation
    $query = $operations[$form_state['values']['operation']][1];
    foreach ($form_state['values']['comments'] as $cid => $value) {
      if ($value) {
        // perform the update action, then refresh node statistics
        db_query($query, $cid);
        $comment = _comment_load($cid);
        _comment_update_node_statistics($comment->nid);
        // Allow modules to respond to the updating of a comment.
        comment_invoke_comment($comment, $form_state['values']['operation']);
        // Add an entry to the watchdog log.
        watchdog('content', 'Comment: updated %subject.', array('%subject' => $comment->subject), WATCHDOG_NOTICE, l(t('view'), 'node/'. $comment->nid, array('fragment' => 'comment-'. $comment->cid)));
      }
    }
    cache_clear_all();
    drupal_set_message(t('The update has been performed.'));
    $form_state['redirect'] = 'admin/content/comment';
  }
}

/**
 * Theme the comment admin form.
 *
 * @param $form
 *   An associative array containing the structure of the form.
 * @ingroup themeable
 */
function theme_comment_admin_overview($form) {
  $output = drupal_render($form['options']);
  if (isset($form['subject']) && is_array($form['subject'])) {
    foreach (element_children($form['subject']) as $key) {
      $row = array();
      $row[] = drupal_render($form['comments'][$key]);
      $row[] = drupal_render($form['subject'][$key]);
      $row[] = drupal_render($form['username'][$key]);
      $row[] = drupal_render($form['node_title'][$key]);
      $row[] = drupal_render($form['timestamp'][$key]);
      $row[] = drupal_render($form['operations'][$key]);
      $rows[] = $row;
    }
  }
  else {
    $rows[] = array(array('data' => t('No comments available.'), 'colspan' => '6'));
  }

  $output .= theme('table', $form['header']['#value'], $rows);
  if ($form['pager']['#value']) {
    $output .= drupal_render($form['pager']);
  }

  $output .= drupal_render($form);

  return $output;
}

/**
 * List the selected comments and verify that the admin really wants to delete
 * them.
 *
 * @param $form_state
 *   An associative array containing the current state of the form.
 * @return
 *   TRUE if the comments should be deleted, FALSE otherwise.
 * @ingroup forms
 * @see comment_multiple_delete_confirm_submit()
 */
function comment_multiple_delete_confirm(&$form_state) {
  $edit = $form_state['post'];

  $form['comments'] = array('#prefix' => '<ul>', '#suffix' => '</ul>', '#tree' => TRUE);
  // array_filter() returns only elements with actual values
  $comment_counter = 0;
  foreach (array_filter($edit['comments']) as $cid => $value) {
    $comment = _comment_load($cid);
    if (is_object($comment) && is_numeric($comment->cid)) {
      $subject = db_result(db_query('SELECT subject FROM {comments} WHERE cid = %d', $cid));
      $form['comments'][$cid] = array('#type' => 'hidden', '#value' => $cid, '#prefix' => '<li>', '#suffix' => check_plain($subject) .'</li>');
      $comment_counter++;
    }
  }
  $form['operation'] = array('#type' => 'hidden', '#value' => 'delete');

  if (!$comment_counter) {
    drupal_set_message(t('There do not appear to be any comments to delete or your selected comment was deleted by another administrator.'));
    drupal_goto('admin/content/comment');
  }
  else {
    return confirm_form($form,
                        t('Are you sure you want to delete these comments and all their children?'),
                        'admin/content/comment', t('This action cannot be undone.'),
                        t('Delete comments'), t('Cancel'));
  }
}

/**
 * Process comment_multiple_delete_confirm form submissions.
 *
 * Perform the actual comment deletion.
 */
function comment_multiple_delete_confirm_submit($form, &$form_state) {
  if ($form_state['values']['confirm']) {
    foreach ($form_state['values']['comments'] as $cid => $value) {
      $comment = _comment_load($cid);
      _comment_delete_thread($comment);
      _comment_update_node_statistics($comment->nid);
    }
    cache_clear_all();
    drupal_set_message(t('The comments have been deleted.'));
  }
  $form_state['redirect'] = 'admin/content/comment';
}

/**
 * Menu callback; delete a comment.
 *
 * @param $cid
 *   The comment do be deleted.
 */
function comment_delete($cid = NULL) {
  $comment = db_fetch_object(db_query('SELECT c.*, u.name AS registered_name, u.uid FROM {comments} c INNER JOIN {users} u ON u.uid = c.uid WHERE c.cid = %d', $cid));
  $comment->name = $comment->uid ? $comment->registered_name : $comment->name;

  $output = '';

  if (is_object($comment) && is_numeric($comment->cid)) {
    $output = drupal_get_form('comment_confirm_delete', $comment);
  }
  else {
    drupal_set_message(t('The comment no longer exists.'));
  }

  return $output;
}

/**
 * Form builder; Builds the confirmation form for deleting a single comment.
 *
 * @ingroup forms
 * @see comment_confirm_delete_submit()
 */
function comment_confirm_delete(&$form_state, $comment) {
  $form = array();
  $form['#comment'] = $comment;
  return confirm_form(
    $form,
    t('Are you sure you want to delete the comment %title?', array('%title' => $comment->subject)),
    'node/'. $comment->nid,
    t('Any replies to this comment will be lost. This action cannot be undone.'),
    t('Delete'),
    t('Cancel'),
    'comment_confirm_delete');
}

/**
 * Process comment_confirm_delete form submissions.
 */
function comment_confirm_delete_submit($form, &$form_state) {
  drupal_set_message(t('The comment and all its replies have been deleted.'));

  $comment = $form['#comment'];

  // Delete comment and its replies.
  _comment_delete_thread($comment);

  _comment_update_node_statistics($comment->nid);

  // Clear the cache so an anonymous user sees that his comment was deleted.
  cache_clear_all();

  $form_state['redirect'] = "node/$comment->nid";
}

/**
 * Perform the actual deletion of a comment and all its replies.
 *
 * @param $comment
 *   An associative array describing the comment to be deleted.
 */
function _comment_delete_thread($comment) {
  if (!is_object($comment) || !is_numeric($comment->cid)) {
    watchdog('content', 'Cannot delete non-existent comment.', array(), WATCHDOG_WARNING);
    return;
  }

  // Delete the comment:
  db_query('DELETE FROM {comments} WHERE cid = %d', $comment->cid);
  watchdog('content', 'Comment: deleted %subject.', array('%subject' => $comment->subject));

  comment_invoke_comment($comment, 'delete');

  // Delete the comment's replies
  $result = db_query('SELECT c.*, u.name AS registered_name, u.uid FROM {comments} c INNER JOIN {users} u ON u.uid = c.uid WHERE pid = %d', $comment->cid);
  while ($comment = db_fetch_object($result)) {
    $comment->name = $comment->uid ? $comment->registered_name : $comment->name;
    _comment_delete_thread($comment);
  }
}
