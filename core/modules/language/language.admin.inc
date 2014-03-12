<?php

/**
 * @file
 * Administration functions for language.module.
 */

use Drupal\Component\Utility\String;
use Drupal\Core\Language\LanguageManager;

/**
 * Prepare a language code list for unused predefined languages.
 */
function language_admin_predefined_list() {
  $languages = language_list();
  $predefined = LanguageManager::getStandardLanguageList();
  foreach ($predefined as $key => $value) {
    if (isset($languages[$key])) {
      unset($predefined[$key]);
      continue;
    }
    $predefined[$key] = t($value[0]);
  }
  asort($predefined);
  return $predefined;
}

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
      if (is_array($element) && element_child($id)) {
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
  foreach (element_children($form, TRUE) as $key) {
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
 * Returns the content language settings form.
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
 *   Use \Drupal\language\Controller\LanguageController::contentSettings().
 */
function language_content_settings_page() {
  return drupal_get_form('language_content_settings_form', language_entity_supported());
}

/**
 * Form constructor for the content language settings form.
 *
 * @param array $supported
 *   Entity types with language support.
 *
 * @see language_content_settings_form_submit()
 *
 * @ingroup forms
 */
function language_content_settings_form(array $form, array $form_state, array $supported) {
  $entity_types = \Drupal::entityManager()->getDefinitions();
  $labels = array();
  $default = array();

  foreach ($supported as $entity_type_id) {
    $labels[$entity_type_id] = $entity_types[$entity_type_id]->getLabel() ?: $entity_type_id;
    $default[$entity_type_id] = FALSE;

    // Check whether we have any custom setting.
    foreach (entity_get_bundles($entity_type_id) as $bundle => $bundle_info) {
      $conf = language_get_default_configuration($entity_type_id, $bundle);
      if (!empty($conf['language_show']) || $conf['langcode'] != 'site_default') {
        $default[$entity_type_id] = $entity_type_id;
      }
      $language_configuration[$entity_type_id][$bundle] = $conf;
    }
  }

  asort($labels);

  $path = drupal_get_path('module', 'language');
  $form = array(
    '#labels' => $labels,
    '#attached' => array(
      'css' => array($path . '/css/language.admin.css'),
    ),
  );

  $form['entity_types'] = array(
    '#title' => t('Custom language settings'),
    '#type' => 'checkboxes',
    '#options' => $labels,
    '#default_value' => $default,
  );

  $form['settings'] = array('#tree' => TRUE);

  foreach ($labels as $entity_type_id => $label) {
    $entity_type = $entity_types[$entity_type_id];

    $form['settings'][$entity_type_id] = array(
      '#title' => $label,
      '#type' => 'container',
      '#entity_type' => $entity_type_id,
      '#theme' => 'language_content_settings_table',
      '#bundle_label' => $entity_type->getBundleLabel() ?: $label,
      '#states' => array(
        'visible' => array(
          ':input[name="entity_types[' . $entity_type_id . ']"]' => array('checked' => TRUE),
        ),
      ),
    );

    foreach (entity_get_bundles($entity_type_id) as $bundle => $bundle_info) {
      $form['settings'][$entity_type_id][$bundle]['settings'] = array(
        '#type' => 'item',
        '#label' => $bundle_info['label'],
        'language' => array(
          '#type' => 'language_configuration',
          '#entity_information' => array(
            'entity_type' => $entity_type_id,
            'bundle' => $bundle,
          ),
          '#default_value' => $language_configuration[$entity_type_id][$bundle],
        ),
      );
    }
  }

  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save'),
  );

  return $form;
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
  foreach (element_children($element) as $bundle) {
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
 * Form submission handler for language_content_settings_form().
 */
function language_content_settings_form_submit(array $form, array &$form_state) {
  $settings = &$form_state['values']['settings'];
  foreach ($settings as $entity_type => $entity_settings) {
    foreach ($entity_settings as $bundle => $bundle_settings) {
      language_save_default_configuration($entity_type, $bundle, $bundle_settings['settings']['language']);
    }
  }
  drupal_set_message(t('Settings successfully updated.'));
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
