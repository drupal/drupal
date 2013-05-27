<?php

/**
 * @file
 * Content administration and module settings user interface.
 */

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Language\Language;

/**
 * Page callback: Form constructor for the permission rebuild confirmation form.
 *
 * @return array
 *   An array as expected by drupal_render().
 *
 * @see node_configure_rebuild_confirm_submit()
 * @see node_menu()
 * @ingroup forms
 */
function node_configure_rebuild_confirm() {
  return confirm_form(array(), t('Are you sure you want to rebuild the permissions on site content?'),
                  'admin/reports/status', t('This action rebuilds all permissions on site content, and may be a lengthy process. This action cannot be undone.'), t('Rebuild permissions'), t('Cancel'));
}

/**
 * Form submission handler for node_configure_rebuild_confirm().
 */
function node_configure_rebuild_confirm_submit($form, &$form_state) {
  node_access_rebuild(TRUE);
  $form_state['redirect'] = 'admin/reports/status';
}

/**
 * Implements hook_node_operations().
 */
function node_node_operations() {
  $operations = array(
    'publish' => array(
      'label' => t('Publish selected content'),
      'callback' => 'node_mass_update',
      'callback arguments' => array('updates' => array('status' => NODE_PUBLISHED)),
    ),
    'unpublish' => array(
      'label' => t('Unpublish selected content'),
      'callback' => 'node_mass_update',
      'callback arguments' => array('updates' => array('status' => NODE_NOT_PUBLISHED)),
    ),
    'promote' => array(
      'label' => t('Promote selected content to front page'),
      'callback' => 'node_mass_update',
      'callback arguments' => array('updates' => array('status' => NODE_PUBLISHED, 'promote' => NODE_PROMOTED)),
    ),
    'demote' => array(
      'label' => t('Demote selected content from front page'),
      'callback' => 'node_mass_update',
      'callback arguments' => array('updates' => array('promote' => NODE_NOT_PROMOTED)),
    ),
    'sticky' => array(
      'label' => t('Make selected content sticky'),
      'callback' => 'node_mass_update',
      'callback arguments' => array('updates' => array('status' => NODE_PUBLISHED, 'sticky' => NODE_STICKY)),
    ),
    'unsticky' => array(
      'label' => t('Make selected content not sticky'),
      'callback' => 'node_mass_update',
      'callback arguments' => array('updates' => array('sticky' => NODE_NOT_STICKY)),
    ),
    'delete' => array(
      'label' => t('Delete selected content'),
      'callback' => NULL,
    ),
  );
  return $operations;
}

/**
 * Lists node administration filters that can be applied.
 *
 * @return
 *   An associative array of filters.
 */
function node_filters() {
  // Regular filters
  $filters['status'] = array(
    'title' => t('status'),
    'options' => array(
      '[any]' => t('any'),
      'status-1' => t('published'),
      'status-0' => t('not published'),
      'promote-1' => t('promoted'),
      'promote-0' => t('not promoted'),
      'sticky-1' => t('sticky'),
      'sticky-0' => t('not sticky'),
    ),
  );
  // Include translation states if we have this module enabled
  if (module_exists('translation')) {
    $filters['status']['options'] += array(
      'translate-0' => t('Up to date translation'),
      'translate-1' => t('Outdated translation'),
    );
  }

  $filters['type'] = array(
    'title' => t('type'),
    'options' => array(
      '[any]' => t('any'),
    ) + node_type_get_names(),
  );

  // Language filter if language support is present.
  if (language_multilingual()) {
    $languages = language_list(Language::STATE_ALL);
    foreach ($languages as $langcode => $language) {
      // Make locked languages appear special in the list.
      $language_options[$langcode] = $language->locked ? t('- @name -', array('@name' => $language->name)) : $language->name;
    }
    $filters['langcode'] = array(
      'title' => t('language'),
      'options' => array(
        '[any]' => t('- Any -'),
      ) + $language_options,
    );
  }
  return $filters;
}

/**
 * Applies filters for the node administration overview based on session.
 *
 * @param Drupal\Core\Database\Query\SelectInterface $query
 *   A SelectQuery to which the filters should be applied.
 */
function node_build_filter_query(SelectInterface $query) {
  // Build query
  $filter_data = isset($_SESSION['node_overview_filter']) ? $_SESSION['node_overview_filter'] : array();
  foreach ($filter_data as $index => $filter) {
    list($key, $value) = $filter;
    switch ($key) {
      case 'status':
        // Note: no exploitable hole as $key/$value have already been checked when submitted
        list($key, $value) = explode('-', $value, 2);
      case 'type':
      case 'langcode':
        $query->condition('n.' . $key, $value);
        break;
    }
  }
}

/**
 * Returns the node administration filters form array to node_admin_content().
 *
 * @see node_admin_nodes()
 * @see node_admin_nodes_submit()
 * @see node_admin_nodes_validate()
 * @see node_filter_form_submit()
 * @see node_multiple_delete_confirm()
 * @see node_multiple_delete_confirm_submit()
 *
 * @ingroup forms
 */
function node_filter_form() {
  $session = isset($_SESSION['node_overview_filter']) ? $_SESSION['node_overview_filter'] : array();
  $filters = node_filters();

  $i = 0;
  $form['filters'] = array(
    '#type' => 'details',
    '#title' => t('Show only items where'),
    '#theme' => 'exposed_filters__node',
  );
  foreach ($session as $filter) {
    list($type, $value) = $filter;
    if ($type == 'term') {
      // Load term name from DB rather than search and parse options array.
      $value = module_invoke('taxonomy', 'term_load', $value);
      $value = $value->name;
    }
    elseif ($type == 'langcode') {
      $value = language_name($value);
    }
    else {
      $value = $filters[$type]['options'][$value];
    }
    $t_args = array('%property' => $filters[$type]['title'], '%value' => $value);
    if ($i++) {
      $form['filters']['current'][] = array('#markup' => t('and where %property is %value', $t_args));
    }
    else {
      $form['filters']['current'][] = array('#markup' => t('where %property is %value', $t_args));
    }
    if (in_array($type, array('type', 'langcode'))) {
      // Remove the option if it is already being filtered on.
      unset($filters[$type]);
    }
  }

  $form['filters']['status'] = array(
    '#type' => 'container',
    '#attributes' => array('class' => array('clearfix')),
    '#prefix' => ($i ? '<div class="additional-filters">' . t('and where') . '</div>' : ''),
  );
  $form['filters']['status']['filters'] = array(
    '#type' => 'container',
    '#attributes' => array('class' => array('filters')),
  );
  foreach ($filters as $key => $filter) {
    $form['filters']['status']['filters'][$key] = array(
      '#type' => 'select',
      '#options' => $filter['options'],
      '#title' => $filter['title'],
      '#default_value' => '[any]',
    );
  }

  $form['filters']['status']['actions'] = array(
    '#type' => 'actions',
    '#attributes' => array('class' => array('container-inline')),
  );
  $form['filters']['status']['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => count($session) ? t('Refine') : t('Filter'),
  );
  if (count($session)) {
    $form['filters']['status']['actions']['undo'] = array('#type' => 'submit', '#value' => t('Undo'));
    $form['filters']['status']['actions']['reset'] = array('#type' => 'submit', '#value' => t('Reset'));
  }

  $form['#attached']['library'][] = array('system', 'drupal.form');

  return $form;
}

/**
 * Form submission handler for node_filter_form().
 *
 * @see node_admin_content()
 * @see node_admin_nodes()
 * @see node_admin_nodes_submit()
 * @see node_admin_nodes_validate()
 * @see node_filter_form()
 * @see node_multiple_delete_confirm()
 * @see node_multiple_delete_confirm_submit()
 */
function node_filter_form_submit($form, &$form_state) {
  $filters = node_filters();
  switch ($form_state['values']['op']) {
    case t('Filter'):
    case t('Refine'):
      // Apply every filter that has a choice selected other than 'any'.
      foreach ($filters as $filter => $options) {
        if (isset($form_state['values'][$filter]) && $form_state['values'][$filter] != '[any]') {
          $_SESSION['node_overview_filter'][] = array($filter, $form_state['values'][$filter]);
        }
      }
      break;
    case t('Undo'):
      array_pop($_SESSION['node_overview_filter']);
      break;
    case t('Reset'):
      $_SESSION['node_overview_filter'] = array();
      break;
  }
}

/**
 * Updates all nodes in the passed-in array with the passed-in field values.
 *
 * IMPORTANT NOTE: This function is intended to work when called from a form
 * submission handler. Calling it outside of the form submission process may not
 * work correctly.
 *
 * @param array $nodes
 *   Array of node nids to update.
 * @param array $updates
 *   Array of key/value pairs with node field names and the value to update that
 *   field to.
 * @param string $langcode
 *   (optional) The language updates should be applied to. If none is specified
 *   all available languages are processed.
 */
function node_mass_update($nodes, $updates, $langcode = NULL) {
  // We use batch processing to prevent timeout when updating a large number
  // of nodes.
  if (count($nodes) > 10) {
    $batch = array(
      'operations' => array(
        array('_node_mass_update_batch_process', array($nodes, $updates, $langcode))
      ),
      'finished' => '_node_mass_update_batch_finished',
      'title' => t('Processing'),
      // We use a single multi-pass operation, so the default
      // 'Remaining x of y operations' message will be confusing here.
      'progress_message' => '',
      'error_message' => t('The update has encountered an error.'),
      // The operations do not live in the .module file, so we need to
      // tell the batch engine which file to load before calling them.
      'file' => drupal_get_path('module', 'node') . '/node.admin.inc',
    );
    batch_set($batch);
  }
  else {
    foreach ($nodes as $nid) {
      _node_mass_update_helper($nid, $updates, $langcode);
    }
    drupal_set_message(t('The update has been performed.'));
  }
}

/**
 * Updates individual nodes when fewer than 10 are queued.
 *
 * @param $nid
 *   ID of node to update.
 * @param $updates
 *   Associative array of updates.
 * @param string $langcode
 *   (optional) The language updates should be applied to. If none is specified
 *   all available languages are processed.
 *
 * @return object
 *   An updated node object.
 *
 * @see node_mass_update()
 */
function _node_mass_update_helper($nid, $updates, $langcode = NULL) {
  $node = node_load($nid, TRUE);
  $langcodes = isset($langcode) ? array($langcode) : array_keys($node->getTranslationLanguages());
  // For efficiency manually save the original node before applying any changes.
  $node->original = clone $node;
  foreach ($langcodes as $langcode) {
    foreach ($updates as $name => $value) {
      $node->getTranslation($langcode, FALSE)->$name = $value;
    }
  }
  $node->save();
  return $node;
}

/**
 * Executes a batch operation for node_mass_update().
 *
 * @param array $nodes
 *   An array of node IDs.
 * @param array $updates
 *   Associative array of updates.
 * @param array $context
 *   An array of contextual key/values.
 */
function _node_mass_update_batch_process($nodes, $updates, &$context) {
  if (!isset($context['sandbox']['progress'])) {
    $context['sandbox']['progress'] = 0;
    $context['sandbox']['max'] = count($nodes);
    $context['sandbox']['nodes'] = $nodes;
  }

  // Process nodes by groups of 5.
  $count = min(5, count($context['sandbox']['nodes']));
  for ($i = 1; $i <= $count; $i++) {
    // For each nid, load the node, reset the values, and save it.
    $nid = array_shift($context['sandbox']['nodes']);
    $node = _node_mass_update_helper($nid, $updates);

    // Store result for post-processing in the finished callback.
    $context['results'][] = l($node->label(), 'node/' . $node->nid);

    // Update our progress information.
    $context['sandbox']['progress']++;
  }

  // Inform the batch engine that we are not finished,
  // and provide an estimation of the completion level we reached.
  if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
    $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
  }
}

/**
 * Reports the 'finished' status of batch operation for node_mass_update().
 *
 * @param bool $success
 *   A boolean indicating whether the batch mass update operation successfully
 *   concluded.
 * @param int $results
 *   The number of nodes updated via the batch mode process.
 * @param array $operations
 *   An array of function calls (not used in this function).
 */
function _node_mass_update_batch_finished($success, $results, $operations) {
  if ($success) {
    drupal_set_message(t('The update has been performed.'));
  }
  else {
    drupal_set_message(t('An error occurred and processing did not complete.'), 'error');
    $message = format_plural(count($results), '1 item successfully processed:', '@count items successfully processed:');
    $message .= theme('item_list', array('items' => $results));
    drupal_set_message($message);
  }
}

/**
 * Page callback: Form constructor for the content administration form.
 *
 * @see node_admin_nodes()
 * @see node_admin_nodes_submit()
 * @see node_admin_nodes_validate()
 * @see node_filter_form()
 * @see node_filter_form_submit()
 * @see node_menu()
 * @see node_multiple_delete_confirm()
 * @see node_multiple_delete_confirm_submit()
 * @ingroup forms
 */
function node_admin_content($form, $form_state) {
  if (isset($form_state['values']['operation']) && $form_state['values']['operation'] == 'delete') {
    return node_multiple_delete_confirm($form, $form_state, array_filter($form_state['values']['nodes']));
  }
  $form['filter'] = node_filter_form();
  $form['#submit'][] = 'node_filter_form_submit';
  $form['admin'] = node_admin_nodes();

  return $form;
}

/**
 * Returns the admin form object to node_admin_content().
 *
 * @see node_admin_nodes_submit()
 * @see node_filter_form()
 * @see node_filter_form_submit()
 * @see node_multiple_delete_confirm()
 * @see node_multiple_delete_confirm_submit()
 *
 * @ingroup forms
 */
function node_admin_nodes() {
  $admin_access = user_access('administer nodes');

  // Build the 'Update options' form.
  $form['options'] = array(
    '#type' => 'details',
    '#title' => t('Update options'),
    '#attributes' => array('class' => array('container-inline')),
    '#access' => $admin_access,
  );
  $options = array();
  foreach (module_invoke_all('node_operations') as $operation => $array) {
    $options[$operation] = $array['label'];
  }
  $form['options']['operation'] = array(
    '#type' => 'select',
    '#title' => t('Operation'),
    '#title_display' => 'invisible',
    '#options' => $options,
    '#default_value' => 'approve',
  );
  $form['options']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Update'),
    '#tableselect' => TRUE,
    '#submit' => array('node_admin_nodes_submit'),
  );

  // Enable language column and filter if multiple languages are enabled.
  $multilingual = language_multilingual();

  // Build the sortable table header.
  $header = array(
    'title' => array(
      'data' => t('Title'),
      'field' => 'n.title',
    ),
    'type' => array(
      'data' => t('Content type'),
      'field' => 'n.type',
      'class' => array(RESPONSIVE_PRIORITY_MEDIUM),
    ),
    'author' => array(
      'data' => t('Author'),
      'class' => array(RESPONSIVE_PRIORITY_LOW),
    ),
    'status' => array(
      'data' => t('Status'),
      'field' => 'n.status',
    ),
    'changed' => array(
      'data' => t('Updated'),
      'field' => 'n.changed',
      'sort' => 'desc',
      'class' => array(RESPONSIVE_PRIORITY_LOW)
    ,)
  );
  if ($multilingual) {
    $header['language_name'] = array('data' => t('Language'), 'field' => 'n.langcode', 'class' => array(RESPONSIVE_PRIORITY_LOW));
  }
  $header['operations'] = array('data' => t('Operations'));

  $query = db_select('node_field_data', 'n')
    ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
    ->extend('Drupal\Core\Database\Query\TableSortExtender');
  node_build_filter_query($query);

  if (!user_access('bypass node access')) {
    // If the user is able to view their own unpublished nodes, allow them
    // to see these in addition to published nodes. Check that they actually
    // have some unpublished nodes to view before adding the condition.
    if (user_access('view own unpublished content') && $own_unpublished = db_query('SELECT DISTINCT nid FROM {node_field_data} WHERE uid = :uid AND status = :status', array(':uid' => $GLOBALS['user']->uid, ':status' => 0))->fetchCol()) {
      $query->condition(db_or()
        ->condition('n.status', 1)
        ->condition('n.nid', $own_unpublished, 'IN')
      );
    }
    else {
      // If not, restrict the query to published nodes.
      $query->condition('n.status', 1);
    }
  }
  $nids = $query
    ->distinct()
    ->fields('n', array('nid'))
    ->limit(50)
    ->orderByHeader($header)
    ->addTag('node_access')
    ->execute()
    ->fetchCol();
  $nodes = node_load_multiple($nids);

  // Prepare the list of nodes.
  $languages = language_list(Language::STATE_ALL);
  $destination = drupal_get_destination();
  $form['nodes'] = array(
    '#type' => 'table',
    '#header' => $header,
    '#empty' => t('No content available.'),
  );
  foreach ($nodes as $node) {
    $l_options = $node->langcode != Language::LANGCODE_NOT_SPECIFIED && isset($languages[$node->langcode]) ? array('language' => $languages[$node->langcode]) : array();
    $form['nodes'][$node->nid]['title'] = array(
      '#type' => 'link',
      '#title' => $node->label(),
      '#href' => 'node/' . $node->nid,
      '#options' => $l_options,
      '#suffix' => ' ' . theme('mark', array('type' => node_mark($node->nid, $node->changed))),
    );
    $form['nodes'][$node->nid]['type'] = array(
      '#markup' => check_plain(node_get_type_label($node)),
    );
    $form['nodes'][$node->nid]['author'] = array(
      '#theme' => 'username',
      '#account' => $node,
    );
    $form['nodes'][$node->nid]['status'] = array(
      '#markup' => $node->status ? t('published') : t('not published'),
    );
    $form['nodes'][$node->nid]['changed'] = array(
      '#markup' => format_date($node->changed, 'short'),
    );
    if ($multilingual) {
      $form['nodes'][$node->nid]['language_name'] = array(
        '#markup' => language_name($node->langcode),
      );
    }

    // Build a list of all the accessible operations for the current node.
    $operations = array();
    if (node_access('update', $node)) {
      $operations['edit'] = array(
        'title' => t('Edit'),
        'href' => 'node/' . $node->nid . '/edit',
        'query' => $destination,
      );
    }
    if (node_access('delete', $node)) {
      $operations['delete'] = array(
        'title' => t('Delete'),
        'href' => 'node/' . $node->nid . '/delete',
        'query' => $destination,
      );
    }
    if ($node->isTranslatable()) {
      $operations['translate'] = array(
        'title' => t('Translate'),
        'href' => 'node/' . $node->nid . '/translations',
        'query' => $destination,
      );
    }
    $form['nodes'][$node->nid]['operations'] = array();
    if (count($operations) > 1) {
      // Render an unordered list of operations links.
      $form['nodes'][$node->nid]['operations'] = array(
        '#type' => 'operations',
        '#subtype' => 'node',
        '#links' => $operations,
      );
    }
    elseif (!empty($operations)) {
      // Render the first and only operation as a link.
      $link = reset($operations);
      $form['nodes'][$node->nid]['operations'] = array(
        '#type' => 'link',
        '#title' => $link['title'],
        '#href' => $link['href'],
        '#options' => array('query' => $link['query']),
      );
    }
  }

  // Only use a tableselect when the current user is able to perform any
  // operations.
  if ($admin_access) {
    $form['nodes']['#tableselect'] = TRUE;
  }

  $form['pager'] = array('#theme' => 'pager');
  return $form;
}

/**
 * Form submission handler for node_admin_nodes().
 *
 * Executes the chosen 'Update option' on the selected nodes.
 *
 * @see node_admin_nodes()
 * @see node_admin_nodes_validate()
 * @see node_filter_form()
 * @see node_filter_form_submit()
 * @see node_multiple_delete_confirm()
 * @see node_multiple_delete_confirm_submit()
 */
function node_admin_nodes_submit($form, &$form_state) {
  $operations = module_invoke_all('node_operations');
  $operation = $operations[$form_state['values']['operation']];
  // Filter out unchecked nodes
  $nodes = array_filter($form_state['values']['nodes']);
  if ($function = $operation['callback']) {
    // Add in callback arguments if present.
    if (isset($operation['callback arguments'])) {
      $args = array_merge(array($nodes), $operation['callback arguments']);
    }
    else {
      $args = array($nodes);
    }
    call_user_func_array($function, $args);

    cache_invalidate_tags(array('content' => TRUE));
  }
  else {
    // We need to rebuild the form to go to a second step. For example, to
    // show the confirmation form for the deletion of nodes.
    $form_state['rebuild'] = TRUE;
  }
}

/**
 * Multiple node deletion confirmation form for node_admin_content().
 *
 * @see node_admin_nodes()
 * @see node_admin_nodes_submit()
 * @see node_admin_nodes_validate()
 * @see node_filter_form()
 * @see node_filter_form_submit()
 * @see node_multiple_delete_confirm_submit()
 * @ingroup forms
 */
function node_multiple_delete_confirm($form, &$form_state, $nodes) {
  $form['nodes'] = array('#prefix' => '<ul>', '#suffix' => '</ul>', '#tree' => TRUE);
  $node_entities = node_load_multiple(array_keys($nodes));
  // array_filter returns only elements with TRUE values
  foreach ($nodes as $nid => $value) {
    $form['nodes'][$nid] = array(
      '#type' => 'hidden',
      '#value' => $nid,
      '#prefix' => '<li>',
      '#suffix' => check_plain($node_entities[$nid]->label()) . "</li>\n",
    );
  }
  $form['operation'] = array('#type' => 'hidden', '#value' => 'delete');
  $form['#submit'][] = 'node_multiple_delete_confirm_submit';
  $confirm_question = format_plural(count($nodes),
                                  'Are you sure you want to delete this item?',
                                  'Are you sure you want to delete these items?');
  return confirm_form($form,
                    $confirm_question,
                    'admin/content', t('This action cannot be undone.'),
                    t('Delete'), t('Cancel'));
}

/**
 * Form submission handler for node_multiple_delete_confirm().
 *
 * @see node_admin_nodes()
 * @see node_admin_nodes_submit()
 * @see node_admin_nodes_validate()
 * @see node_filter_form()
 * @see node_filter_form_submit()
 * @see node_multiple_delete_confirm()
 */
function node_multiple_delete_confirm_submit($form, &$form_state) {
  if ($form_state['values']['confirm']) {
    node_delete_multiple(array_keys($form_state['values']['nodes']));
    $count = count($form_state['values']['nodes']);
    watchdog('content', 'Deleted @count posts.', array('@count' => $count));
    drupal_set_message(format_plural($count, 'Deleted 1 post.', 'Deleted @count posts.'));
  }
  $form_state['redirect'] = 'admin/content';
}
