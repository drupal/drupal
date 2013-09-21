<?php

/**
 * @file
 * User page callbacks for the book module.
 */

use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;


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
 * Form constructor to confirm removal of a node from a book.
 *
 * @param \Drupal\Core\Entity\EntityInterface $node
 *   The node to delete.
 *
 * @see book_remove_form_submit()
 * @see book_menu()
 * @ingroup forms
 *
 * @deprecated Use \Drupal\book\Form\BookForm::remove()
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
  if (\Drupal::service('book.manager')->checkNodeIsRemovable($node)) {
    menu_link_delete($node->book['mlid']);
    db_delete('book')
      ->condition('nid', $node->id())
      ->execute();
    drupal_set_message(t('The post has been removed from the book.'));
  }
  $form_state['redirect'] = 'node/' . $node->id();
}
