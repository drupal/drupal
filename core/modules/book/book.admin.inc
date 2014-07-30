<?php

/**
 * @file
 * Administration page callbacks for the Book module.
 */

use Drupal\Component\Utility\SafeMarkup;
use Drupal\book\BookManager;
use Drupal\Core\Render\Element;

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

  $header = array(t('Title'), t('Weight'), t('Parent'), t('Operations'));

  $rows = array();
  $destination = drupal_get_destination();
  $access = \Drupal::currentUser()->hasPermission('administer nodes');
  foreach (Element::children($form) as $key) {
    $nid = $form[$key]['nid']['#value'];
    $href = \Drupal::url('node.view', array('node' => $nid));

    // Add special classes to be used with tabledrag.js.
    $form[$key]['pid']['#attributes']['class'] = array('book-pid');
    $form[$key]['nid']['#attributes']['class'] = array('book-nid');
    $form[$key]['weight']['#attributes']['class'] = array('book-weight');

    $indentation = array('#theme' => 'indentation', '#size' => $form[$key]['depth']['#value'] - 2);
    $data = array(
      SafeMarkup::set(drupal_render($indentation) . drupal_render($form[$key]['title'])),
      drupal_render($form[$key]['weight']),
      SafeMarkup::set(drupal_render($form[$key]['pid']) . drupal_render($form[$key]['nid'])),
    );
    $links = array();
    $links['view'] = array(
      'title' => t('View'),
      'href' => $href,
    );
    if ($access) {
      $links['edit'] = array(
        'title' => t('Edit'),
        'route_name' => 'node.page_edit',
        'route_parameters' => array('node' => $nid),
        'query' => $destination,
      );
      $links['delete'] = array(
        'title' => t('Delete'),
        'route_name' => 'node.delete_confirm',
        'route_parameters' => array('node' => $nid),
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
  $table = array(
    '#type' => 'table',
    '#header' => $header,
    '#rows' => $rows,
    '#attributes' => array('id' => 'book-outline'),
    '#empty' => t('No book content available.'),
    '#tabledrag' => array(
      array(
        'action' => 'match',
        'relationship' => 'parent',
        'group' => 'book-pid',
        'subgroup' => 'book-pid',
        'source' => 'book-nid',
        'hidden' => TRUE,
        'limit' => BookManager::BOOK_MAX_DEPTH - 2,
      ),
      array(
        'action' => 'order',
        'relationship' => 'sibling',
        'group' => 'book-weight',
      ),
    ),
  );
  return drupal_render($table);
}
