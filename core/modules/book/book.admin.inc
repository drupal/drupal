<?php

/**
 * @file
 * Administration page callbacks for the Book module.
 */

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Language\Language;

/**
 * Returns HTML for a book administration form.
 *
 * @param array $variables
 *   An associative array containing:
 *   - form: A render element representing the form.
 *
 * @see \Drupal\book\Form\BookAdminEditForm::bookAdminTable()
 * @ingroup themeable
 */
function theme_book_admin_table($variables) {
  $form = $variables['form'];

  drupal_add_tabledrag('book-outline', 'match', 'parent', 'book-plid', 'book-plid', 'book-mlid', TRUE, MENU_MAX_DEPTH - 2);
  drupal_add_tabledrag('book-outline', 'order', 'sibling', 'book-weight');

  $header = array(t('Title'), t('Weight'), t('Parent'), t('Operations'));

  $rows = array();
  $destination = drupal_get_destination();
  $access = \Drupal::currentUser()->hasPermission('administer nodes');
  foreach (element_children($form) as $key) {
    $nid = $form[$key]['nid']['#value'];
    $href = $form[$key]['href']['#value'];

    // Add special classes to be used with tabledrag.js.
    $form[$key]['plid']['#attributes']['class'] = array('book-plid');
    $form[$key]['mlid']['#attributes']['class'] = array('book-mlid');
    $form[$key]['weight']['#attributes']['class'] = array('book-weight');

    $indentation = array('#theme' => 'indentation', '#size' => $form[$key]['depth']['#value'] - 2);
    $data = array(
      drupal_render($indentation) . drupal_render($form[$key]['title']),
      drupal_render($form[$key]['weight']),
      drupal_render($form[$key]['plid']) . drupal_render($form[$key]['mlid']),
    );
    $links = array();
    $links['view'] = array(
      'title' => t('View'),
      'href' => $href,
    );
    if ($access) {
      $links['edit'] = array(
        'title' => t('Edit'),
        'href' => "node/$nid/edit",
        'query' => $destination,
      );
      $links['delete'] = array(
        'title' => t('Delete'),
        'href' => "node/$nid/delete",
        'query' => $destination,
      );
    }
    $data[] = array(
      'data' => array(
        '#type' => 'operations',
        '#links' => $links,
      ),
    );
    $row = array('data' => $data);
    if (isset($form[$key]['#attributes'])) {
      $row = array_merge($row, $form[$key]['#attributes']);
    }
    $row['class'][] = 'draggable';
    $rows[] = $row;
  }
  $table = array('#theme' => 'table', '#header' => $header, '#rows' => $rows, '#attributes' => array('id' => 'book-outline'), '#empty' => t('No book content available.'));
  return drupal_render($table);
}
