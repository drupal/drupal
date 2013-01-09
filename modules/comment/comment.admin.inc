<?php

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
    return drupal_get_form('comment_admin_overview', $type);
  }
}

/**
 * Form builder for the comment overview administration form.
 *
 * @param $arg
 *   Current path's fourth component: the type of overview form ('approval' or
 *   'new').
 *
 * @ingroup forms
 * @see comment_admin_overview_validate()
 * @see comment_admin_overview_submit()
 * @see theme_comment_admin_overview()
 */
function comment_admin_overview($form, &$form_state, $arg) {
  // Build an 'Update options' form.
  $form['options'] = array(
    '#type' => 'fieldset',
    '#title' => t('Update options'),
    '#attributes' => array('class' => array('container-inline')),
  );

  if ($arg == 'approval') {
    $options['publish'] = t('Publish the selected comments');
  }
  else {
    $options['unpublish'] = t('Unpublish the selected comments');
  }
  $options['delete'] = t('Delete the selected comments');

  $form['options']['operation'] = array(
    '#type' => 'select',
    '#title' => t('Operation'),
    '#title_display' => 'invisible',
    '#options' => $options,
    '#default_value' => 'publish',
  );
  $form['options']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Update'),
  );

  // Load the comments that need to be displayed.
  $status = ($arg == 'approval') ? COMMENT_NOT_PUBLISHED : COMMENT_PUBLISHED;
  $header = array(
    'subject' => array('data' => t('Subject'), 'field' => 'subject'),
    'author' => array('data' => t('Author'), 'field' => 'name'),
    'posted_in' => array('data' => t('Posted in'), 'field' => 'node_title'),
    'changed' => array('data' => t('Updated'), 'field' => 'c.changed', 'sort' => 'desc'),
    'operations' => array('data' => t('Operations')),
  );

  $query = db_select('comment', 'c')->extend('PagerDefault')->extend('TableSort');
  $query->join('node', 'n', 'n.nid = c.nid');
  $query->addField('n', 'title', 'node_title');
  $query->addTag('node_access');
  $result = $query
    ->fields('c', array('cid', 'subject', 'name', 'changed'))
    ->condition('c.status', $status)
    ->limit(50)
    ->orderByHeader($header)
    ->execute();

  $cids = array();

  // We collect a sorted list of node_titles during the query to attach to the
  // comments later.
  foreach ($result as $row) {
    $cids[] = $row->cid;
    $node_titles[] = $row->node_title;
  }
  $comments = comment_load_multiple($cids);

  // Build a table listing the appropriate comments.
  $options = array();
  $destination = drupal_get_destination();

  foreach ($comments as $comment) {
    // Remove the first node title from the node_titles array and attach to
    // the comment.
    $comment->node_title = array_shift($node_titles);
    $comment_body = field_get_items('comment', $comment, 'comment_body');
    $options[$comment->cid] = array(
      'subject' => array(
        'data' => array(
          '#type' => 'link',
          '#title' => $comment->subject,
          '#href' => 'comment/' . $comment->cid,
          '#options' => array('attributes' => array('title' => truncate_utf8($comment_body[0]['value'], 128)), 'fragment' => 'comment-' . $comment->cid),
        ),
      ),
      'author' => theme('username', array('account' => $comment)),
      'posted_in' => array(
        'data' => array(
          '#type' => 'link',
          '#title' => $comment->node_title,
          '#href' => 'node/' . $comment->nid,
        ),
      ),
      'changed' => format_date($comment->changed, 'short'),
      'operations' => array(
        'data' => array(
          '#type' => 'link',
          '#title' => t('edit'),
          '#href' => 'comment/' . $comment->cid . '/edit',
          '#options' => array('query' => $destination),
        ),
      ),
    );
  }

  $form['comments'] = array(
    '#type' => 'tableselect',
    '#header' => $header,
    '#options' => $options,
    '#empty' => t('No comments available.'),
  );

  $form['pager'] = array('#theme' => 'pager');

  return $form;
}

/**
 * Validate comment_admin_overview form submissions.
 */
function comment_admin_overview_validate($form, &$form_state) {
  $form_state['values']['comments'] = array_diff($form_state['values']['comments'], array(0));
  // We can't execute any 'Update options' if no comments were selected.
  if (count($form_state['values']['comments']) == 0) {
    form_set_error('', t('Select one or more comments to perform the update on.'));
  }
}

/**
 * Process comment_admin_overview form submissions.
 *
 * Execute the chosen 'Update option' on the selected comments, such as
 * publishing, unpublishing or deleting.
 */
function comment_admin_overview_submit($form, &$form_state) {
  $operation = $form_state['values']['operation'];
  $cids = $form_state['values']['comments'];

  if ($operation == 'delete') {
    comment_delete_multiple($cids);
  }
  else {
    foreach ($cids as $cid => $value) {
      $comment = comment_load($value);

      if ($operation == 'unpublish') {
        $comment->status = COMMENT_NOT_PUBLISHED;
      }
      elseif ($operation == 'publish') {
        $comment->status = COMMENT_PUBLISHED;
      }
      comment_save($comment);
    }
  }
  drupal_set_message(t('The update has been performed.'));
  $form_state['redirect'] = 'admin/content/comment';
  cache_clear_all();
}

/**
 * List the selected comments and verify that the admin wants to delete them.
 *
 * @param $form_state
 *   An associative array containing the current state of the form.
 * @return
 *   TRUE if the comments should be deleted, FALSE otherwise.
 * @ingroup forms
 * @see comment_multiple_delete_confirm_submit()
 */
function comment_multiple_delete_confirm($form, &$form_state) {
  $edit = $form_state['input'];

  $form['comments'] = array(
    '#prefix' => '<ul>',
    '#suffix' => '</ul>',
    '#tree' => TRUE,
  );
  // array_filter() returns only elements with actual values.
  $comment_counter = 0;
  foreach (array_filter($edit['comments']) as $cid => $value) {
    $comment = comment_load($cid);
    if (is_object($comment) && is_numeric($comment->cid)) {
      $subject = db_query('SELECT subject FROM {comment} WHERE cid = :cid', array(':cid' => $cid))->fetchField();
      $form['comments'][$cid] = array('#type' => 'hidden', '#value' => $cid, '#prefix' => '<li>', '#suffix' => check_plain($subject) . '</li>');
      $comment_counter++;
    }
  }
  $form['operation'] = array('#type' => 'hidden', '#value' => 'delete');

  if (!$comment_counter) {
    drupal_set_message(t('There do not appear to be any comments to delete, or your selected comment was deleted by another administrator.'));
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
 */
function comment_multiple_delete_confirm_submit($form, &$form_state) {
  if ($form_state['values']['confirm']) {
    comment_delete_multiple(array_keys($form_state['values']['comments']));
    cache_clear_all();
    $count = count($form_state['values']['comments']);
    watchdog('content', 'Deleted @count comments.', array('@count' => $count));
    drupal_set_message(format_plural($count, 'Deleted 1 comment.', 'Deleted @count comments.'));
  }
  $form_state['redirect'] = 'admin/content/comment';
}

/**
 * Page callback for comment deletions.
 */
function comment_confirm_delete_page($cid) {
  if ($comment = comment_load($cid)) {
    return drupal_get_form('comment_confirm_delete', $comment);
  }
  return MENU_NOT_FOUND;
}

/**
 * Form builder; Builds the confirmation form for deleting a single comment.
 *
 * @ingroup forms
 * @see comment_confirm_delete_submit()
 */
function comment_confirm_delete($form, &$form_state, $comment) {
  $form['#comment'] = $comment;
  // Always provide entity id in the same form key as in the entity edit form.
  $form['cid'] = array('#type' => 'value', '#value' => $comment->cid);
  return confirm_form(
    $form,
    t('Are you sure you want to delete the comment %title?', array('%title' => $comment->subject)),
    'node/' . $comment->nid,
    t('Any replies to this comment will be lost. This action cannot be undone.'),
    t('Delete'),
    t('Cancel'),
    'comment_confirm_delete');
}

/**
 * Process comment_confirm_delete form submissions.
 */
function comment_confirm_delete_submit($form, &$form_state) {
  $comment = $form['#comment'];
  // Delete the comment and its replies.
  comment_delete($comment->cid);
  drupal_set_message(t('The comment and all its replies have been deleted.'));
  watchdog('content', 'Deleted comment @cid and its replies.', array('@cid' => $comment->cid));
  // Clear the cache so an anonymous user sees that his comment was deleted.
  cache_clear_all();

  $form_state['redirect'] = "node/$comment->nid";
}
