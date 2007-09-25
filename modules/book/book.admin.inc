<?php
// $Id: book.admin.inc,v 1.2 2007/09/25 12:50:34 goba Exp $

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
    $form = array();

    $form['#node'] = $node;
    $form['table'] = _book_admin_table($node);
    $form['save'] = array(
      '#type' => 'submit',
      '#value' => t('Save book pages'),
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
      $node->title = $row['title'];
      $node->book['link_title'] = $row['title'];
      $node->book['weight'] = $row['weight'];
      $node->revision = 1;

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
  foreach ($tree as $data) {
    $form[] = array(
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
    $rows[] = array(
      '<div style="padding-left: '. (25 * $form[$key]['depth']['#value']) .'px;">'. drupal_render($form[$key]['title']) .'</div>',
      drupal_render($form[$key]['weight']),
      l(t('view'), $href),
      $access ? l(t('edit'), 'node/'. $nid .'/edit', array('query' => $destination)) : '&nbsp',
      $access ? l(t('delete'), 'node/'. $nid .'/delete', array('query' => $destination) )  : '&nbsp',
    );
  }

  return theme('table', $header, $rows);
}
