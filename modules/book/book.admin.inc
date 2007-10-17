<?php
// $Id: book.admin.inc,v 1.5 2007/10/17 12:34:16 dries Exp $

/**
 * @file
 * Admin page callbacks for the book module.
 */

/**
 * Returns an administrative overview of all books.
 */
function book_admin_overview() {
  $rows = array();
  foreach (book_get_books() as $book) {
    $rows[] = array(l($book['title'], $book['href'], $book['options']), l(t('edit order and titles'), "admin/content/book/". $book['nid']));
  }
  $headers = array(t('Book'), t('Operations'));

  return theme('table', $headers, $rows);
}

/**
 * Builds and returns the book settings form.
 *
 * @see book_admin_settings_validate()
 *
 * @ingroup forms
 */
function book_admin_settings() {
  $types = node_get_types('names');
  $form['book_allowed_types'] = array(
    '#type' => 'checkboxes',
    '#title' => t('Allowed book outline types'),
    '#default_value' => variable_get('book_allowed_types', array('book')),
    '#options' => $types,
    '#description' => t('Select content types which users with the %add-perm permission will be allowed to add to the book hierarchy. Users with the %outline-perm permission can add all content types.', array('%add-perm' => t('add content to books'),  '%outline-perm' => t('administer book outlines'))),
    '#required' => TRUE,
  );
  $form['book_child_type'] = array(
    '#type' => 'radios',
    '#title' => t('Default child page type'),
    '#default_value' => variable_get('book_child_type', 'book'),
    '#options' => $types,
    '#description' => t('The content type for the %add-child link must be one of those selected as an allowed book outline type.', array('%add-child' => t('Add child page'))),
    '#required' => TRUE,
  );
  $form['array_filter'] = array('#type' => 'value', '#value' => TRUE);
  $form['#validate'][] = 'book_admin_settings_validate';
  return system_settings_form($form);
}

/**
 * Validate the book settings form.
 *
 * @see book_admin_settings()
 */
function book_admin_settings_validate($form, &$form_state) {
  $child_type = $form_state['values']['book_child_type'];
  if (empty($form_state['values']['book_allowed_types'][$child_type])) {
    form_set_error('book_child_type', t('The content type for the %add-child link must be one of those selected as an allowed book outline type.', array('%add-child' => t('Add child page'))));
  }
}

/**
 * Build the form to administrate the hierarchy of a single book.
 *
 * @see book_admin_edit_submit()
 *
 * @ingroup forms.
 */
function book_admin_edit($form_state, $node) {
  drupal_set_title(check_plain($node->title));
  $form = array(
    '#cache' => TRUE,
    '#prefix' => '<div id="book-admin-edit-wrapper">',
    '#suffix' => '</div>',
  );

  $form['#node'] = $node;
  $form['table'] = _book_admin_table($node);
  $form['save'] = array(
    '#type' => 'submit',
    '#value' => t('Save book pages'),
    '#ahah' => array(
      'path' => 'book/js/admin/'. $node->nid,
      'selector' => '#book-admin-edit select',
      'wrapper' => 'book-admin-edit-wrapper',
      'event' => 'change',
      'effect' => 'fade',
    ),
  );
  return $form;
}

/**
 * Handle submission of the book administrative page form.
 *
 * @see book_admin_edit()
 */
function book_admin_edit_submit($form, &$form_state) {
  foreach ($form_state['values']['table'] as $row) {
    $node = node_load($row['nid'], FALSE);

    if ($row['title'] != $node->title || $row['weight'] != $node->book['weight']) {

      // Record changes in node's log message.
      $log_messages = array();
      if ($row['title'] != $node->title) {
        $log_messages[] = t('Title changed from %original to %current.', array('%original' => $node->title, '%current' => $row['title']));
      }
      if ($row['weight'] != $node->book['weight']) {
        $log_messages[] = t('Weight changed from %original to %current.', array('%original' => $node->book['weight'], '%current' => $row['weight']));
      }

      $node->title = $row['title'];
      $node->book['link_title'] = $row['title'];
      $node->book['weight'] = $row['weight'];
      $node->revision = 1;
      $node->log = implode(' ', $log_messages);

      node_save($node);
      watchdog('content', 'book: updated %title.', array('%title' => $node->title), WATCHDOG_NOTICE, l(t('view'), 'node/'. $node->nid));
    }
  }
  // Insure we have the current title - it may have been changed in the form.
  $title = db_result(db_query("SELECT title FROM {node} WHERE nid = %d", $form['#node']->nid));
  drupal_set_message(t('Updated book %title.', array('%title' => $title)));
}

/**
 * Build the table portion of the form for the book administration page.
 *
 * @see book_admin_edit()
 */
function _book_admin_table($node) {
  $form = array(
    '#theme' => 'book_admin_table',
    '#tree' => TRUE,
  );

  $tree = book_menu_subtree_data($node->book);
  _book_admin_table_tree($tree, $form);
  return $form;
}

/**
 * Recursive helper to build the main table in the book administration page form.
 *
 * @see book_admin_edit()
 */
function _book_admin_table_tree($tree, &$form) {
  foreach ($tree as $key => $data) {
    $form[$key] = array(
      'nid' => array('#type' => 'value', '#value' => $data['link']['nid']),
      'depth' => array('#type' => 'value', '#value' => $data['link']['depth']),
      'href' => array('#type' => 'value', '#value' => $data['link']['href']),
      'title' => array(
        '#type' => 'textfield',
        '#default_value' => $data['link']['link_title'],
        '#maxlength' => 255,
      ),
      'weight' => array(
        '#type' => 'weight',
        '#default_value' => $data['link']['weight'],
        '#delta' => 15,
      ),
    );
    if ($data['below']) {
      _book_admin_table_tree($data['below'], $form);
    }
  }

  return $form;
}

/**
 * Theme function for the book administration page form.
 *
 * @ingroup themeable
 */
function theme_book_admin_table($form) {
  $header = array(t('Title'), t('Weight'), array('data' => t('Operations'), 'colspan' => '3'));

  $rows = array();
  $destination = drupal_get_destination();
  $access = user_access('administer nodes');
  foreach (element_children($form) as $key) {
    $nid = $form[$key]['nid']['#value'];
    $href = $form[$key]['href']['#value'];
    $asterisk = (isset($form[$key]['#attributes']['class']) && strpos($form[$key]['#attributes']['class'], 'book-changed') !== FALSE) ? '<span class="warning">*</span>' : '';
    $data = array(
      '<div style="padding-left: '. (25 * $form[$key]['depth']['#value']) .'px;">'. drupal_render($form[$key]['title']) . $asterisk .'</div>',
      drupal_render($form[$key]['weight']),
      l(t('view'), $href),
      $access ? l(t('edit'), 'node/'. $nid .'/edit', array('query' => $destination)) : '&nbsp',
      $access ? l(t('delete'), 'node/'. $nid .'/delete', array('query' => $destination) )  : '&nbsp',
    );
    $row = array('data' => $data);
    if (isset($form[$key]['#attributes'])) {
      $row = array_merge($row, $form[$key]['#attributes']);
    }
    $rows[] = $row;
  }

  return theme('status_messages') . theme('table', $header, $rows);
}

/**
 * Menu callback for updating the book outline form.
 */
function book_admin_js_update() {
  $cid = 'form_'. $_POST['form_build_id'];
  $cache = cache_get($cid, 'cache_form');
  if ($cache) {
    $form = $cache->data;

    $tree = book_menu_subtree_data($form['#node']->book);
    _book_admin_js_update_tree($tree);
    _book_admin_sort_tree($tree);

    // Create the form in the new order.
    $table_form = array();
    _book_admin_table_tree($tree, $table_form);

    // Find the changed element on this request and save the current classes.
    foreach (element_children($form['table']) as $key) {
      if (isset($form['table'][$key]['#attributes'])) {
        $table_form[$key]['#attributes'] = $form['table'][$key]['#attributes'];
      }
      if ($form['table'][$key]['weight']['#default_value'] != $_POST['table'][$key]['weight']) {
        $changed_key = $key;
      }
    }

    // Preserve the order of the new form while merging the previous data.
    $form_order = array_flip(array_keys($table_form)); // Save the form order.
    $form['table'] = array_merge($form['table'], $table_form); // Merge the data.
    $form['table'] = array_merge($form_order, $form['table']); // Put back into the correct order.
    $form['table'][$changed_key]['#attributes']['class'] = 'book-changed';

    cache_set($cid, $form, 'cache_form', $cache->expire);

    // Add the special AHAH class for new content.
    $form['table'][$changed_key]['#attributes']['class'] = isset($form['table'][$changed_key]['#attributes']['class']) ? $form['table'][$changed_key]['#attributes']['class'] .' ahah-new-content' : 'ahah-new-content';

    // Set a message for the user to save the form.
    drupal_set_message(t('Your changes will not be saved until you click the <em>Save book pages</em> button.'), 'warning');

    // Prevent duplicate wrappers.
    unset($form['#prefix'], $form['#suffix']);

    // Render the form.
    $form['#post'] = $_POST;
    $form_state = array('submitted' => FALSE);
    $form = form_builder('book_admin_edit', $form, $form_state);
    $output = drupal_render($form);

    drupal_json(array('status' => TRUE, 'data' => $output));
  }
}

/**
 * Recursive helper to set new form weights to the tree.
 */
function _book_admin_js_update_tree(&$tree) {
  foreach($tree as $key => $subtree) {
    $tree[$key]['link']['weight'] = $_POST['table'][$key]['weight'];
    $tree[$key]['link']['title'] = $_POST['table'][$key]['title'];
    if (!empty($subtree['below'])) {
      _book_admin_js_update_tree($tree[$key]['below']);
    }
  }
}

/**
 * Recursive helper to sort each layer of a book tree by weight.
 */
function _book_admin_sort_tree(&$tree) {
  uasort($tree, '_book_admin_compare');
  foreach($tree as $key => $subtree) {
    if (!empty($tree[$key]['below'])) {
      _book_admin_sort_tree($tree[$key]['below']);
    }
  }
}

/**
 * Used by uasort() in _book_admin_sort_tree() to compare items in a book tree.
 */
function _book_admin_compare($a, $b) {
  $weight = $a['link']['weight'] - $b['link']['weight'];
  if ($weight) {
    return $weight;
  }
  return strncmp($a['link']['title'], $b['link']['title']);
}
