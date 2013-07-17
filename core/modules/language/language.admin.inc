<?php

/**
 * @file
 * Administration functions for language.module.
 */

use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageManager;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * User interface for the language addition screen.
 */
function language_admin_add_form($form, &$form_state) {
  $predefined_languages = language_admin_predefined_list();
  $predefined_languages['custom'] = t('Custom language...');
  $predefined_default = !empty($form_state['values']['predefined_langcode']) ? $form_state['values']['predefined_langcode'] : key($predefined_languages);
  $form['predefined_langcode'] = array(
    '#type' => 'select',
    '#title' => t('Language name'),
    '#default_value' => $predefined_default,
    '#options' => $predefined_languages,
  );
  $form['predefined_submit'] = array(
    '#type' => 'submit',
    '#value' => t('Add language'),
    '#limit_validation_errors' => array(array('predefined_langcode'), array('predefined_submit')),
    '#states' => array(
      'invisible' => array(
        'select#edit-predefined-langcode' => array('value' => 'custom'),
      ),
    ),
    '#validate' => array('language_admin_add_predefined_form_validate'),
    '#submit' => array('language_admin_add_form_submit'),
  );

  $form['custom_language'] = array(
    '#type' => 'container',
    '#states' => array(
      'visible' => array(
        'select#edit-predefined-langcode' => array('value' => 'custom'),
      ),
    ),
  );
  _language_admin_common_controls($form['custom_language']);
  $form['custom_language']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Add custom language'),
    '#validate' => array('language_admin_add_custom_form_validate'),
    '#submit' => array('language_admin_add_form_submit'),
  );

  return $form;
}

/**
 * Editing screen for a particular language.
 *
 * @param $langcode
 *   Language code of the language to edit.
 */
function language_admin_edit_form($form, &$form_state, $language) {
  _language_admin_common_controls($form, $language);
  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save language')
  );
  $form['#submit'][] = 'language_admin_edit_form_submit';
  $form['#validate'][] = 'language_admin_edit_form_validate';
  return $form;
}

/**
 * Common elements of the language addition and editing form.
 *
 * @param $form
 *   A parent form item (or empty array) to add items below.
 * @param $language
 *   Language object to edit.
 */
function _language_admin_common_controls(&$form, $language = NULL) {
  if (!is_object($language)) {
    $language = new Language(array('id' => NULL, 'name' => NULL,));
  }
  if (isset($language->id)) {
    $form['langcode_view'] = array(
      '#type' => 'item',
      '#title' => t('Language code'),
      '#markup' => $language->id
    );
    $form['langcode'] = array(
      '#type' => 'value',
      '#value' => $language->id
    );
  }
  else {
    $form['langcode'] = array(
      '#type' => 'textfield',
      '#title' => t('Language code'),
      '#maxlength' => 12,
      '#required' => TRUE,
      '#default_value' => '',
      '#disabled' => FALSE,
      '#description' => t('Use language codes as <a href="@w3ctags">defined by the W3C</a> for interoperability. <em>Examples: "en", "en-gb" and "zh-hant".</em>', array('@w3ctags' => 'http://www.w3.org/International/articles/language-tags/')),
    );
  }
  $form['name'] = array(
    '#type' => 'textfield',
    '#title' => t('Language name in English'),
    '#maxlength' => 64,
    '#default_value' => @$language->name,
    '#required' => TRUE,
  );
  $form['direction'] = array(
    '#type' => 'radios',
    '#title' => t('Direction'),
    '#required' => TRUE,
    '#description' => t('Direction that text in this language is presented.'),
    '#default_value' => @$language->direction,
    '#options' => array(Language::DIRECTION_LTR => t('Left to right'), Language::DIRECTION_RTL => t('Right to left')),
  );
  return $form;
}

/**
 * Element specific validator for the Add language button.
 */
function language_admin_add_predefined_form_validate($form, &$form_state) {
  $langcode = $form_state['values']['predefined_langcode'];
  if ($langcode == 'custom') {
    form_set_error('predefined_langcode', t('Fill in the language details and save the language with <em>Add custom language</em>.'));
  }
  else {
    if ($language = language_load($langcode)) {
      form_set_error('predefined_langcode', t('The language %language (%langcode) already exists.', array('%language' => $language->name, '%langcode' => $langcode)));
    }
  }
}

/**
 * Validate the language addition form on custom language button.
 */
function language_admin_add_custom_form_validate($form, &$form_state) {
  if ($form_state['values']['predefined_langcode'] == 'custom') {
    $langcode = $form_state['values']['langcode'];
    // Reuse the editing form validation routine if we add a custom language.
    language_admin_edit_form_validate($form['custom_language'], $form_state);

    if ($language = language_load($langcode)) {
      form_set_error('langcode', t('The language %language (%langcode) already exists.', array('%language' => $language->name, '%langcode' => $langcode)));
    }
  }
  else {
    form_set_error('predefined_langcode', t('Use the <em>Add language</em> button to save a predefined language.'));
  }
}

/**
 * Process the custom and predefined language addition form submission.
 */
function language_admin_add_form_submit($form, &$form_state) {
  $langcode = $form_state['values']['predefined_langcode'];
  if ($langcode == 'custom') {
    $langcode = $form_state['values']['langcode'];
    // Custom language form.
    $language = new Language(array(
      'id' => $langcode,
      'name' => $form_state['values']['name'],
      'direction' => $form_state['values']['direction'],
    ));
  }
  else {
    $language = new Language(array('id' => $langcode));
  }
  // Save the language and inform the user that it happened.
  $language = language_save($language);
  drupal_set_message(t('The language %language has been created and can now be used.', array('%language' => $language->name)));

  // Tell the user they have the option to add a language switcher block
  // to their theme so they can switch between the languages.
  drupal_set_message(t('Use one of the language switcher blocks to allow site visitors to switch between languages. You can enable these blocks on the <a href="@block-admin">block administration page</a>.', array('@block-admin' => url('admin/structure/block'))));

  $form_state['redirect'] = 'admin/config/regional/language';
}

/**
 * Validate the language editing form. Reused for custom language addition too.
 */
function language_admin_edit_form_validate($form, &$form_state) {
  // Ensure sane field values for langcode and name.
  if (!isset($form['langcode_view']) && preg_match('@[^a-zA-Z_-]@', $form_state['values']['langcode'])) {
    form_set_error('langcode', t('%field may only contain characters a-z, underscores, or hyphens.', array('%field' => $form['langcode']['#title'])));
  }
  if ($form_state['values']['name'] != check_plain($form_state['values']['name'])) {
    form_set_error('name', t('%field cannot contain any markup.', array('%field' => $form['name']['#title'])));
  }
}

/**
 * Process the language editing form submission.
 */
function language_admin_edit_form_submit($form, &$form_state) {
  // Prepare a language object for saving.
  $languages = language_list();
  $langcode = $form_state['values']['langcode'];
  $language = $languages[$langcode];
  $language->name = $form_state['values']['name'];
  $language->direction = $form_state['values']['direction'];
  language_save($language);
  $form_state['redirect'] = 'admin/config/regional/language';
}

/**
 * User interface for the language deletion confirmation screen.
 */
function language_admin_delete_form($form, &$form_state, $language) {
  $langcode = $language->id;

  if (language_default()->id == $langcode) {
    drupal_set_message(t('The default language cannot be deleted.'));
    return new RedirectResponse(url('admin/config/regional/language', array('absolute' => TRUE)));
  }

  // For other languages, warn the user that data loss is ahead.
  $languages = language_list();

  if (!isset($languages[$langcode])) {
    throw new NotFoundHttpException();
  }
  else {
    $form['langcode'] = array('#type' => 'value', '#value' => $langcode);
    return confirm_form($form, t('Are you sure you want to delete the language %language?', array('%language' => $languages[$langcode]->name)), 'admin/config/regional/language', t('Deleting a language will remove all interface translations associated with it, and posts in this language will be set to be language neutral. This action cannot be undone.'), t('Delete'), t('Cancel'));
  }
}

/**
 * Process language deletion submissions.
 */
function language_admin_delete_form_submit($form, &$form_state) {
  $langcode = $form_state['values']['langcode'];
  $languages = language_list();
  $language = $languages[$langcode];

  $success = language_delete($langcode);

  if ($success) {
    $t_args = array('%language' => $language->name, '%langcode' => $language->id);
    drupal_set_message(t('The %language (%langcode) language has been removed.', $t_args));
  }

  $form_state['redirect'] = 'admin/config/regional/language';
}

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
  $configurable = config('system.language.types')->get('configurable');
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
    $configurable = config('system.language.types')->get('configurable');
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

  $stored_values = config('system.language.types')->get('configurable');
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
  if (module_exists('block')) {
    // If there is an active language switcher for a language type that has been
    // made not configurable, deactivate it first.
    $non_configurable = array_keys(array_diff($customized, array_filter($customized)));
    _language_disable_language_switcher($non_configurable);
    Drupal::service('plugin.manager.block')->clearCachedDefinitions();
  }

  $form_state['redirect'] = 'admin/config/regional/language/detection';
  drupal_set_message(t('Language negotiation configuration saved.'));
}

/**
 * Builds the browser language negotiation method configuration form.
 */
function language_negotiation_configure_browser_form($form, &$form_state) {
  $form = array();

  // Initialize a language list to the ones available, including English.
  $languages = language_list();

  $existing_languages = array();
  foreach ($languages as $langcode => $language) {
    $existing_languages[$langcode] = $language->name;
  }

  // If we have no languages available, present the list of predefined languages
  // only. If we do have already added languages, set up two option groups with
  // the list of existing and then predefined languages.
  if (empty($existing_languages)) {
    $language_options = language_admin_predefined_list();
  }
  else {
    $language_options = array(
      t('Existing languages') => $existing_languages,
      t('Languages not yet added') => language_admin_predefined_list()
    );
  }

  $form['mappings'] = array(
    '#tree' => TRUE,
    '#theme' => 'language_negotiation_configure_browser_form_table',
  );

  $mappings = language_get_browser_drupal_langcode_mappings();
  foreach ($mappings as $browser_langcode => $drupal_langcode) {
    $form['mappings'][$browser_langcode] = array(
      'browser_langcode' => array(
        '#type' => 'textfield',
        '#default_value' => $browser_langcode,
        '#size' => 20,
        '#required' => TRUE,
      ),
      'drupal_langcode' => array(
        '#type' => 'select',
        '#options' => $language_options,
        '#default_value' => $drupal_langcode,
        '#required' => TRUE,
      ),
    );
  }

  // Add empty row.
  $form['new_mapping'] = array(
    '#type' => 'details',
    '#title' => t('Add a new mapping'),
    '#collapsed' => TRUE,
    '#tree' => TRUE,
  );
  $form['new_mapping']['browser_langcode'] = array(
    '#type' => 'textfield',
    '#title' => t('Browser language code'),
    '#description' => t('Use language codes as <a href="@w3ctags">defined by the W3C</a> for interoperability. <em>Examples: "en", "en-gb" and "zh-hant".</em>', array('@w3ctags' => 'http://www.w3.org/International/articles/language-tags/')),
    '#default_value' => '',
    '#size' => 20,
  );
  $form['new_mapping']['drupal_langcode'] = array(
    '#type' => 'select',
    '#title' => t('Drupal language'),
    '#options' => $language_options,
    '#default_value' => '',
  );

  $form['actions']['#type'] = 'actions';
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save configuration'),
  );

  return $form;
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
 * Browser language negotiation form validation.
 */
function language_negotiation_configure_browser_form_validate($form, &$form_state) {
  // Array to check if all browser language codes are unique.
  $unique_values = array();

  // Check all mappings.
  $mappings = array();
  if (isset($form_state['values']['mappings'])) {
    $mappings = $form_state['values']['mappings'];
    foreach ($mappings as $key => $data) {
      // Make sure browser_langcode is unique.
      if (array_key_exists($data['browser_langcode'], $unique_values)) {
        form_set_error('mappings][' . $key . '][browser_langcode', t('Browser language codes must be unique.'));
      }
      elseif (preg_match('/[^a-z\-]/', $data['browser_langcode'])) {
        form_set_error('mappings][' . $key . '][browser_langcode', t('Browser language codes can only contain lowercase letters and a hyphen(-).'));
      }
      $unique_values[$data['browser_langcode']] = $data['drupal_langcode'];
    }
  }

  // Check new mapping.
  $data = $form_state['values']['new_mapping'];
  if (!empty($data['browser_langcode'])) {
    // Make sure browser_langcode is unique.
    if (array_key_exists($data['browser_langcode'], $unique_values)) {
      form_set_error('mappings][' . $key . '][browser_langcode', t('Browser language codes must be unique.'));
    }
    elseif (preg_match('/[^a-z\-]/', $data['browser_langcode'])) {
      form_set_error('mappings][' . $key . '][browser_langcode', t('Browser language codes can only contain lowercase letters and a hyphen(-).'));
    }
    $unique_values[$data['browser_langcode']] = $data['drupal_langcode'];
  }

  $form_state['mappings'] = $unique_values;
}

/**
 * Browser language negotiation form submit.
 */
function language_negotiation_configure_browser_form_submit($form, &$form_state) {
  $mappings = $form_state['mappings'];
  if (!empty($mappings)) {
    language_set_browser_drupal_langcode_mappings($mappings);
  }
  $form_state['redirect'] = 'admin/config/regional/language/detection';
}

/**
 * Form for deleting a browser language negotiation mapping.
 */
function language_negotiation_configure_browser_delete_form($form, &$form_state, $browser_langcode) {
  $form_state['browser_langcode'] = $browser_langcode;
  $question = t('Are you sure you want to delete %browser_langcode?', array(
    '%browser_langcode' => $browser_langcode,
  ));
  $path = 'admin/config/regional/language/detection/browser';
  return confirm_form($form, $question, $path, '');
}

/**
 * Form submit handler to delete a browser language negotiation mapping.
 */
function language_negotiation_configure_browser_delete_form_submit($form, &$form_state) {
  $browser_langcode = $form_state['browser_langcode'];
  $mappings = language_get_browser_drupal_langcode_mappings();
  if (array_key_exists($browser_langcode, $mappings)) {
    unset($mappings[$browser_langcode]);
    language_set_browser_drupal_langcode_mappings($mappings);
  }
  $form_state['redirect'] = 'admin/config/regional/language/detection/browser';
}

/**
 * Returns the content language settings form.
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
  $entity_types = $form_state['values']['entity_types'];
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
