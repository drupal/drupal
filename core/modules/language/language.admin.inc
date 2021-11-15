<?php

/**
 * @file
 * Administration functions for language.module.
 */

use Drupal\Core\Render\Element;
use Drupal\Core\Template\Attribute;

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
  $variables['language_types'] = [];

  foreach ($form['#language_types'] as $type) {
    $header = [
      t('Detection method'),
      t('Description'),
      t('Enabled'),
      t('Weight'),
    ];

    // If there is at least one operation enabled show the operation column.
    if ($form[$type]['#show_operations']) {
      $header[] = t('Operations');
    }

    $table = [
      '#type' => 'table',
      '#header' => $header,
      '#attributes' => ['id' => 'language-negotiation-methods-' . $type],
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'language-method-weight-' . $type,
        ],
      ],
    ];

    foreach ($form[$type]['title'] as $id => $element) {
      // Do not take form control structures.
      if (is_array($element) && Element::child($id)) {
        $table[$id]['#attributes']['class'][] = 'draggable';
        $table[$id]['#weight'] = $element['#weight'];

        $table[$id]['title'] = [
          '#prefix' => '<strong>',
          $form[$type]['title'][$id],
          '#suffix' => '</strong>',
        ];
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
    $configurable = $form[$type]['configurable'] ?? NULL;
    unset($form[$type]['configurable']);

    $variables['language_types'][] = [
      'type' => $type,
      'title' => $form[$type]['#title'],
      'description' => $form[$type]['#description'],
      'configurable' => $configurable,
      'table' => $table,
      'children' => $form[$type],
      'attributes' => new Attribute(),
    ];
    // Prevent the type from rendering with the remaining form child elements.
    unset($form[$type]);
  }

  $variables['children'] = $form;
}

/**
 * Prepares variables for language content settings table templates.
 *
 * Default template: language-content-settings-table.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the properties of the element.
 *     Properties used: #bundle_label, #title.
 */
function template_preprocess_language_content_settings_table(&$variables) {
  // Add a render element representing the bundle language settings table.
  $element = $variables['element'];

  $header = [
    [
      'data' => $element['#bundle_label'],
      'class' => ['bundle'],
    ],
    [
      'data' => t('Configuration'),
      'class' => ['operations'],
    ],
  ];

  $rows = [];
  foreach (Element::children($element) as $bundle) {
    $rows[$bundle] = [
      'data' => [
        [
          'data' => [
            '#prefix' => '<label>',
            '#suffix' => '</label>',
            '#plain_text' => $element[$bundle]['settings']['#label'],
          ],
          'class' => ['bundle'],
        ],
        [
          'data' => $element[$bundle]['settings'],
          'class' => ['operations'],
        ],
      ],
      'class' => ['bundle-settings'],
    ];
  }

  $variables['title'] = $element['#title'];
  $variables['build'] = [
    '#header' => $header,
    '#rows' => $rows,
    '#type' => 'table',
  ];
}
