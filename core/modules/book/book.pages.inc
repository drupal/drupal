<?php

/**
 * @file
 * User page callbacks for the book module.
 */

use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Page callback: Generates representations of a book page and its children.
 *
 * The function delegates the generation of output to helper functions. The
 * function name is derived by prepending 'book_export_' to the given output
 * type. So, e.g., a type of 'html' results in a call to the function
 * book_export_html().
 *
 * @param $type
 *   A string encoding the type of output requested. The following types are
 *   currently supported in book module:
 *   - html: Printer-friendly HTML.
 *   Other types may be supported in contributed modules.
 * @param \Drupal\node\Entity\EntityInterface $node
 *   The node to export.
 *
 * @return
 *   A string representing the node and its children in the book hierarchy in a
 *   format determined by the $type parameter.
 *
 * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
 *
 * @see book_menu()
 */
function book_export($type, EntityInterface $node) {
  $type = drupal_strtolower($type);

  $export_function = 'book_export_' . $type;

  if (function_exists($export_function)) {
    print call_user_func($export_function, $node);
  }
  else {
    drupal_set_message(t('Unknown export format.'));
    throw new NotFoundHttpException();
  }
}

/**
 * Generates HTML for export when invoked by book_export().
 *
 * The given node is embedded to its absolute depth in a top level section. For
 * example, a child node with depth 2 in the hierarchy is contained in
 * (otherwise empty) <div> elements corresponding to depth 0 and depth 1.
 * This is intended to support WYSIWYG output - e.g., level 3 sections always
 * look like level 3 sections, no matter their depth relative to the node
 * selected to be exported as printer-friendly HTML.
 *
 * @param \Drupal\node\Entity\Node
 *   The node to export.
 *
 * @return
 *   A string containing HTML representing the node and its children in
 *   the book hierarchy.
 *
 * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
 * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
 */
function book_export_html(EntityInterface $node) {
  if (user_access('access printer-friendly version')) {
    if (isset($node->book)) {
      $tree = book_menu_subtree_data($node->book);
      $contents = book_export_traverse($tree, 'book_node_export');
      $book_exported_html = array('#theme' => 'book_export_html', '#title' => $node->label(), '#contents' => $contents, '#depth' => $node->book['depth']);
      return drupal_render($book_exported_html);
    }
    else {
      throw new NotFoundHttpException();
    }
  }
  else {
    throw new AccessDeniedHttpException();
  }
}

/**
 * Page callback: Shows the outline form for a single node.
 *
 * @param \Drupal\Core\Entity\EntityInterface $node
 *   The book node for which to show the outline.
 *
 * @return string
 *   A HTML-formatted string with the outline form for a single node.
 *
 * @see book_menu()
 */
function book_outline(EntityInterface $node) {
  drupal_set_title($node->label());
  return drupal_get_form('book_outline_form', $node);
}

/**
 * Form constructor for the book outline form.
 *
 * Allows handling of all book outline operations via the outline tab.
 *
 * @param \Drupal\Core\Entity\EntityInterface $node
 *   The book node for which to show the outline.
 *
 * @see book_outline_form_submit()
 * @see book_remove_button_submit()
 * @ingroup forms
 */
function book_outline_form($form, &$form_state, EntityInterface $node) {
  if (!isset($node->book)) {
    // The node is not part of any book yet - set default options.
    $node->book = _book_link_defaults($node->id());
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
  _book_add_form_elements($form, $form_state, $node);

  $form['update'] = array(
    '#type' => 'submit',
    '#value' => $node->book['original_bid'] ? t('Update book outline') : t('Add to book outline'),
    '#weight' => 15,
  );

  $form['remove'] = array(
    '#type' => 'submit',
    '#value' => t('Remove from book outline'),
    '#access' => _book_node_is_removable($node),
    '#weight' => 20,
    '#submit' => array('book_remove_button_submit'),
  );

  return $form;
}

/**
 * Form submission handler for book_outline_form().
 *
 * Redirects to removal confirmation form.
 *
 * @see book_outline_form_submit()
 */
function book_remove_button_submit($form, &$form_state) {
  $form_state['redirect'] = 'node/' . $form['#node']->id() . '/outline/remove';
}

/**
 * Form submission handler for book_outline_form().
 *
 * @see book_remove_button_submit()
 */
function book_outline_form_submit($form, &$form_state) {
  $node = $form['#node'];
  $form_state['redirect'] = "node/" . $node->id();
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
      $form_state['redirect'] = "node/" . $node->id() . "/outline";
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
 * Form constructor to confirm removal of a node from a book.
 *
 * @param \Drupal\Core\Entity\EntityInterface $node
 *   The node to delete.
 *
 * @see book_remove_form_submit()
 * @see book_menu()
 * @ingroup forms
 */
function book_remove_form($form, &$form_state, EntityInterface $node) {
  $form['#node'] = $node;
  $title = array('%title' => $node->label());

  if ($node->book['has_children']) {
    $description = t('%title has associated child pages, which will be relocated automatically to maintain their connection to the book. To recreate the hierarchy (as it was before removing this page), %title may be added again using the Outline tab, and each of its former child pages will need to be relocated manually.', $title);
  }
  else {
    $description = t('%title may be added to hierarchy again using the Outline tab.', $title);
  }

  return confirm_form($form, t('Are you sure you want to remove %title from the book hierarchy?', $title), 'node/' . $node->id(), $description, t('Remove'));
}

/**
 * Form submission handler for book_remove_form().
 */
function book_remove_form_submit($form, &$form_state) {
  $node = $form['#node'];
  if (_book_node_is_removable($node)) {
    menu_link_delete($node->book['mlid']);
    db_delete('book')
      ->condition('nid', $node->id())
      ->execute();
    drupal_set_message(t('The post has been removed from the book.'));
  }
  $form_state['redirect'] = 'node/' . $node->id();
}
