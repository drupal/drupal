<?php

/**
 * @file
 * Administrative page callbacks for menu module.
 */

/**
 * Returns HTML for the menu overview form into a table.
 *
 * @param $variables
 *   An associative array containing:
 *   - form: A render element representing the form.
 *
 * @ingroup themeable
 */
function theme_menu_overview_form($variables) {
  $form = $variables['form'];

  $header = array(
    t('Menu link'),
    array('data' => t('Enabled'), 'class' => array('checkbox')),
    t('Weight'),
    t('Operations'),
  );

  $rows = array();
  foreach (element_children($form) as $mlid) {
    if (isset($form[$mlid]['hidden'])) {
      $element = &$form[$mlid];

      // Add special classes to be used for tabledrag.js.
      $element['plid']['#attributes']['class'] = array('menu-plid');
      $element['mlid']['#attributes']['class'] = array('menu-mlid');
      $element['weight']['#attributes']['class'] = array('menu-weight');

      // Change the parent field to a hidden. This allows any value but hides the field.
      $element['plid']['#type'] = 'hidden';

      $indent = array(
        '#theme' => 'indentation',
        '#size' => $element['#item']['depth'] - 1,
      );

      $row = array();
      $row[] = drupal_render($indent) . drupal_render($element['title']);
      $row[] = array('data' => drupal_render($element['hidden']), 'class' => array('checkbox', 'menu-enabled'));
      $row[] = drupal_render($element['weight']) . drupal_render($element['plid']) . drupal_render($element['mlid']);
      $row[] = drupal_render($element['operations']);

      $row = array_merge(array('data' => $row), $element['#attributes']);
      $row['class'][] = 'draggable';
      $rows[] = $row;
    }
  }
  $output = '';
  if (empty($rows)) {
    $rows[] = array(array('data' => $form['#empty_text'], 'colspan' => '7'));
  }

  $table = array(
    '#type' => 'table',
    '#header' => $header,
    '#rows' => $rows,
    '#attributes' => array(
      'id' => 'menu-overview',
    ),
    '#tabledrag' => array(
      array(
        'action' => 'match',
        'relationship' => 'parent',
        'group' => 'menu-plid',
        'subgroup' => 'menu-plid',
        'source' => 'menu-mlid',
        'hidden' => TRUE,
        'limit' => MENU_MAX_DEPTH - 1,
      ),
      array(
        'action' => 'order',
        'relationship' => 'sibling',
        'group' => 'menu-weight',
      ),
    ),
  );

  $output .= drupal_render($form['inline_actions']);
  $output .= drupal_render($table);
  $output .= drupal_render_children($form);
  return $output;
}
