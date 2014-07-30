<?php

/**
 * @file
 * Administrative page callbacks for Menu UI module.
 */

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Render\Element;

/**
 * Returns HTML for the menu overview form into a table.
 *
 * @param array $variables
 *   An associative array containing:
 *   - form: A render element representing the form.
 *
 * @return string
 *   The themed HTML.
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
  foreach (Element::children($form) as $id) {
    if (isset($form[$id]['#item'])) {
      $element = &$form[$id];

      // Add special classes to be used for tabledrag.js.
      $element['parent']['#attributes']['class'] = array('menu-parent');
      $element['id']['#attributes']['class'] = array('menu-id');
      $element['weight']['#attributes']['class'] = array('menu-weight');

      // Change the parent field to a hidden. This allows any value but hides
      // the field.
      $element['parent']['#type'] = 'hidden';

      $indent = array(
        '#theme' => 'indentation',
        '#size' => $element['#item']->depth - 1,
      );

      $row = array();
      $row[] = SafeMarkup::set(drupal_render($indent) . drupal_render($element['title']));
      $row[] = array('data' => drupal_render($element['enabled']), 'class' => array('checkbox', 'menu-enabled'));
      $row[] = SafeMarkup::set(drupal_render($element['weight']) . drupal_render($element['parent']) . drupal_render($element['id']));
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
        'group' => 'menu-parent',
        'subgroup' => 'menu-parent',
        'source' => 'menu-id',
        'hidden' => TRUE,
        'limit' => \Drupal::menuTree()->maxDepth() - 1,
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
