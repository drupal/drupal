<?php

/**
 * @file
 * Admin page callbacks for the Comment module.
 */

use Drupal\comment\Entity\Comment;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Page callback: Presents an administrative comment listing.
 *
 * @param $type
 *   The type of the overview form ('approval' or 'new'). See
 *   comment_admin_overview() for details.
 *
 * @see comment_menu()
 * @see comment_multiple_delete_confirm()
 */
function comment_admin($type = 'new') {
  $edit = Drupal::request()->request->all();

  if (isset($edit['operation']) && ($edit['operation'] == 'delete') && isset($edit['comments']) && $edit['comments']) {
    return drupal_get_form('comment_multiple_delete_confirm');
  }
  else {
    return drupal_get_form('comment_admin_overview', $type);
  }
}

/**
 * Form constructor for the comment overview administration form.
 *
 * @param $arg
 *   The type of overview form ('approval' or 'new').
 *
 * @ingroup forms
 * @see comment_admin()
 * @see comment_admin_overview_validate()
 * @see comment_admin_overview_submit()
 * @see theme_comment_admin_overview()
 */
function comment_admin_overview($form, &$form_state, $arg) {
  // Build an 'Update options' form.
  $form['options'] = array(
    '#type' => 'details',
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
    '#title' => t('Action'),
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
    'author' => array('data' => t('Author'), 'field' => 'name', 'class' => array(RESPONSIVE_PRIORITY_MEDIUM)),
    'posted_in' => array('data' => t('Posted in'), 'field' => 'node_title', 'class' => array(RESPONSIVE_PRIORITY_LOW)),
    'changed' => array('data' => t('Updated'), 'field' => 'c.changed', 'sort' => 'desc', 'class' => array(RESPONSIVE_PRIORITY_LOW)),
    'operations' => t('Operations'),
  );

  $query = db_select('comment', 'c')
    ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
    ->extend('Drupal\Core\Database\Query\TableSortExtender');
  $query->join('node_field_data', 'n', 'n.nid = c.nid');
  $query->addTag('node_access');
  $result = $query
    ->fields('c', array('cid', 'nid', 'subject', 'name', 'changed'))
    ->condition('c.status', $status)
    ->limit(50)
    ->orderByHeader($header)
    ->execute();

  $nids = array();
  $cids = array();

  // We collect a sorted list of node_titles during the query to attach to the
  // comments later.
  foreach ($result as $row) {
    $nids[] = $row->nid;
    $cids[] = $row->cid;
  }
  // Ensure all nodes are statically cached so that we do not have to load them
  // individually when getting their labels below.
  node_load_multiple($nids);
  $comments = comment_load_multiple($cids);

  // Build a table listing the appropriate comments.
  $options = array();
  $destination = drupal_get_destination();

  foreach ($comments as $comment) {
    // Remove the first node title from the node_titles array and attach to
    // the comment.
    $node_title = $comment->nid->entity->label();
    $username = array(
      '#theme' => 'username',
      '#account' => comment_prepare_author($comment),
    );
    $options[$comment->id()] = array(
      'subject' => array(
        'data' => array(
          '#type' => 'link',
          '#title' => $comment->subject->value,
          '#href' => 'comment/' . $comment->id(),
          '#options' => array('attributes' => array('title' => truncate_utf8($comment->comment_body->value, 128)), 'fragment' => 'comment-' . $comment->id()),
        ),
      ),
      'author' => drupal_render($username),
      'posted_in' => array(
        'data' => array(
          '#type' => 'link',
          '#title' => $node_title,
          '#href' => 'node/' . $comment->nid->target_id,
        ),
      ),
      'changed' => format_date($comment->changed->value, 'short'),
    );
    $links = array();
    $links['edit'] = array(
      'title' => t('edit'),
      'href' => 'comment/' . $comment->id() . '/edit',
      'query' => $destination,
    );
    if (module_invoke('content_translation', 'translate_access', $comment)) {
      $links['translate'] = array(
        'title' => t('translate'),
        'href' => 'comment/' . $comment->id() . '/translations',
        'query' => $destination,
      );
    }
    $options[$comment->id()]['operations']['data'] = array(
      '#type' => 'operations',
      '#links' => $links,
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
 * Form validation handler for comment_admin_overview().
 *
 * @see comment_admin_overview_submit()
 */
function comment_admin_overview_validate($form, &$form_state) {
  $form_state['values']['comments'] = array_diff($form_state['values']['comments'], array(0));
  // We can't execute any 'Update options' if no comments were selected.
  if (count($form_state['values']['comments']) == 0) {
    form_set_error('', t('Select one or more comments to perform the update on.'));
  }
}

/**
 * Form submission handler for comment_admin_overview().
 *
 * Executes the chosen 'Update option' on the selected comments, such as
 * publishing, unpublishing or deleting.
 *
 * @see comment_admin_overview_validate()
 */
function comment_admin_overview_submit($form, &$form_state) {
  $operation = $form_state['values']['operation'];
  $cids = $form_state['values']['comments'];

  if ($operation == 'delete') {
    entity_delete_multiple('comment', $cids);
  }
  else {
    foreach ($cids as $cid => $value) {
      $comment = comment_load($value);

      if ($operation == 'unpublish') {
        $comment->status->value = COMMENT_NOT_PUBLISHED;
      }
      elseif ($operation == 'publish') {
        $comment->status->value = COMMENT_PUBLISHED;
      }
      $comment->save();
    }
  }
  drupal_set_message(t('The update has been performed.'));
  $form_state['redirect'] = 'admin/content/comment';
  cache_invalidate_tags(array('content' => TRUE));
}

/**
 * Form constructor for the confirmation form for bulk comment deletion.
 *
 * @ingroup forms
 * @see comment_admin()
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
    if (is_object($comment) && is_numeric($comment->id())) {
      $subject = db_query('SELECT subject FROM {comment} WHERE cid = :cid', array(':cid' => $cid))->fetchField();
      $form['comments'][$cid] = array('#type' => 'hidden', '#value' => $cid, '#prefix' => '<li>', '#suffix' => check_plain($subject) . '</li>');
      $comment_counter++;
    }
  }
  $form['operation'] = array('#type' => 'hidden', '#value' => 'delete');

  if (!$comment_counter) {
    drupal_set_message(t('There do not appear to be any comments to delete, or your selected comment was deleted by another administrator.'));
    return new RedirectResponse(url('admin/content/comment', array('absolute' => TRUE)));
  }
  else {
    return confirm_form($form,
                        t('Are you sure you want to delete these comments and all their children?'),
                        'admin/content/comment', t('This action cannot be undone.'),
                        t('Delete comments'), t('Cancel'));
  }
}

/**
 * Form submission handler for comment_multiple_delete_confirm().
 */
function comment_multiple_delete_confirm_submit($form, &$form_state) {
  if ($form_state['values']['confirm']) {
    entity_delete_multiple('comment', array_keys($form_state['values']['comments']));
    cache_invalidate_tags(array('content' => TRUE));
    $count = count($form_state['values']['comments']);
    watchdog('content', 'Deleted @count comments.', array('@count' => $count));
    drupal_set_message(format_plural($count, 'Deleted 1 comment.', 'Deleted @count comments.'));
  }
  $form_state['redirect'] = 'admin/content/comment';
}

/**
 * Page callback: Shows a confirmation page for comment deletions.
 *
 * @param \Drupal\comment\Entity\Comment $comment
 *   The comment entity that is about to be deleted.
 *
 * @see comment_menu()
 * @see comment_confirm_delete()
 */
function comment_confirm_delete_page(Comment $comment) {
  return drupal_get_form('comment_confirm_delete', $comment);
}

/**
 * Form constructor for the confirmation form for comment deletion.
 *
 * @param Drupal\comment\Comment $comment
 *   The comment that is about to be deleted.
 *
 * @ingroup forms
 * @see comment_confirm_delete_page()
 * @see comment_confirm_delete_submit()
 * @see confirm_form()
 */
function comment_confirm_delete($form, &$form_state, Comment $comment) {
  $form_state['comment'] = $comment;
  // Always provide entity id in the same form key as in the entity edit form.
  $form['cid'] = array('#type' => 'value', '#value' => $comment->id());
  return confirm_form(
    $form,
    t('Are you sure you want to delete the comment %title?', array('%title' => $comment->subject->value)),
    'node/' . $comment->nid->target_id,
    t('Any replies to this comment will be lost. This action cannot be undone.'),
    t('Delete'),
    t('Cancel'),
    'comment_confirm_delete');
}

/**
 * Form submission handler for comment_confirm_delete().
 */
function comment_confirm_delete_submit($form, &$form_state) {
  $comment = $form_state['comment'];
  // Delete the comment and its replies.
  $comment->delete();
  drupal_set_message(t('The comment and all its replies have been deleted.'));
  watchdog('content', 'Deleted comment @cid and its replies.', array('@cid' => $comment->id()));
  // Clear the cache so an anonymous user sees that his comment was deleted.
  cache_invalidate_tags(array('content' => TRUE));

  $form_state['redirect'] = "node/{$comment->nid->target_id}";
}
