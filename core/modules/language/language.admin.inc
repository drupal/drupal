<?php

/**
 * @file
 * Administration functions for language.module.
 */

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Render\Element;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;

/**
 * Prepares variables for language negotiation configuration form.
 *
 * Default template: language-content-configuration-form.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - form: A render element representing the form.
 */
function template_preprocess_language_negotiation_configure_form(&$variables) {
  $form =& $variables['form'];
  $variables['language_types'] = array();

  foreach ($form['#language_types'] as $type) {
    $header = array(
      t('Detection method'),
      t('Description'),
      t('Enabled'),
      t('Weight'),
    );

    // If there is at least one operation enabled show the operation column.
    if ($form[$type]['#show_operations']) {
      $header[] = t('Operations');
    }

    $table = array(
      '#type' => 'table',
      '#header' => $header,
      '#attributes' => array('id' => 'language-negotiation-methods-' . $type),
      '#tabledrag' => array(
        array(
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'language-method-weight-' . $type,
        ),
      ),
    );

    foreach ($form[$type]['title'] as $id => $element) {
      // Do not take form control structures.
      if (is_array($element) && element_child($id)) {
        $table[$id]['#attributes']['class'][] = 'draggable';
        $table[$id]['#weight'] = $element['#weight'];

        $table[$id]['title'] = array(
          '#prefix' => '<strong>',
          $form[$type]['title'][$id],
          '#suffix' => '</strong>',
        );
        $table[$id]['description'] = $form[$type]['description'][$id];
        $table[$id]['enabled'] = $form[$type]['enabled'][$id];
        $table[$id]['weight'] = $form[$type]['weight'][$id];
        if ($form[$type]['#show_operations']) {
          $table[$id]['operation'] = $form[$type]['operation'][$id];
        }
        // Unset to prevent rendering along with children.
        unset($form[$type]['title'][$id]);
        unset($form[$type]['description'][$id]);
        unset($form[$type]['enabled'][$id]);
        unset($form[$type]['weight'][$id]);
        unset($form[$type]['operation'][$id]);
      }
    }

    // Unset configurable to prevent rendering twice with children.
    $configurable = isset($form[$type]['configurable']) ? $form[$type]['configurable'] : NULL;
    unset($form[$type]['configurable']);

    $variables['language_types'][] = array(
      'type' => $type,
      'title' => $form[$type]['#title'],
      'description' => $form[$type]['#description'],
      'configurable' => $configurable,
      'table' => $table,
      'children' => $form[$type],
      'attributes' => new Attribute(),
    );
    // Prevent the type from rendering with the remaining form child elements.
    unset($form[$type]);
  }

  $variables['children'] = $form;
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
      'url' => Url::fromRoute('language.negotiation_browser_delete', ['browser_langcode' => $key]),
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
    t('Site language'),
    t('Operations'),
  );

  $table = array(
    '#type' => 'table',
    '#header' => $header,
    '#rows' => $rows,
    '#empty' => t('No browser language mappings available.'),
    '#attributes' => array('id' => 'language-negotiation-browser'),
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
            '#markup' => SafeMarkup::checkPlain($element[$bundle]['settings']['#label']),
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
