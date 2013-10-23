<?php

/**
 * @file
 * Admin page callbacks for the Comment module.
 */

/**
 * Page callback: Presents an administrative comment listing.
 *
 * @param $type
 *   The type of the overview form ('approval' or 'new'). See
 *   comment_admin_overview() for details.
 *
 * @see comment_menu()
 * @see comment_multiple_delete_confirm()
 *
 * @deprecated Use \Drupal\comment\Controller\CommentController::adminPage()
 */
function comment_admin($type = 'new') {
  $request = \Drupal::request();
  $edit = $request->request->all();

  if (isset($edit['operation']) && ($edit['operation'] == 'delete') && isset($edit['comments']) && $edit['comments']) {
    return drupal_get_form('Drupal\comment\Form\ConfirmDeleteMultiple');
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
  if (\Drupal::moduleHandler()->moduleExists('node')) {
    // Special case to ensure node access works.
    $query->leftJoin('node_field_data', 'n', "n.nid = c.entity_id AND c.entity_type = 'node'");
    $query->addTag('node_access');
  }
  $result = $query
    ->fields('c', array('cid', 'subject', 'name', 'changed', 'entity_id', 'entity_type', 'field_id'))
    ->condition('c.status', $status)
    ->limit(50)
    ->orderByHeader($header)
    ->execute();

  $cids = array();
  $entity_ids = array();
  $entities = array();

  // We collect entities grouped by entity_type so we can load them and use
  // their labels.
  foreach ($result as $row) {
    $entity_ids[$row->entity_type][] = $row->entity_id;
    $cids[] = $row->cid;
  }
  // Ensure all entities are statically cached so that we do not have to load
  // them individually when getting their labels below.
  foreach ($entity_ids as $entity_type => $ids) {
    $entities[$entity_type] = entity_load_multiple($entity_type, $ids);
  }
  $comments = entity_load_multiple('comment', $cids);

  // Build a table listing the appropriate comments.
  $options = array();
  $destination = drupal_get_destination();

  foreach ($comments as $comment) {
    // Use the first entity label.
    $entity = $entities[$comment->entity_type->value][$comment->entity_id->value];
    $entity_uri = $entity->uri();
    // Remove the first node title from the node_titles array and attach to
    // the comment.
    $body = '';
    if (!empty($comment->comment_body->value)) {
      $body = $comment->comment_body->value;
    }
    $options[$comment->id()] = array(
      'title' => array('data' => array('#title' => $comment->subject->value ?: $comment->id())),
      'subject' => array(
        'data' => array(
          '#type' => 'link',
          '#title' => $comment->subject->value,
          '#href' => 'comment/' . $comment->id(),
          '#options' => array('attributes' => array('title' => truncate_utf8($body, 128)), 'fragment' => 'comment-' . $comment->id()),
        ),
      ),
      'author' => array(
        'data' => array(
          '#theme' => 'username',
          '#account' => comment_prepare_author($comment),
        ),
      ),
      'posted_in' => array(
        'data' => array(
          '#type' => 'link',
          '#title' => $entity->label(),
          '#href' => $entity_uri['path'],
          '#options' => $entity_uri['options'],
          '#access' => $entity->access('view'),
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
    foreach ($cids as $value) {
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
