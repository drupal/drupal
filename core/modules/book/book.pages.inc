<?php

/**
 * @file
 * User page callbacks for the book module.
 */

use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
