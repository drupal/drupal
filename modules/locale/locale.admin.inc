<?php

/**
 * @file
 * Administration functions for locale.module.
 */

/**
 * @defgroup locale-language-administration Language administration interface
 * @{
 * Administration interface for languages.
 *
 * These functions provide the user interface to show, add, edit and
 * delete languages as well as providing options for language negotiation.
 */

/**
 * User interface for the language overview screen.
 */
function locale_languages_overview_form() {
  drupal_static_reset('language');
  $languages = language_list('language');

  $options = array();
  $form['weight'] = array('#tree' => TRUE);
  foreach ($languages as $langcode => $language) {

    $options[$langcode] = '';
    if ($language->enabled) {
      $enabled[] = $langcode;
    }
    $form['weight'][$langcode] = array(
      '#type' => 'weight',
      '#title' => t('Weight for @title', array('@title' => $language->name)),
      '#title_display' => 'invisible',
      '#default_value' => $language->weight,
      '#attributes' => array('class' => array('language-order-weight')),
    );
    $form['name'][$langcode] = array('#markup' => check_plain($language->name));
    $form['native'][$langcode] = array('#markup' => check_plain($language->native));
    $form['direction'][$langcode] = array('#markup' => ($language->direction == LANGUAGE_RTL ? t('Right to left') : t('Left to right')));
  }
  $form['enabled'] = array(
    '#type' => 'checkboxes',
    '#title' => t('Enabled languages'),
    '#title_display' => 'invisible',
    '#options' => $options,
    '#default_value' => $enabled,
  );
  $form['site_default'] = array(
    '#type' => 'radios',
    '#title' => t('Default language'),
    '#title_display' => 'invisible',
    '#options' => $options,
    '#default_value' => language_default('language'),
  );
  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array('#type' => 'submit', '#value' => t('Save configuration'));
  $form['#theme'] = 'locale_languages_overview_form';

  return $form;
}

/**
 * Returns HTML for the language overview form.
 *
 * @param $variables
 *   An associative array containing:
 *   - form: A render element representing the form.
 *
 * @ingroup themeable
 */
function theme_locale_languages_overview_form($variables) {
  $form = $variables['form'];
  $default = language_default();
  foreach ($form['name'] as $key => $element) {
    // Do not take form control structures.
    if (is_array($element) && element_child($key)) {
      // Disable checkbox for the default language, because it cannot be disabled.
      if ($key == $default->language) {
        $form['enabled'][$key]['#attributes']['disabled'] = 'disabled';
      }

      // Add invisible labels for the checkboxes and radio buttons in the table
      // for accessibility. These changes are only required and valid when the
      // form is themed as a table, so it would be wrong to perform them in the
      // form constructor.
      $title = drupal_render($form['name'][$key]);
      $form['enabled'][$key]['#title'] = t('Enable !title', array('!title' => $title));
      $form['enabled'][$key]['#title_display'] = 'invisible';
      $form['site_default'][$key]['#title'] = t('Set !title as default', array('!title' => $title));
      $form['site_default'][$key]['#title_display'] = 'invisible';
      $rows[] = array(
        'data' => array(
          '<strong>' . $title . '</strong>',
          drupal_render($form['native'][$key]),
          check_plain($key),
          drupal_render($form['direction'][$key]),
          array('data' => drupal_render($form['enabled'][$key]), 'align' => 'center'),
          drupal_render($form['site_default'][$key]),
          drupal_render($form['weight'][$key]),
          l(t('edit'), 'admin/config/regional/language/edit/' . $key) . (($key != 'en' && $key != $default->language) ? ' ' . l(t('delete'), 'admin/config/regional/language/delete/' . $key) : '')
        ),
        'class' => array('draggable'),
      );
    }
  }
  $header = array(array('data' => t('English name')), array('data' => t('Native name')), array('data' => t('Code')), array('data' => t('Direction')), array('data' => t('Enabled')), array('data' => t('Default')), array('data' => t('Weight')), array('data' => t('Operations')));
  $output = theme('table', array('header' => $header, 'rows' => $rows, 'attributes' => array('id' => 'language-order')));
  $output .= drupal_render_children($form);

  drupal_add_tabledrag('language-order', 'order', 'sibling', 'language-order-weight');

  return $output;
}

/**
 * Process language overview form submissions, updating existing languages.
 */
function locale_languages_overview_form_submit($form, &$form_state) {
  $languages = language_list();
  $default = language_default();
  $url_prefixes = variable_get('locale_language_negotiation_url_part', LOCALE_LANGUAGE_NEGOTIATION_URL_PREFIX) == LOCALE_LANGUAGE_NEGOTIATION_URL_PREFIX;
  $enabled_count = 0;

  foreach ($languages as $langcode => $language) {
    if ($form_state['values']['site_default'] == $langcode || $default->language == $langcode) {
      // Automatically enable the default language and the language
      // which was default previously (because we will not get the
      // value from that disabled checkbox).
      $form_state['values']['enabled'][$langcode] = 1;
    }

    // If language URL prefixes are enabled we must clear language domains and
    // assign a valid prefix to each non-default language.
    if ($url_prefixes) {
      $language->domain = '';
      if (empty($language->prefix) && $form_state['values']['site_default'] != $langcode) {
        $language->prefix = $langcode;
      }
    }

    if ($form_state['values']['enabled'][$langcode]) {
      $enabled_count++;
      $language->enabled = 1;
    }
    else {
      $language->enabled = 0;
    }

    $language->weight = $form_state['values']['weight'][$langcode];

    db_update('languages')
      ->fields(array(
        'enabled' => $language->enabled,
        'weight' => $language->weight,
        'prefix' => $language->prefix,
        'domain' => $language->domain,
      ))
      ->condition('language', $langcode)
      ->execute();

    $languages[$langcode] = $language;
  }

  variable_set('language_default', $languages[$form_state['values']['site_default']]);
  variable_set('language_count', $enabled_count);
  drupal_set_message(t('Configuration saved.'));

  // Changing the language settings impacts the interface.
  cache_clear_all('*', 'cache_page', TRUE);
  module_invoke_all('multilingual_settings_changed');

  $form_state['redirect'] = 'admin/config/regional/language';
  return;
}

/**
 * User interface for the language addition screen.
 */
function locale_languages_add_screen() {
  $build['predefined'] = drupal_get_form('locale_languages_predefined_form');
  $build['custom'] = drupal_get_form('locale_languages_custom_form');
  return $build;
}

/**
 * Predefined language setup form.
 */
function locale_languages_predefined_form($form) {
  $predefined = _locale_prepare_predefined_list();
  $form['language list'] = array('#type' => 'fieldset',
    '#title' => t('Predefined language'),
    '#collapsible' => TRUE,
  );
  $form['language list']['langcode'] = array('#type' => 'select',
    '#title' => t('Language name'),
    '#default_value' => key($predefined),
    '#options' => $predefined,
    '#description' => t('Use the <em>Custom language</em> section below if your desired language does not appear in this list.'),
  );
  $form['language list']['actions'] = array('#type' => 'actions');
  $form['language list']['actions']['submit'] = array('#type' => 'submit', '#value' => t('Add language'));
  return $form;
}

/**
 * Custom language addition form.
 */
function locale_languages_custom_form($form) {
  $form['custom language'] = array('#type' => 'fieldset',
    '#title' => t('Custom language'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  );
  _locale_languages_common_controls($form['custom language']);
  $form['custom language']['actions'] = array('#type' => 'actions');
  $form['custom language']['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Add custom language')
  );
  // Reuse the validation and submit functions of the predefined language setup form.
  $form['#submit'][] = 'locale_languages_predefined_form_submit';
  $form['#validate'][] = 'locale_languages_predefined_form_validate';
  return $form;
}

/**
 * Editing screen for a particular language.
 *
 * @param $langcode
 *   Language code of the language to edit.
 */
function locale_languages_edit_form($form, &$form_state, $langcode) {
  if ($language = db_query("SELECT * FROM {languages} WHERE language = :language", array(':language' => $langcode))->fetchObject()) {
    _locale_languages_common_controls($form, $language);
    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save language')
    );
    $form['#submit'][] = 'locale_languages_edit_form_submit';
    $form['#validate'][] = 'locale_languages_edit_form_validate';
    return $form;
  }
  else {
    drupal_not_found();
    drupal_exit();
  }
}

/**
 * Common elements of the language addition and editing form.
 *
 * @param $form
 *   A parent form item (or empty array) to add items below.
 * @param $language
 *   Language object to edit.
 */
function _locale_languages_common_controls(&$form, $language = NULL) {
  if (!is_object($language)) {
    $language = new stdClass();
  }
  if (isset($language->language)) {
    $form['langcode_view'] = array(
      '#type' => 'item',
      '#title' => t('Language code'),
      '#markup' => $language->language
    );
    $form['langcode'] = array(
      '#type' => 'value',
      '#value' => $language->language
    );
  }
  else {
    $form['langcode'] = array('#type' => 'textfield',
      '#title' => t('Language code'),
      '#maxlength' => 12,
      '#required' => TRUE,
      '#default_value' => @$language->language,
      '#disabled' => (isset($language->language)),
      '#description' => t('<a href="@rfc4646">RFC 4646</a> compliant language identifier. Language codes typically use a country code, and optionally, a script or regional variant name. <em>Examples: "en", "en-US" and "zh-Hant".</em>', array('@rfc4646' => 'http://www.ietf.org/rfc/rfc4646.txt')),
    );
  }
  $form['name'] = array('#type' => 'textfield',
    '#title' => t('Language name in English'),
    '#maxlength' => 64,
    '#default_value' => @$language->name,
    '#required' => TRUE,
    '#description' => t('Name of the language in English. Will be available for translation in all languages.'),
  );
  $form['native'] = array('#type' => 'textfield',
    '#title' => t('Native language name'),
    '#maxlength' => 64,
    '#default_value' => @$language->native,
    '#required' => TRUE,
    '#description' => t('Name of the language in the language being added.'),
  );
  $form['prefix'] = array('#type' => 'textfield',
    '#title' => t('Path prefix language code'),
    '#maxlength' => 64,
    '#default_value' => @$language->prefix,
    '#description' => t('Language code or other custom text to use as a path prefix for URL language detection, if your <em>Detection and selection</em> settings use URL path prefixes. For the default language, this value may be left blank. <strong>Modifying this value may break existing URLs. Use with caution in a production environment.</strong> Example: Specifying "deutsch" as the path prefix code for German results in URLs like "example.com/deutsch/contact".')
  );
  $form['domain'] = array('#type' => 'textfield',
    '#title' => t('Language domain'),
    '#maxlength' => 128,
    '#default_value' => @$language->domain,
    '#description' => t('The domain name to use for this language if URL domains are used for <em>Detection and selection</em>. Leave blank for the default language. <strong>Changing this value may break existing URLs.</strong> Example: Specifying "de.example.com" as language domain for German will result in an URL like "http://de.example.com/contact".'),
  );
  $form['direction'] = array('#type' => 'radios',
    '#title' => t('Direction'),
    '#required' => TRUE,
    '#description' => t('Direction that text in this language is presented.'),
    '#default_value' => @$language->direction,
    '#options' => array(LANGUAGE_LTR => t('Left to right'), LANGUAGE_RTL => t('Right to left'))
  );
  return $form;
}

/**
 * Validate the language addition form.
 */
function locale_languages_predefined_form_validate($form, &$form_state) {
  $langcode = $form_state['values']['langcode'];

  if (($duplicate = db_query("SELECT COUNT(*) FROM {languages} WHERE language = :language", array(':language' => $langcode))->fetchField()) != 0) {
    form_set_error('langcode', t('The language %language (%code) already exists.', array('%language' => $form_state['values']['name'], '%code' => $langcode)));
  }

  if (!isset($form_state['values']['name'])) {
    // Predefined language selection.
    include_once DRUPAL_ROOT . '/includes/iso.inc';
    $predefined = _locale_get_predefined_list();
    if (!isset($predefined[$langcode])) {
      form_set_error('langcode', t('Invalid language code.'));
    }
  }
  else {
    // Reuse the editing form validation routine if we add a custom language.
    locale_languages_edit_form_validate($form, $form_state);
  }
}

/**
 * Process the language addition form submission.
 */
function locale_languages_predefined_form_submit($form, &$form_state) {
  $langcode = $form_state['values']['langcode'];
  if (isset($form_state['values']['name'])) {
    // Custom language form.
    locale_add_language($langcode, $form_state['values']['name'], $form_state['values']['native'], $form_state['values']['direction'], $form_state['values']['domain'], $form_state['values']['prefix']);
    drupal_set_message(t('The language %language has been created and can now be used. More information is available on the <a href="@locale-help">help screen</a>.', array('%language' => t($form_state['values']['name']), '@locale-help' => url('admin/help/locale'))));
  }
  else {
    // Predefined language selection.
    include_once DRUPAL_ROOT . '/includes/iso.inc';
    $predefined = _locale_get_predefined_list();
    locale_add_language($langcode);
    drupal_set_message(t('The language %language has been created and can now be used. More information is available on the <a href="@locale-help">help screen</a>.', array('%language' => t($predefined[$langcode][0]), '@locale-help' => url('admin/help/locale'))));
  }

  // See if we have language files to import for the newly added
  // language, collect and import them.
  if ($batch = locale_batch_by_language($langcode, '_locale_batch_language_finished')) {
    batch_set($batch);
  }

  $form_state['redirect'] = 'admin/config/regional/language';
}

/**
 * Validate the language editing form. Reused for custom language addition too.
 */
function locale_languages_edit_form_validate($form, &$form_state) {
  // Ensure sane field values for langcode, name, and native.
  if (!isset($form['langcode_view']) && preg_match('@[^a-zA-Z_-]@', $form_state['values']['langcode'])) {
    form_set_error('langcode', t('%field may only contain characters a-z, underscores, or hyphens.', array('%field' => $form['langcode']['#title'])));
  }
  if ($form_state['values']['name'] != check_plain($form_state['values']['name'])) {
    form_set_error('name', t('%field cannot contain any markup.', array('%field' => $form['name']['#title'])));
  }
  if ($form_state['values']['native'] != check_plain($form_state['values']['native'])) {
    form_set_error('native', t('%field cannot contain any markup.', array('%field' => $form['native']['#title'])));
  }

  if (!empty($form_state['values']['domain']) && !empty($form_state['values']['prefix'])) {
    form_set_error('prefix', t('Domain and path prefix values should not be set at the same time.'));
  }
  if (!empty($form_state['values']['domain']) && $duplicate = db_query("SELECT language FROM {languages} WHERE domain = :domain AND language <> :language", array(':domain' => $form_state['values']['domain'], ':language' => $form_state['values']['langcode']))->fetchField()) {
    form_set_error('domain', t('The domain (%domain) is already tied to a language (%language).', array('%domain' => $form_state['values']['domain'], '%language' => $duplicate)));
  }
  if (empty($form_state['values']['prefix']) && language_default('language') != $form_state['values']['langcode'] && empty($form_state['values']['domain'])) {
    form_set_error('prefix', t('Only the default language can have both the domain and prefix empty.'));
  }
  if (!empty($form_state['values']['prefix']) && $duplicate = db_query("SELECT language FROM {languages} WHERE prefix = :prefix AND language <> :language", array(':prefix' => $form_state['values']['prefix'], ':language' => $form_state['values']['langcode']))->fetchField()) {
    form_set_error('prefix', t('The prefix (%prefix) is already tied to a language (%language).', array('%prefix' => $form_state['values']['prefix'], '%language' => $duplicate)));
  }
}

/**
 * Process the language editing form submission.
 */
function locale_languages_edit_form_submit($form, &$form_state) {
  db_update('languages')
    ->fields(array(
      'name' => $form_state['values']['name'],
      'native' => $form_state['values']['native'],
      'domain' => $form_state['values']['domain'],
      'prefix' => $form_state['values']['prefix'],
      'direction' => $form_state['values']['direction'],
    ))
    ->condition('language',  $form_state['values']['langcode'])
    ->execute();
  $default = language_default();
  if ($default->language == $form_state['values']['langcode']) {
    $properties = array('name', 'native', 'direction', 'enabled', 'plurals', 'formula', 'domain', 'prefix', 'weight');
    foreach ($properties as $keyname) {
      if (isset($form_state['values'][$keyname])) {
        $default->$keyname = $form_state['values'][$keyname];
      }
    }
    variable_set('language_default', $default);
  }
  $form_state['redirect'] = 'admin/config/regional/language';
  return;
}

/**
 * User interface for the language deletion confirmation screen.
 */
function locale_languages_delete_form($form, &$form_state, $langcode) {

  // Do not allow deletion of English locale.
  if ($langcode == 'en') {
    drupal_set_message(t('The English language cannot be deleted.'));
    drupal_goto('admin/config/regional/language');
  }

  if (language_default('language') == $langcode) {
    drupal_set_message(t('The default language cannot be deleted.'));
    drupal_goto('admin/config/regional/language');
  }

  // For other languages, warn user that data loss is ahead.
  $languages = language_list();

  if (!isset($languages[$langcode])) {
    drupal_not_found();
    drupal_exit();
  }
  else {
    $form['langcode'] = array('#type' => 'value', '#value' => $langcode);
    return confirm_form($form, t('Are you sure you want to delete the language %name?', array('%name' => t($languages[$langcode]->name))), 'admin/config/regional/language', t('Deleting a language will remove all interface translations associated with it, and posts in this language will be set to be language neutral. This action cannot be undone.'), t('Delete'), t('Cancel'));
  }
}

/**
 * Process language deletion submissions.
 */
function locale_languages_delete_form_submit($form, &$form_state) {
  $languages = language_list();
  if (isset($languages[$form_state['values']['langcode']])) {
    // Remove translations first.
    db_delete('locales_target')
      ->condition('language', $form_state['values']['langcode'])
      ->execute();
    cache_clear_all('locale:' . $form_state['values']['langcode'], 'cache');
    // With no translations, this removes existing JavaScript translations file.
    _locale_rebuild_js($form_state['values']['langcode']);
    // Remove the language.
    db_delete('languages')
      ->condition('language', $form_state['values']['langcode'])
      ->execute();
    db_update('node')
      ->fields(array('language' => ''))
      ->condition('language', $form_state['values']['langcode'])
      ->execute();
    if ($languages[$form_state['values']['langcode']]->enabled) {
      variable_set('language_count', variable_get('language_count', 1) - 1);
    }
    module_invoke_all('multilingual_settings_changed');
    $variables = array('%locale' => $languages[$form_state['values']['langcode']]->name);
    drupal_set_message(t('The language %locale has been removed.', $variables));
    watchdog('locale', 'The language %locale has been removed.', $variables);
  }

  // Changing the language settings impacts the interface:
  cache_clear_all('*', 'cache_page', TRUE);

  $form_state['redirect'] = 'admin/config/regional/language';
  return;
}

/**
 * Setting for language negotiation options
 */
function locale_languages_configure_form() {
  include_once DRUPAL_ROOT . '/includes/language.inc';

  $form = array(
    '#submit' => array('locale_languages_configure_form_submit'),
    '#theme' => 'locale_languages_configure_form',
    '#language_types' => language_types_configurable(FALSE),
    '#language_types_info' => language_types_info(),
    '#language_providers' => language_negotiation_info(),
  );

  foreach ($form['#language_types'] as $type) {
    _locale_languages_configure_form_language_table($form, $type);
  }

  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save settings'),
  );

  return $form;
}

/**
 * Helper function to build a language provider table.
 */
function _locale_languages_configure_form_language_table(&$form, $type) {
  $info = $form['#language_types_info'][$type];

  $table_form = array(
    '#title' => t('@type language detection', array('@type' => $info['name'])),
    '#tree' => TRUE,
    '#description' => $info['description'],
    '#language_providers' => array(),
    '#show_operations' => FALSE,
    'weight' => array('#tree' => TRUE),
    'enabled' => array('#tree' => TRUE),
  );

  $language_providers = $form['#language_providers'];
  $enabled_providers = variable_get("language_negotiation_$type", array());
  $providers_weight = variable_get("locale_language_providers_weight_$type", array());

  // Add missing data to the providers lists.
  foreach ($language_providers as $id => $provider) {
    if (!isset($providers_weight[$id])) {
      $providers_weight[$id] = language_provider_weight($provider);
    }
  }

  // Order providers list by weight.
  asort($providers_weight);

  foreach ($providers_weight as $id => $weight) {
    // A language provider might be no more available if the defining module has
    // been disabled after the last configuration saving.
    if (!isset($language_providers[$id])) {
      continue;
    }

    $enabled = isset($enabled_providers[$id]);
    $provider = $language_providers[$id];

    // List the provider only if the current type is defined in its 'types' key.
    // If it is not defined default to all the configurable language types.
    $types = array_flip(isset($provider['types']) ? $provider['types'] : $form['#language_types']);

    if (isset($types[$type])) {
      $table_form['#language_providers'][$id] = $provider;

      $table_form['weight'][$id] = array(
        '#type' => 'weight',
        '#title' => t('Weight for @title', array('@title' => $provider['name'])),
        '#title_display' => 'invisible',
        '#default_value' => $weight,
        '#attributes' => array('class' => array("language-provider-weight-$type")),
      );

      $table_form['title'][$id] = array('#markup' => check_plain($provider['name']));

      $table_form['enabled'][$id] = array(
        '#type' => 'checkbox',
        '#title' => t('@title language provider', array('@title' => $provider['name'])),
        '#title_display' => 'invisible',
        '#default_value' => $enabled,
      );
      if ($id === LANGUAGE_NEGOTIATION_DEFAULT) {
        $table_form['enabled'][$id]['#default_value'] = TRUE;
        $table_form['enabled'][$id]['#attributes'] = array('disabled' => 'disabled');
      }

      $table_form['description'][$id] = array('#markup' => filter_xss_admin($provider['description']));

      $config_op = array();
      if (isset($provider['config'])) {
        $config_op = array('#type' => 'link', '#title' => t('Configure'), '#href' => $provider['config']);
        // If there is at least one operation enabled show the operation column.
        $table_form['#show_operations'] = TRUE;
      }
      $table_form['operation'][$id] = $config_op;
    }
  }

  $form[$type] = $table_form;
}

/**
 * Returns HTML for a language configuration form.
 *
 * @param $variables
 *   An associative array containing:
 *   - form: A render element representing the form.
 *
 * @ingroup themeable
 */
function theme_locale_languages_configure_form($variables) {
  $form = $variables['form'];
  $output = '';

  foreach ($form['#language_types'] as $type) {
    $rows = array();
    $info = $form['#language_types_info'][$type];
    $title = '<label>' . $form[$type]['#title'] . '</label>';
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

    $variables = array(
      'header' => $header,
      'rows' => $rows,
      'attributes' => array('id' => "language-negotiation-providers-$type"),
    );
    $table  = theme('table', $variables);
    $table .= drupal_render_children($form[$type]);

    drupal_add_tabledrag("language-negotiation-providers-$type", 'order', 'sibling', "language-provider-weight-$type");

    $output .= '<div class="form-item">' . $title . $description . $table . '</div>';
  }

  $output .= drupal_render_children($form);
  return $output;
}

/**
 * Submit handler for language negotiation settings.
 */
function locale_languages_configure_form_submit($form, &$form_state) {
  $configurable_types = $form['#language_types'];

  foreach ($configurable_types as $type) {
    $negotiation = array();
    $enabled_providers = $form_state['values'][$type]['enabled'];
    $enabled_providers[LANGUAGE_NEGOTIATION_DEFAULT] = TRUE;
    $providers_weight = $form_state['values'][$type]['weight'];

    foreach ($providers_weight as $id => $weight) {
      if ($enabled_providers[$id]) {
        $provider = $form[$type]['#language_providers'][$id];
        $provider['weight'] = $weight;
        $negotiation[$id] = $provider;
      }
    }

    language_negotiation_set($type, $negotiation);
    variable_set("locale_language_providers_weight_$type", $providers_weight);
  }

  // Update non-configurable language types and the related language negotiation
  // configuration.
  language_types_set();

  $form_state['redirect'] = 'admin/config/regional/language/configure';
  drupal_set_message(t('Language negotiation configuration saved.'));
}

/**
 * The URL language provider configuration form.
 */
function locale_language_providers_url_form($form, &$form_state) {
  $form['locale_language_negotiation_url_part'] = array(
    '#title' => t('Part of the URL that determines language'),
    '#type' => 'radios',
    '#options' => array(
      LOCALE_LANGUAGE_NEGOTIATION_URL_PREFIX => t('Path prefix'),
      LOCALE_LANGUAGE_NEGOTIATION_URL_DOMAIN => t('Domain'),
    ),
    '#default_value' => variable_get('locale_language_negotiation_url_part', LOCALE_LANGUAGE_NEGOTIATION_URL_PREFIX),
    '#description' => t('<em>Path prefix</em>: URLs like http://example.com/de/contact set language to German (de). <em>Domain</em>: URLs like http://de.example.com/contact set the language to German. <strong>Warning: Changing this setting may break incoming URLs. Use with caution on a production site.</strong>'),
  );

  $form_state['redirect'] = 'admin/config/regional/language/configure';

  return system_settings_form($form);
}

/**
 * The URL language provider configuration form.
 */
function locale_language_providers_session_form($form, &$form_state) {
  $form['locale_language_negotiation_session_param'] = array(
    '#title' => t('Request/session parameter'),
    '#type' => 'textfield',
    '#default_value' => variable_get('locale_language_negotiation_session_param', 'language'),
    '#description' => t('Name of the request/session parameter used to determine the desired language.'),
  );

  $form_state['redirect'] = 'admin/config/regional/language/configure';

  return system_settings_form($form);
}

/**
 * @} End of "locale-language-administration"
 */

/**
 * @defgroup locale-translate-administration-screens Translation administration screens
 * @{
 * Screens for translation administration.
 *
 * These functions provide various screens as administration interface
 * to import, export and view translations.
 */

/**
 * Overview screen for translations.
 */
function locale_translate_overview_screen() {
  drupal_static_reset('language_list');
  $languages = language_list('language');
  $groups = module_invoke_all('locale', 'groups');

  // Build headers with all groups in order.
  $headers = array_merge(array(t('Language')), array_values($groups));

  // Collect summaries of all source strings in all groups.
  $sums = db_query("SELECT COUNT(*) AS strings, textgroup FROM {locales_source} GROUP BY textgroup");
  $groupsums = array();
  foreach ($sums as $group) {
    $groupsums[$group->textgroup] = $group->strings;
  }

  // Set up overview table with default values, ensuring common order for values.
  $rows = array();
  foreach ($languages as $langcode => $language) {
    $rows[$langcode] = array('name' => ($langcode == 'en' ? t('English (built-in)') : t($language->name)));
    foreach ($groups as $group => $name) {
      $rows[$langcode][$group] = ($langcode == 'en' ? t('n/a') : '0/' . (isset($groupsums[$group]) ? $groupsums[$group] : 0) . ' (0%)');
    }
  }

  // Languages with at least one record in the locale table.
  $translations = db_query("SELECT COUNT(*) AS translation, t.language, s.textgroup FROM {locales_source} s INNER JOIN {locales_target} t ON s.lid = t.lid GROUP BY textgroup, language");
  foreach ($translations as $data) {
    $ratio = (!empty($groupsums[$data->textgroup]) && $data->translation > 0) ? round(($data->translation/$groupsums[$data->textgroup]) * 100.0, 2) : 0;
    $rows[$data->language][$data->textgroup] = $data->translation . '/' . $groupsums[$data->textgroup] . " ($ratio%)";
  }

  return theme('table', array('header' => $headers, 'rows' => $rows));
}

/**
 * String search screen.
 */
function locale_translate_seek_screen() {
  // Add CSS.
  drupal_add_css(drupal_get_path('module', 'locale') . '/locale.css');

  $elements = drupal_get_form('locale_translation_filter_form');
  $output = drupal_render($elements);
  $output .= _locale_translate_seek();
  return $output;
}

/**
 * List locale translation filters that can be applied.
 */
function locale_translation_filters() {
  $filters = array();

  // Get all languages, except English
  drupal_static_reset('language_list');
  $languages = locale_language_list('name');
  unset($languages['en']);

  $filters['string'] = array(
    'title' => t('String contains'),
    'description' => t('Leave blank to show all strings. The search is case sensitive.'),
  );

  $filters['language'] = array(
    'title' => t('Language'),
    'options' => array_merge(array('all' => t('All languages'), 'en' => t('English (provided by Drupal)')), $languages),
  );

  $filters['translation'] = array(
    'title' => t('Search in'),
    'options' => array('all' => t('Both translated and untranslated strings'), 'translated' => t('Only translated strings'), 'untranslated' => t('Only untranslated strings')),
  );

  $groups = module_invoke_all('locale', 'groups');
  $filters['group'] = array(
    'title' => t('Limit search to'),
    'options' => array_merge(array('all' => t('All text groups')), $groups),
  );

  return $filters;
}

/**
 * Return form for locale translation filters.
 *
 * @ingroup forms
 */
function locale_translation_filter_form() {
  $filters = locale_translation_filters();

  $form['filters'] = array(
    '#type' => 'fieldset',
    '#title' => t('Filter translatable strings'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
  );
  foreach ($filters as $key => $filter) {
    // Special case for 'string' filter.
    if ($key == 'string') {
      $form['filters']['status']['string'] = array(
        '#type' => 'textfield',
        '#title' => $filter['title'],
        '#description' => $filter['description'],
      );
    }
    else {
      $form['filters']['status'][$key] = array(
        '#title' => $filter['title'],
        '#type' => 'select',
        '#empty_value' => 'all',
        '#empty_option' => $filter['options']['all'],
        '#size' => 0,
        '#options' => $filter['options'],
      );
    }
    if (!empty($_SESSION['locale_translation_filter'][$key])) {
      $form['filters']['status'][$key]['#default_value'] = $_SESSION['locale_translation_filter'][$key];
    }
  }

  $form['filters']['actions'] = array(
    '#type' => 'actions',
    '#attributes' => array('class' => array('container-inline')),
  );
  $form['filters']['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Filter'),
  );
  if (!empty($_SESSION['locale_translation_filter'])) {
    $form['filters']['actions']['reset'] = array(
      '#type' => 'submit',
      '#value' => t('Reset')
    );
  }

  return $form;
}

/**
 * Validate result from locale translation filter form.
 */
function locale_translation_filter_form_validate($form, &$form_state) {
  if ($form_state['values']['op'] == t('Filter') && empty($form_state['values']['language']) && empty($form_state['values']['group'])) {
    form_set_error('type', t('You must select something to filter by.'));
  }
}

/**
 * Process result from locale translation filter form.
 */
function locale_translation_filter_form_submit($form, &$form_state) {
  $op = $form_state['values']['op'];
  $filters = locale_translation_filters();
  switch ($op) {
    case t('Filter'):
      foreach ($filters as $name => $filter) {
        if (isset($form_state['values'][$name])) {
          $_SESSION['locale_translation_filter'][$name] = $form_state['values'][$name];
        }
      }
      break;
    case t('Reset'):
      $_SESSION['locale_translation_filter'] = array();
      break;
  }

  $form_state['redirect'] = 'admin/config/regional/translate/translate';
}

/**
 * User interface for the translation import screen.
 */
function locale_translate_import_form($form) {
  // Get all languages, except English
  drupal_static_reset('language_list');
  $names = locale_language_list('name');
  unset($names['en']);

  if (!count($names)) {
    $languages = _locale_prepare_predefined_list();
    $default = key($languages);
  }
  else {
    $languages = array(
      t('Already added languages') => $names,
      t('Languages not yet added') => _locale_prepare_predefined_list()
    );
    $default = key($names);
  }

  $form['import'] = array('#type' => 'fieldset',
    '#title' => t('Import translation'),
  );
  $form['import']['file'] = array('#type' => 'file',
    '#title' => t('Language file'),
    '#size' => 50,
    '#description' => t('A Gettext Portable Object (<em>.po</em>) file.'),
  );
  $form['import']['langcode'] = array('#type' => 'select',
    '#title' => t('Import into'),
    '#options' => $languages,
    '#default_value' => $default,
    '#description' => t('Choose the language you want to add strings into. If you choose a language which is not yet set up, it will be added.'),
  );
  $form['import']['group'] = array('#type' => 'radios',
    '#title' => t('Text group'),
    '#default_value' => 'default',
    '#options' => module_invoke_all('locale', 'groups'),
    '#description' => t('Imported translations will be added to this text group.'),
  );
  $form['import']['mode'] = array('#type' => 'radios',
    '#title' => t('Mode'),
    '#default_value' => LOCALE_IMPORT_KEEP,
    '#options' => array(
      LOCALE_IMPORT_OVERWRITE => t('Strings in the uploaded file replace existing ones, new ones are added. The plural format is updated.'),
      LOCALE_IMPORT_KEEP => t('Existing strings and the plural format are kept, only new strings are added.')
    ),
  );
  $form['import']['submit'] = array('#type' => 'submit', '#value' => t('Import'));

  return $form;
}

/**
 * Process the locale import form submission.
 */
function locale_translate_import_form_submit($form, &$form_state) {
  $validators = array('file_validate_extensions' => array('po'));
  // Ensure we have the file uploaded
  if ($file = file_save_upload('file', $validators)) {

    // Add language, if not yet supported
    drupal_static_reset('language_list');
    $languages = language_list('language');
    $langcode = $form_state['values']['langcode'];
    if (!isset($languages[$langcode])) {
      include_once DRUPAL_ROOT . '/includes/iso.inc';
      $predefined = _locale_get_predefined_list();
      locale_add_language($langcode);
      drupal_set_message(t('The language %language has been created.', array('%language' => t($predefined[$langcode][0]))));
    }

    // Now import strings into the language
    if ($return = _locale_import_po($file, $langcode, $form_state['values']['mode'], $form_state['values']['group']) == FALSE) {
      $variables = array('%filename' => $file->filename);
      drupal_set_message(t('The translation import of %filename failed.', $variables), 'error');
      watchdog('locale', 'The translation import of %filename failed.', $variables, WATCHDOG_ERROR);
    }
  }
  else {
    drupal_set_message(t('File to import not found.'), 'error');
    $form_state['redirect'] = 'admin/config/regional/translate/import';
    return;
  }

  $form_state['redirect'] = 'admin/config/regional/translate';
  return;
}

/**
 * User interface for the translation export screen.
 */
function locale_translate_export_screen() {
  // Get all languages, except English
  drupal_static_reset('language_list');
  $names = locale_language_list('name');
  unset($names['en']);
  $output = '';
  // Offer translation export if any language is set up.
  if (count($names)) {
    $elements = drupal_get_form('locale_translate_export_po_form', $names);
    $output = drupal_render($elements);
  }
  $elements = drupal_get_form('locale_translate_export_pot_form');
  $output .= drupal_render($elements);
  return $output;
}

/**
 * Form to export PO files for the languages provided.
 *
 * @param $names
 *   An associate array with localized language names
 */
function locale_translate_export_po_form($form, &$form_state, $names) {
  $form['export_title'] = array('#type' => 'item',
    '#title' => t('Export translation'),
  );
  $form['langcode'] = array('#type' => 'select',
    '#title' => t('Language name'),
    '#options' => $names,
    '#description' => t('Select the language to export in Gettext Portable Object (<em>.po</em>) format.'),
  );
  $form['group'] = array('#type' => 'radios',
    '#title' => t('Text group'),
    '#default_value' => 'default',
    '#options' => module_invoke_all('locale', 'groups'),
  );
  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array('#type' => 'submit', '#value' => t('Export'));
  return $form;
}

/**
 * Translation template export form.
 */
function locale_translate_export_pot_form() {
  // Complete template export of the strings
  $form['export_title'] = array('#type' => 'item',
    '#title' => t('Export template'),
    '#description' => t('Generate a Gettext Portable Object Template (<em>.pot</em>) file with all strings from the Drupal locale database.'),
  );
  $form['group'] = array('#type' => 'radios',
    '#title' => t('Text group'),
    '#default_value' => 'default',
    '#options' => module_invoke_all('locale', 'groups'),
  );
  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array('#type' => 'submit', '#value' => t('Export'));
  // Reuse PO export submission callback.
  $form['#submit'][] = 'locale_translate_export_po_form_submit';
  return $form;
}

/**
 * Process a translation (or template) export form submission.
 */
function locale_translate_export_po_form_submit($form, &$form_state) {
  // If template is required, language code is not given.
  $language = NULL;
  if (isset($form_state['values']['langcode'])) {
    $languages = language_list();
    $language = $languages[$form_state['values']['langcode']];
  }
  _locale_export_po($language, _locale_export_po_generate($language, _locale_export_get_strings($language, $form_state['values']['group'])));
}
/**
 * @} End of "locale-translate-administration-screens"
 */

/**
 * @defgroup locale-translate-edit-delete Translation editing/deletion interface
 * @{
 * Edit and delete translation strings.
 *
 * These functions provide the user interface to edit and delete
 * translation strings.
 */

/**
 * User interface for string editing.
 */
function locale_translate_edit_form($form, &$form_state, $lid) {
  // Fetch source string, if possible.
  $source = db_query('SELECT source, context, textgroup, location FROM {locales_source} WHERE lid = :lid', array(':lid' => $lid))->fetchObject();
  if (!$source) {
    drupal_set_message(t('String not found.'), 'error');
    drupal_goto('admin/config/regional/translate/translate');
  }

  // Add original text to the top and some values for form altering.
  $form['original'] = array(
    '#type'  => 'item',
    '#title' => t('Original text'),
    '#markup' => check_plain(wordwrap($source->source, 0)),
  );
  if (!empty($source->context)) {
    $form['context'] = array(
      '#type' => 'item',
      '#title' => t('Context'),
      '#markup' => check_plain($source->context),
    );
  }
  $form['lid'] = array(
    '#type'  => 'value',
    '#value' => $lid
  );
  $form['textgroup'] = array(
    '#type'  => 'value',
    '#value' => $source->textgroup,
  );
  $form['location'] = array(
    '#type'  => 'value',
    '#value' => $source->location
  );

  // Include default form controls with empty values for all languages.
  // This ensures that the languages are always in the same order in forms.
  $languages = language_list();
  $default = language_default();
  // We don't need the default language value, that value is in $source.
  $omit = $source->textgroup == 'default' ? 'en' : $default->language;
  unset($languages[($omit)]);
  $form['translations'] = array('#tree' => TRUE);
  // Approximate the number of rows to use in the default textarea.
  $rows = min(ceil(str_word_count($source->source) / 12), 10);
  foreach ($languages as $langcode => $language) {
    $form['translations'][$langcode] = array(
      '#type' => 'textarea',
      '#title' => t($language->name),
      '#rows' => $rows,
      '#default_value' => '',
    );
  }

  // Fetch translations and fill in default values in the form.
  $result = db_query("SELECT DISTINCT translation, language FROM {locales_target} WHERE lid = :lid AND language <> :omit", array(':lid' => $lid, ':omit' => $omit));
  foreach ($result as $translation) {
    $form['translations'][$translation->language]['#default_value'] = $translation->translation;
  }

  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array('#type' => 'submit', '#value' => t('Save translations'));
  return $form;
}

/**
 * Validate string editing form submissions.
 */
function locale_translate_edit_form_validate($form, &$form_state) {
  // Locale string check is needed for default textgroup only.
  $safe_check_needed = $form_state['values']['textgroup'] == 'default';
  foreach ($form_state['values']['translations'] as $key => $value) {
    if ($safe_check_needed && !locale_string_is_safe($value)) {
      form_set_error('translations', t('The submitted string contains disallowed HTML: %string', array('%string' => $value)));
      watchdog('locale', 'Attempted submission of a translation string with disallowed HTML: %string', array('%string' => $value), WATCHDOG_WARNING);
    }
  }
}

/**
 * Process string editing form submissions.
 *
 * Saves all translations of one string submitted from a form.
 */
function locale_translate_edit_form_submit($form, &$form_state) {
  $lid = $form_state['values']['lid'];
  foreach ($form_state['values']['translations'] as $key => $value) {
    $translation = db_query("SELECT translation FROM {locales_target} WHERE lid = :lid AND language = :language", array(':lid' => $lid, ':language' => $key))->fetchField();
    if (!empty($value)) {
      // Only update or insert if we have a value to use.
      if (!empty($translation)) {
        db_update('locales_target')
          ->fields(array(
            'translation' => $value,
          ))
          ->condition('lid', $lid)
          ->condition('language', $key)
          ->execute();
      }
      else {
        db_insert('locales_target')
          ->fields(array(
            'lid' => $lid,
            'translation' => $value,
            'language' => $key,
          ))
          ->execute();
      }
    }
    elseif (!empty($translation)) {
      // Empty translation entered: remove existing entry from database.
      db_delete('locales_target')
        ->condition('lid', $lid)
        ->condition('language', $key)
        ->execute();
    }

    // Force JavaScript translation file recreation for this language.
    _locale_invalidate_js($key);
  }

  drupal_set_message(t('The string has been saved.'));

  // Clear locale cache.
  _locale_invalidate_js();
  cache_clear_all('locale:', 'cache', TRUE);

  $form_state['redirect'] = 'admin/config/regional/translate/translate';
  return;
}

/**
 * String deletion confirmation page.
 */
function locale_translate_delete_page($lid) {
  if ($source = db_query('SELECT lid, source FROM {locales_source} WHERE lid = :lid', array(':lid' => $lid))->fetchObject()) {
    return drupal_get_form('locale_translate_delete_form', $source);
  }
  return MENU_NOT_FOUND;
}

/**
 * User interface for the string deletion confirmation screen.
 */
function locale_translate_delete_form($form, &$form_state, $source) {
  $form['lid'] = array('#type' => 'value', '#value' => $source->lid);
  return confirm_form($form, t('Are you sure you want to delete the string "%source"?', array('%source' => $source->source)), 'admin/config/regional/translate/translate', t('Deleting the string will remove all translations of this string in all languages. This action cannot be undone.'), t('Delete'), t('Cancel'));
}

/**
 * Process string deletion submissions.
 */
function locale_translate_delete_form_submit($form, &$form_state) {
  db_delete('locales_source')
    ->condition('lid', $form_state['values']['lid'])
    ->execute();
  db_delete('locales_target')
    ->condition('lid', $form_state['values']['lid'])
    ->execute();
  // Force JavaScript translation file recreation for all languages.
  _locale_invalidate_js();
  cache_clear_all('locale:', 'cache', TRUE);
  drupal_set_message(t('The string has been removed.'));
  $form_state['redirect'] = 'admin/config/regional/translate/translate';
}
/**
 * @} End of "locale-translate-edit-delete"
 */

/**
 * Returns HTML for a locale date format form.
 *
 * @param $variables
 *   An associative array containing:
 *   - form: A render element representing the form.
 *
 * @ingroup themeable
 */
function theme_locale_date_format_form($variables) {
  $form = $variables['form'];
  $header = array(
    t('Date type'),
    t('Format'),
  );

  foreach (element_children($form['date_formats']) as $key) {
    $row = array();
    $row[] = $form['date_formats'][$key]['#title'];
    unset($form['date_formats'][$key]['#title']);
    $row[] = array('data' => drupal_render($form['date_formats'][$key]));
    $rows[] = $row;
  }

  $output = drupal_render($form['language']);
  $output .= theme('table', array('header' => $header, 'rows' => $rows));
  $output .= drupal_render_children($form);

  return $output;
}

/**
 * Display edit date format links for each language.
 */
function locale_date_format_language_overview_page() {
  $header = array(
    t('Language'),
    array('data' => t('Operations'), 'colspan' => '2'),
  );

  // Get list of languages.
  $languages = locale_language_list('native');

  foreach ($languages as $langcode => $info) {
    $row = array();
    $row[] = $languages[$langcode];
    $row[] = l(t('edit'), 'admin/config/regional/date-time/locale/' . $langcode . '/edit');
    $row[] = l(t('reset'), 'admin/config/regional/date-time/locale/' . $langcode . '/reset');
    $rows[] = $row;
  }

  return theme('table', array('header' => $header, 'rows' => $rows));
}

/**
 * Provide date localization configuration options to users.
 */
function locale_date_format_form($form, &$form_state, $langcode) {
  $languages = locale_language_list('native');
  $language_name = $languages[$langcode];

  // Display the current language name.
  $form['language'] = array(
    '#type' => 'item',
    '#title' => t('Language'),
    '#markup' => check_plain($language_name),
    '#weight' => -10,
  );
  $form['langcode'] = array(
    '#type' => 'value',
    '#value' => $langcode,
  );

  // Get list of date format types.
  $types = system_get_date_types();

  // Get list of available formats.
  $formats = system_get_date_formats();
  $choices = array();
  foreach ($formats as $type => $list) {
    foreach ($list as $f => $format) {
      $choices[$f] = format_date(REQUEST_TIME, 'custom', $f);
    }
  }
  reset($formats);

  // Get configured formats for each language.
  $locale_formats = system_date_format_locale($langcode);
  // Display a form field for each format type.
  foreach ($types as $type => $type_info) {
    if (!empty($locale_formats) && in_array($type, array_keys($locale_formats))) {
      $default = $locale_formats[$type];
    }
    else {
      $default = variable_get('date_format_' . $type, key($formats));
    }

    // Show date format select list.
    $form['date_formats']['date_format_' . $type] = array(
      '#type' => 'select',
      '#title' => check_plain($type_info['title']),
      '#attributes' => array('class' => array('date-format')),
      '#default_value' => (isset($choices[$default]) ? $default : 'custom'),
      '#options' => $choices,
    );
  }

  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save configuration'),
  );

  return $form;
}

/**
 * Submit handler for configuring localized date formats on the locale_date_format_form.
 */
function locale_date_format_form_submit($form, &$form_state) {
  include_once DRUPAL_ROOT . '/includes/locale.inc';
  $langcode = $form_state['values']['langcode'];

  // Get list of date format types.
  $types = system_get_date_types();
  foreach ($types as $type => $type_info) {
    $format = $form_state['values']['date_format_' . $type];
    if ($format == 'custom') {
      $format = $form_state['values']['date_format_' . $type . '_custom'];
    }
    locale_date_format_save($langcode, $type, $format);
  }
  drupal_set_message(t('Configuration saved.'));
  $form_state['redirect'] = 'admin/config/regional/date-time/locale';
}

/**
 * Reset locale specific date formats to the global defaults.
 *
 * @param $langcode
 *   Language code, e.g. 'en'.
 */
function locale_date_format_reset_form($form, &$form_state, $langcode) {
  $form['langcode'] = array('#type' => 'value', '#value' => $langcode);
  $languages = language_list();
  return confirm_form($form,
    t('Are you sure you want to reset the date formats for %language to the global defaults?', array('%language' => $languages[$langcode]->name)),
    'admin/config/regional/date-time/locale',
    t('Resetting will remove all localized date formats for this language. This action cannot be undone.'),
    t('Reset'), t('Cancel'));
}

/**
 * Reset date formats for a specific language to global defaults.
 */
function locale_date_format_reset_form_submit($form, &$form_state) {
  db_delete('date_format_locale')
    ->condition('language', $form_state['values']['langcode'])
    ->execute();
  $form_state['redirect'] = 'admin/config/regional/date-time/locale';
}
