<?php

/**
 * @file
 * Administration functions for language.module.
 */

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
 * Builds the configuration form for language negotiation.
 *
 * @deprecated Use \Drupal\language\Form\LanguageForm::negotiation()
 */
function language_negotiation_configure_form() {
  language_negotiation_include();

  $form = array(
    '#submit' => array('language_negotiation_configure_form_submit'),
    '#theme' => 'language_negotiation_configure_form',
    '#language_types_info' => language_types_info(),
    '#language_negotiation_info' => language_negotiation_info(),
  );
  $form['#language_types'] = array();
  $configurable = \Drupal::config('system.language.types')->get('configurable');
  foreach ($form['#language_types_info'] as $type => $info) {
    // Show locked language types only if they are configurable.
    if (empty($info['locked']) || in_array($type, $configurable)) {
      $form['#language_types'][] = $type;
    }
  }
  foreach ($form['#language_types'] as $type) {
    language_negotiation_configure_form_table($form, $type);
  }

  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save settings'),
  );

  return $form;
}

/**
 * Builds a language negotiation method configuration table.
 */
function language_negotiation_configure_form_table(&$form, $type) {
  $info = $form['#language_types_info'][$type];

  $table_form = array(
    '#title' => t('@type language detection', array('@type' => $info['name'])),
    '#tree' => TRUE,
    '#description' => $info['description'],
    '#language_negotiation_info' => array(),
    '#show_operations' => FALSE,
    'weight' => array('#tree' => TRUE),
  );
  // Only show configurability checkbox for the unlocked language types.
  if (empty($info['locked'])) {
    $configurable = \Drupal::config('system.language.types')->get('configurable');
    $table_form['configurable'] = array(
      '#type' => 'checkbox',
      '#title' => t('Customize %language_name language detection to differ from User interface text language detection settings.', array('%language_name' => $info['name'])),
      '#default_value' => in_array($type, $configurable),
      '#attributes' => array('class' => array('language-customization-checkbox')),
      '#attached' => array(
        'library' => array(
          array('language', 'language.admin')
        ),
      ),
    );
  }

  $negotiation_info = $form['#language_negotiation_info'];
  $enabled_methods = variable_get("language_negotiation_$type", array());
  $methods_weight = variable_get("language_negotiation_methods_weight_$type", array());

  // Add missing data to the methods lists.
  foreach ($negotiation_info as $method_id => $method) {
    if (!isset($methods_weight[$method_id])) {
      $methods_weight[$method_id] = isset($method['weight']) ? $method['weight'] : 0;
    }
  }

  // Order methods list by weight.
  asort($methods_weight);

  foreach ($methods_weight as $method_id => $weight) {
    // A language method might be no more available if the defining module has
    // been disabled after the last configuration saving.
    if (!isset($negotiation_info[$method_id])) {
      continue;
    }

    $enabled = isset($enabled_methods[$method_id]);
    $method = $negotiation_info[$method_id];

    // List the method only if the current type is defined in its 'types' key.
    // If it is not defined default to all the configurable language types.
    $types = array_flip(isset($method['types']) ? $method['types'] : $form['#language_types']);

    if (isset($types[$type])) {
      $table_form['#language_negotiation_info'][$method_id] = $method;
      $method_name = check_plain($method['name']);

      $table_form['weight'][$method_id] = array(
        '#type' => 'weight',
        '#title' => t('Weight for !title language detection method', array('!title' => drupal_strtolower($method_name))),
        '#title_display' => 'invisible',
        '#default_value' => $weight,
        '#attributes' => array('class' => array("language-method-weight-$type")),
        '#delta' => 20,
      );

      $table_form['title'][$method_id] = array('#markup' => $method_name);

      $table_form['enabled'][$method_id] = array(
        '#type' => 'checkbox',
        '#title' => t('Enable !title language detection method', array('!title' => drupal_strtolower($method_name))),
        '#title_display' => 'invisible',
        '#default_value' => $enabled,
      );
      if ($method_id === LANGUAGE_NEGOTIATION_SELECTED) {
        $table_form['enabled'][$method_id]['#default_value'] = TRUE;
        $table_form['enabled'][$method_id]['#attributes'] = array('disabled' => 'disabled');
      }

      $table_form['description'][$method_id] = array('#markup' => filter_xss_admin($method['description']));

      $config_op = array();
      if (isset($method['config'])) {
        $config_op['configure'] = array(
          'title' => t('Configure'),
          'href' => $method['config'],
        );
        // If there is at least one operation enabled show the operation column.
        $table_form['#show_operations'] = TRUE;
      }
      $table_form['operation'][$method_id] = array(
       '#type' => 'operations',
       '#links' => $config_op,
      );
    }
  }

  $form[$type] = $table_form;
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
    $title = '<h4 class="label">' . $form[$type]['#title'] . '</h4>';
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
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => array('id' => "language-negotiation-methods-$type"),
    );
    $table  = drupal_render($form[$type]['configurable']);
    $table .= drupal_render($build);
    $table .= drupal_render_children($form[$type]);

    drupal_add_tabledrag("language-negotiation-methods-$type", 'order', 'sibling', "language-method-weight-$type");

    $output .= '<div class="form-item table-language-group table-' . $type . '-wrapper">' . $title . $description . $table . '</div>';
  }

  $output .= drupal_render_children($form);
  return $output;
}

/**
 * Submit handler for language negotiation settings.
 */
function language_negotiation_configure_form_submit($form, &$form_state) {
  $configurable_types = $form['#language_types'];

  $stored_values = \Drupal::config('system.language.types')->get('configurable');
  $customized = array();
  $method_weights_type = array();

  foreach ($configurable_types as $type) {
    $customized[$type] = in_array($type, $stored_values);
    $method_weights = array();
    $enabled_methods = $form_state['values'][$type]['enabled'];
    $enabled_methods[LANGUAGE_NEGOTIATION_SELECTED] = TRUE;
    $method_weights_input = $form_state['values'][$type]['weight'];
    if (isset($form_state['values'][$type]['configurable'])) {
      $customized[$type] = !empty($form_state['values'][$type]['configurable']);
    }

    foreach ($method_weights_input as $method_id => $weight) {
      if ($enabled_methods[$method_id]) {
        $method_weights[$method_id] = $weight;
      }
    }

    $method_weights_type[$type] = $method_weights;
    variable_set("language_negotiation_methods_weight_$type", $method_weights_input);
  }

  // Update non-configurable language types and the related language negotiation
  // configuration.
  language_types_set(array_keys(array_filter($customized)));

  // Update the language negotiations after setting the configurability.
  foreach ($method_weights_type as $type => $method_weights) {
    language_negotiation_set($type, $method_weights);
  }

  // Clear block definitions cache since the available blocks and their names
  // may have been changed based on the configurable types.
  if (\Drupal::moduleHandler()->moduleExists('block')) {
    // If there is an active language switcher for a language type that has been
    // made not configurable, deactivate it first.
    $non_configurable = array_keys(array_diff($customized, array_filter($customized)));
    _language_disable_language_switcher($non_configurable);
    \Drupal::service('plugin.manager.block')->clearCachedDefinitions();
  }

  $form_state['redirect'] = 'admin/config/regional/language/detection';
  drupal_set_message(t('Language negotiation configuration saved.'));
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
    '#theme' => 'table',
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
 * @deprecated Use \Drupal\language\Controller\LanguageController::contentSettings()
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
  $entity_info = entity_get_info();
  $labels = array();
  $default = array();

  foreach ($supported as $entity_type) {
    $labels[$entity_type] = isset($entity_info[$entity_type]['label']) ? $entity_info[$entity_type]['label'] : $entity_type;
    $default[$entity_type] = FALSE;

    // Check whether we have any custom setting.
    foreach (entity_get_bundles($entity_type) as $bundle => $bundle_info) {
      $conf = language_get_default_configuration($entity_type, $bundle);
      if (!empty($conf['language_show']) || $conf['langcode'] != 'site_default') {
        $default[$entity_type] = $entity_type;
      }
      $language_configuration[$entity_type][$bundle] = $conf;
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

  foreach ($labels as $entity_type => $label) {
    $info = $entity_info[$entity_type];

    $form['settings'][$entity_type] = array(
      '#title' => $label,
      '#type' => 'container',
      '#entity_type' => $entity_type,
      '#theme' => 'language_content_settings_table',
      '#bundle_label' => isset($info['bundle_label']) ? $info['bundle_label'] : $label,
      '#states' => array(
        'visible' => array(
          ':input[name="entity_types[' . $entity_type . ']"]' => array('checked' => TRUE),
        ),
      ),
    );

    foreach (entity_get_bundles($entity_type) as $bundle => $bundle_info) {
      $form['settings'][$entity_type][$bundle]['settings'] = array(
        '#type' => 'item',
        '#label' => $bundle_info['label'],
        'language' => array(
          '#type' => 'language_configuration',
          '#entity_information' => array(
            'entity_type' => $entity_type,
            'bundle' => $bundle,
          ),
          '#default_value' => $language_configuration[$entity_type][$bundle],
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
            '#markup' => check_plain($element[$bundle]['settings']['#label']),
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
    '#theme' => 'table',
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
