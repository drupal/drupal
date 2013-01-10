<?php

/**
 * @file
 * Administration page callbacks for the Book module.
 */

/**
 * Returns an administrative overview of all books.
 *
 * @return string
 *   A HTML-formatted string with the administrative page content.
 *
 * @see book_menu()
 */
function book_admin_overview() {
  $rows = array();

  $headers = array(t('Book'), t('Operations'));

  // Add any recognized books to the table list.
  foreach (book_get_books() as $book) {
    $rows[] = array(l($book['title'], $book['href'], $book['options']), l(t('edit order and titles'), 'admin/content/book/' . $book['nid']));
  }

  return theme('table', array('header' => $headers, 'rows' => $rows, 'empty' => t('No books available.')));
}

/**
 * Form constructor for the book settings form.
 *
 * @see book_admin_settings_validate()
 *
 * @ingroup forms
 */
function book_admin_settings() {
  $types = node_type_get_names();
  $form['book_allowed_types'] = array(
    '#type' => 'checkboxes',
    '#title' => t('Content types allowed in book outlines'),
    '#default_value' => variable_get('book_allowed_types', array('book')),
    '#options' => $types,
    '#description' => t('Users with the %outline-perm permission can add all content types.', array('%outline-perm' => t('Administer book outlines'))),
    '#required' => TRUE,
  );
  $form['book_child_type'] = array(
    '#type' => 'radios',
    '#title' => t('Content type for child pages'),
    '#default_value' => variable_get('book_child_type', 'book'),
    '#options' => $types,
    '#required' => TRUE,
  );
  $form['array_filter'] = array('#type' => 'value', '#value' => TRUE);
  $form['#validate'][] = 'book_admin_settings_validate';

  return system_settings_form($form);
}

/**
 * Form validation handler for book_admin_settings().
 *
 * @see book_admin_settings_submit()
 */
function book_admin_settings_validate($form, &$form_state) {
  $child_type = $form_state['values']['book_child_type'];
  if (empty($form_state['values']['book_allowed_types'][$child_type])) {
    form_set_error('book_child_type', t('The content type for the %add-child link must be one of those selected as an allowed book outline type.', array('%add-child' => t('Add child page'))));
  }
}

/**
 * Form constructor for administering a single book's hierarchy.
 *
 * @see book_admin_edit_submit()
 *
 * @param $node
 *   The node of the top-level page in the book.
 *
 * @see book_admin_edit_validate()
 * @see book_admin_edit_submit()
 * @ingroup forms
 */
function book_admin_edit($form, $form_state, $node) {
  drupal_set_title($node->title);
  $form['#node'] = $node;
  _book_admin_table($node, $form);
  $form['save'] = array(
    '#type' => 'submit',
    '#value' => t('Save book pages'),
  );

  return $form;
}

/**
 * Form validation handler for book_admin_edit().
 *
 * Checks that the book has not been changed while using the form.
 *
 * @see book_admin_edit_submit()
 */
function book_admin_edit_validate($form, &$form_state) {
  if ($form_state['values']['tree_hash'] != $form_state['values']['tree_current_hash']) {
    form_set_error('', t('This book has been modified by another user, the changes could not be saved.'));
  }
}

/**
 * Form submission handler for book_admin_edit().
 *
 * This function takes care to save parent menu items before their children.
 * Saving menu items in the incorrect order can break the menu tree.
 *
 * @see book_admin_edit_validate()
 * @see menu_overview_form_submit()
 */
function book_admin_edit_submit($form, &$form_state) {
  // Save elements in the same order as defined in post rather than the form.
  // This ensures parents are updated before their children, preventing orphans.
  $order = array_flip(array_keys($form_state['input']['table']));
  $form['table'] = array_merge($order, $form['table']);

  foreach (element_children($form['table']) as $key) {
    if ($form['table'][$key]['#item']) {
      $row = $form['table'][$key];
      $values = $form_state['values']['table'][$key];

      // Update menu item if moved.
      if ($row['plid']['#default_value'] != $values['plid'] || $row['weight']['#default_value'] != $values['weight']) {
        $row['#item']['plid'] = $values['plid'];
        $row['#item']['weight'] = $values['weight'];
        menu_link_save($row['#item']);
      }

      // Update the title if changed.
      if ($row['title']['#default_value'] != $values['title']) {
        $node = node_load($values['nid']);
        $langcode = LANGUAGE_NONE;
        $node->title = $values['title'];
        $node->book['link_title'] = $values['title'];
        $node->revision = 1;
        $node->log = t('Title changed from %original to %current.', array('%original' => $node->title, '%current' => $values['title']));

        node_save($node);
        watchdog('content', 'book: updated %title.', array('%title' => $node->title), WATCHDOG_NOTICE, l(t('view'), 'node/' . $node->nid));
      }
    }
  }

  drupal_set_message(t('Updated book %title.', array('%title' => $form['#node']->title)));
}

/**
 * Builds the table portion of the form for the book administration page.
 *
 * @param $node
 *   The node of the top-level page in the book.
 * @param $form
 *   The form that is being modified, passed by reference.
 *
 * @see book_admin_edit()
 */
function _book_admin_table($node, &$form) {
  $form['table'] = array(
    '#theme' => 'book_admin_table',
    '#tree' => TRUE,
  );

  $tree = book_menu_subtree_data($node->book);
  $tree = array_shift($tree); // Do not include the book item itself.
  if ($tree['below']) {
    $hash = drupal_hash_base64(serialize($tree['below']));
    // Store the hash value as a hidden form element so that we can detect
    // if another user changed the book hierarchy.
    $form['tree_hash'] = array(
      '#type' => 'hidden',
      '#default_value' => $hash,
    );
    $form['tree_current_hash'] = array(
      '#type' => 'value',
      '#value' => $hash,
    );
    _book_admin_table_tree($tree['below'], $form['table']);
  }

}

/**
 * Helps build the main table in the book administration page form.
 *
 * @param $tree
 *   A subtree of the book menu hierarchy.
 * @param $form
 *   The form that is being modified, passed by reference.
 *
 * @return
 *   The modified form array.
 *
 * @see book_admin_edit()
 */
function _book_admin_table_tree($tree, &$form) {
  // The delta must be big enough to give each node a distinct value.
  $count = count($tree);
  $delta = ($count < 30) ? 15 : intval($count / 2) + 1;

  foreach ($tree as $data) {
    $form['book-admin-' . $data['link']['nid']] = array(
      '#item' => $data['link'],
      'nid' => array('#type' => 'value', '#value' => $data['link']['nid']),
      'depth' => array('#type' => 'value', '#value' => $data['link']['depth']),
      'href' => array('#type' => 'value', '#value' => $data['link']['href']),
      'title' => array(
        '#type' => 'textfield',
        '#default_value' => $data['link']['link_title'],
        '#maxlength' => 255,
        '#size' => 40,
      ),
      'weight' => array(
        '#type' => 'weight',
        '#default_value' => $data['link']['weight'],
        '#delta' => max($delta, abs($data['link']['weight'])),
        '#title' => t('Weight for @title', array('@title' => $data['link']['title'])),
        '#title_display' => 'invisible',
      ),
      'plid' => array(
        '#type' => 'hidden',
        '#default_value' => $data['link']['plid'],
      ),
      'mlid' => array(
        '#type' => 'hidden',
        '#default_value' => $data['link']['mlid'],
      ),
    );
    if ($data['below']) {
      _book_admin_table_tree($data['below'], $form);
    }
  }

  return $form;
}

/**
 * Returns HTML for a book administration form.
 *
 * @param $variables
 *   An associative array containing:
 *   - form: A render element representing the form.
 *
 * @see book_admin_table()
 * @ingroup themeable
 */
function theme_book_admin_table($variables) {
  $form = $variables['form'];

  drupal_add_tabledrag('book-outline', 'match', 'parent', 'book-plid', 'book-plid', 'book-mlid', TRUE, MENU_MAX_DEPTH - 2);
  drupal_add_tabledrag('book-outline', 'order', 'sibling', 'book-weight');

  $header = array(t('Title'), t('Weight'), t('Parent'), array('data' => t('Operations'), 'colspan' => '3'));

  $rows = array();
  $destination = drupal_get_destination();
  $access = user_access('administer nodes');
  foreach (element_children($form) as $key) {
    $nid = $form[$key]['nid']['#value'];
    $href = $form[$key]['href']['#value'];

    // Add special classes to be used with tabledrag.js.
    $form[$key]['plid']['#attributes']['class'] = array('book-plid');
    $form[$key]['mlid']['#attributes']['class'] = array('book-mlid');
    $form[$key]['weight']['#attributes']['class'] = array('book-weight');

    $data = array(
      theme('indentation', array('size' => $form[$key]['depth']['#value'] - 2)) . drupal_render($form[$key]['title']),
      drupal_render($form[$key]['weight']),
      drupal_render($form[$key]['plid']) . drupal_render($form[$key]['mlid']),
      l(t('view'), $href),
      $access ? l(t('edit'), 'node/' . $nid . '/edit', array('query' => $destination)) : '&nbsp;',
      $access ? l(t('delete'), 'node/' . $nid . '/delete', array('query' => $destination) )  : '&nbsp;',
    );
    $row = array('data' => $data);
    if (isset($form[$key]['#attributes'])) {
      $row = array_merge($row, $form[$key]['#attributes']);
    }
    $row['class'][] = 'draggable';
    $rows[] = $row;
  }

  return theme('table', array('header' => $header, 'rows' => $rows, 'attributes' => array('id' => 'book-outline'), 'empty' => t('No book content available.')));
}
