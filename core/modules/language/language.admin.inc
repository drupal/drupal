<?php

/**
 * @file
 * Administration functions for language.module.
 */

use Drupal\Component\Utility\String;
use Drupal\Core\Render\Element;

/**
 * Returns HTML for the language negotiation configuration form.
 *
 * @param $variables
 *   An associative array containing:
 *   - form: A render element representing the form.
 *
 * @ingroup themeable
 */
function theme_language_negotiation_configure_form($variables) {
  $form = $variables['form'];
  $output = '';

  foreach ($form['#language_types'] as $type) {
    $rows = array();
    $title = '<h2>' . $form[$type]['#title'] . '</h2>';
    $description = '<div class="description">' . $form[$type]['#description'] . '</div>';

    foreach ($form[$type]['title'] as $id => $element) {
      // Do not take form control structures.
      if (is_array($element) && Element::child($id)) {
        $row = array(
          'data' => array(
            '<strong>' . drupal_render($form[$type]['title'][$id]) . '</strong>',
            drupal_render($form[$type]['description'][$id]),
            drupal_render($form[$type]['enabled'][$id]),
            drupal_render($form[$type]['weight'][$id]),
          ),
          'class' => array('draggable'),
        );
        if ($form[$type]['#show_operations']) {
          $row['data'][] = drupal_render($form[$type]['operation'][$id]);
        }
        $rows[] = $row;
      }
    }

    $header = array(
      array('data' => t('Detection method')),
      array('data' => t('Description')),
      array('data' => t('Enabled')),
      array('data' => t('Weight')),
    );

    // If there is at least one operation enabled show the operation column.
    if ($form[$type]['#show_operations']) {
      $header[] = array('data' => t('Operations'));
    }

    $build = array(
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => array('id' => "language-negotiation-methods-$type"),
      '#tabledrag' => array(
        array(
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => "language-method-weight-$type",
        ),
      ),
    );

    $table  = drupal_render($form[$type]['configurable']);
    $table .= drupal_render($build);
    $table .= drupal_render_children($form[$type]);

    $output .= '<div class="form-item table-language-group table-' . $type . '-wrapper">' . $title . $description . $table . '</div>';
  }

  $output .= drupal_render_children($form);
  return $output;
}

/**
 * Theme browser configuration form as table.
 *
 * @param $variables
 *   An associative array containing:
 *   - form: A render element representing the form.
 *
 * @ingroup themeable
 */
function theme_language_negotiation_configure_browser_form_table($variables) {
  $form = $variables['form'];
  $rows = array();
  foreach (Element::children($form, TRUE) as $key) {
    $row = array();
    $row[] = drupal_render($form[$key]['browser_langcode']);
    $row[] = drupal_render($form[$key]['drupal_langcode']);
    $links = array();
    $links['delete'] = array(
      'title' => t('Delete'),
      'href' => "admin/config/regional/language/detection/browser/delete/$key",
      'attributes' => array(
        'class' => array('image-style-link'),
      ),
    );
    $row[] = array(
      'data' => array(
        '#type' => 'operations',
        '#links' => $links,
      ),
    );

    $rows[] = $row;
  }

  $header = array(
    t('Browser language code'),
    t('Drupal language'),
    t('Operations'),
  );

  $table = array(
    '#type' => 'table',
    '#header' => $header,
    '#rows' => $rows,
    '#attributes' => array('id' => 'lang-neg-browser'),
  );
  $output = drupal_render($table);

  return $output;
}

/**
 * Implements hook_preprocess_HOOK() for theme_language_content_settings_table().
 */
function template_preprocess_language_content_settings_table(&$variables) {
  // Add a render element representing the bundle language settings table.
  $element = $variables['element'];

  $header = array(
    array(
      'data' => $element['#bundle_label'],
      'class' => array('bundle'),
    ),
    array(
      'data' => t('Configuration'),
      'class' => array('operations'),
    ),
  );

  $rows = array();
  foreach (Element::children($element) as $bundle) {
    $rows[$bundle] = array(
      'data' => array(
        array(
          'data' => array(
            '#prefix' => '<label>',
            '#suffix' => '</label>',
            '#markup' => String::checkPlain($element[$bundle]['settings']['#label']),
          ),
          'class' => array('bundle'),
        ),
        array(
          'data' => $element[$bundle]['settings'],
          'class' => array('operations'),
        ),
      ),
      'class' => array('bundle-settings'),
    );
  }

  $variables['build'] = array(
    '#title' => $element['#title'],
    '#header' => $header,
    '#rows' => $rows,
    '#type' => 'table',
  );
}

/**
 * Returns HTML for an administration settings table.
 *
 * @param array $variables
 *   An associative array containing:
 *   - build: A render element representing a table of bundle content language
 *     settings for a particular entity type.
 *
 * @ingroup themable
 */
function theme_language_content_settings_table($variables) {
  return '<h4>' . $variables['build']['#title'] . '</h4>' . drupal_render($variables['build']);
}

/**
 * Helper function to disable the language switcher blocks.
 *
 * @param array $language_types
 *   Array containing all language types whose language switchers need to be
 *   disabled.
 */
function _language_disable_language_switcher(array $language_types) {
  $blocks = _block_rehash();
  foreach ($language_types as $language_type) {
    foreach ($blocks as $block) {
      if (strpos($block->id, 'language_switcher_' . substr($language_type, 9)) !== FALSE) {
        $block->delete();
      }
    }
  }
}
