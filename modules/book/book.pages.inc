<?php
// $Id$

/**
 * @file
 * User page callbacks for the book module.
 */

/**
 * Menu callback; prints a listing of all books.
 */
function book_render() {
  $book_list = array();
  foreach (book_get_books() as $book) {
    $book_list[] = l($book['title'], $book['href'], $book['options']);
  }

  return theme('item_list', $book_list);
}

/**
 * Menu callback; Generates various representation of a book page and its children.
 *
 * The function delegates the generation of output to helper functions.
 * The function name is derived by prepending 'book_export_' to the
 * given output type. So, e.g., a type of 'html' results in a call to
 * the function book_export_html().
 *
 * @param $type
 *   A string encoding the type of output requested. The following
 *   types are currently supported in book module:
 *
 *   - html: HTML (printer friendly output)
 *
 *   Other types may be supported in contributed modules.
 * @param $nid
 *   An integer representing the node id (nid) of the node to export
 * @return
 *   A string representing the node and its children in the book hierarchy
 *   in a format determined by the $type parameter.
 */
function book_export($type, $nid) {

  $type = drupal_strtolower($type);

  $export_function = 'book_export_'. $type;

  if (function_exists($export_function)) {
    print call_user_func($export_function, $nid);
  }
  else {
    drupal_set_message(t('Unknown export format.'));
    drupal_not_found();
  }
}

/**
 * This function is called by book_export() to generate HTML for export.
 *
 * The given node is /embedded to its absolute depth in a top level
 * section/. For example, a child node with depth 2 in the hierarchy
 * is contained in (otherwise empty) &lt;div&gt; elements
 * corresponding to depth 0 and depth 1. This is intended to support
 * WYSIWYG output - e.g., level 3 sections always look like level 3
 * sections, no matter their depth relative to the node selected to be
 * exported as printer-friendly HTML.
 *
 * @param $nid
 *   An integer representing the node id (nid) of the node to export.
 * @return
 *   A string containing HTML representing the node and its children in
 *   the book hierarchy.
 */
function book_export_html($nid) {
  if (user_access('access printer-friendly version')) {
    $export_data = array();
    $node = node_load($nid);
    if (isset($node->book)) {
      $tree = book_menu_subtree_data($node->book);
      $contents = book_export_traverse($tree, 'book_node_export');
    }
    return theme('book_export_html', $node->title, $contents, $node->book['depth']);
  }
  else {
    drupal_access_denied();
  }
}

/**
 * Menu callback; show the outline form for a single node.
 */
function book_outline($node) {
  drupal_set_title(check_plain($node->title));
  return drupal_get_form('book_outline_form', $node);
}

/**
 * Build the form to handle all book outline operations via the outline tab.
 *
 * @see book_outline_form_submit()
 * @see book_remove_button_submit()
 *
 * @ingroup forms
 */
function book_outline_form(&$form_state, $node) {

  if (!isset($node->book)) {
    // The node is not part of any book yet - set default options.
    $node->book = _book_link_defaults($node->nid);
  }
  else {
    $node->book['original_bid'] = $node->book['bid'];
  }
  // Find the depth limit for the parent select.
  if (!isset($node->book['parent_depth_limit'])) {
    $node->book['parent_depth_limit'] = _book_parent_depth_limit($node->book);
  }
  $form['#node'] = $node;
  $form['#id'] = 'book-outline';
  _book_add_form_elements($form, $node);

  $form['book']['#collapsible'] = FALSE;

  $form['update'] = array(
    '#type' => 'submit',
    '#value' => $node->book['original_bid'] ? t('Update book outline') : t('Add to book outline'),
    '#weight' => 15,
  );

  $form['remove'] = array(
    '#type' => 'submit',
    '#value' => t('Remove from book outline'),
    '#access' => $node->nid != $node->book['bid'] && $node->book['bid'],
    '#weight' => 20,
    '#submit' => array('book_remove_button_submit'),
  );

  return $form;
}

/**
 * Button submit function to redirect to removal confirm form.
 *
 * @see book_outline_form()
 */
function book_remove_button_submit($form, &$form_state) {
  $form_state['redirect'] = 'node/'. $form['#node']->nid .'/outline/remove';
}

/**
 * Handles book outline form submissions from the outline tab.
 *
 * @see book_outline_form()
 */
function book_outline_form_submit($form, &$form_state) {
  $node = $form['#node'];
  $form_state['redirect'] = "node/". $node->nid;
  $book_link = $form_state['values']['book'];
  if (!$book_link['bid']) {
    drupal_set_message(t('No changes were made'));
    return;
  }

  $book_link['menu_name'] = book_menu_name($book_link['bid']);
  $node->book = $book_link;
  if (_book_update_outline($node)) {
    if ($node->book['parent_mismatch']) {
      // This will usually only happen when JS is disabled.
      drupal_set_message(t('The post has been added to the selected book. You may now position it relative to other pages.'));
      $form_state['redirect'] = "node/". $node->nid ."/outline";
    }
    else {
      drupal_set_message(t('The book outline has been updated.'));
    }
  }
  else {
    drupal_set_message(t('There was an error adding the post to the book.'), 'error');
  }
}

/**
 * Menu callback; builds a form to confirm removal of a node from the book.
 *
 * @see book_remove_form_submit()
 *
 * @ingroup forms
 */
function book_remove_form(&$form_state, $node) {
  $form['#node'] = $node;
  $title = array('%title' => $node->title);

  if ($node->book['has_children']) {
    $description = t('%title has associated child pages, which will be relocated automatically to maintain their connection to the book. To recreate the hierarchy (as it was before removing this page), %title may be added again using the Outline tab, and each of its former child pages will need to be relocated manually.', $title);
  }
  else {
    $description = t('%title may be added to hierarchy again using the Outline tab.', $title);
  }

  return confirm_form($form, t('Are you sure you want to remove %title from the book hierarchy?', $title), 'node/'. $node->nid, $description, t('Remove'));
}

/**
 * Confirm form submit function to remove a node from the book.
 *
 * @see book_remove_form()
 */
function book_remove_form_submit($form, &$form_state) {
  $node = $form['#node'];
  if ($node->nid != $node->book['bid']) {
    // Only allowed when this is not a book (top-level page).
    menu_link_delete($node->book['mlid']);
    db_query('DELETE FROM {book} WHERE nid = %d', $node->nid);
    drupal_set_message(t('The post has been removed from the book.'));
  }
  $form_state['redirect'] = 'node/'. $node->nid;
}

/**
 * Renders a new parent page select element when the book selection changes.
 *
 * This function is called via AJAX when the selected book is changed on a node
 * or book outline form. It creates a new parent page select element, adds it
 * to the cached form, and then returns the rendered element so it can be
 * displayed on the form.
 *
 * @return
 *   The rendered parent page select element.
 */
function book_form_update() {
  $bid = $_POST['book']['bid'];
  if ($form = form_get_cache($_POST['form_build_id'], $form_state)) {

    // Validate the bid.
    if (isset($form['book']['bid']['#options'][$bid])) {
      $book_link = $form['#node']->book;
      $book_link['bid'] = $bid;
      // Get the new options and update the cache.
      $form['book']['plid'] = _book_parent_select($book_link);
      form_set_cache($_POST['form_build_id'], $form, $form_state);

      // Build and render the new select element, then return it in JSON format.
      $form_state = array();
      $form['#post'] = array();
      $form = form_builder($form['form_id']['#value'] , $form, $form_state);
      $output = drupal_render($form['book']['plid']);
      drupal_json(array('status' => TRUE, 'data' => $output));
    }
    else {
      drupal_json(array('status' => FALSE, 'data' => ''));
    }
  }
  else {
    drupal_json(array('status' => FALSE, 'data' => ''));
  }
  exit();
}
