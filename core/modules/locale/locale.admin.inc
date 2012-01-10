<?php

/**
 * @file
 * Administration functions for locale.module.
 */

/**
 * Setting for language negotiation options
 */
function locale_languages_configure_form() {
  include_once DRUPAL_ROOT . '/core/includes/language.inc';

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
      $provider_name = check_plain($provider['name']);

      $table_form['weight'][$id] = array(
        '#type' => 'weight',
        '#title' => t('Weight for !title language detection method', array('!title' => drupal_strtolower($provider_name))),
        '#title_display' => 'invisible',
        '#default_value' => $weight,
        '#attributes' => array('class' => array("language-provider-weight-$type")),
      );

      $table_form['title'][$id] = array('#markup' => $provider_name);

      $table_form['enabled'][$id] = array(
        '#type' => 'checkbox',
        '#title' => t('Enable !title language detection method', array('!title' => drupal_strtolower($provider_name))),
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
  );

  $form['prefix'] = array(
    '#type' => 'fieldset',
    '#tree' => TRUE,
    '#title' => t('Path prefix configuration'),
    '#description' => t('Language codes or other custom text to use as a path prefix for URL language detection. For the default language, this value may be left blank. <strong>Modifying this value may break existing URLs. Use with caution in a production environment.</strong> Example: Specifying "deutsch" as the path prefix code for German results in URLs like "example.com/deutsch/contact".'),
    '#states' => array(
      'visible' => array(
        ':input[name="locale_language_negotiation_url_part"]' => array(
          'value' => (string) LOCALE_LANGUAGE_NEGOTIATION_URL_PREFIX,
        ),
      ),
    ),
  );
  $form['domain'] = array(
    '#type' => 'fieldset',
    '#tree' => TRUE,
    '#title' => t('Domain configuration'),
    '#description' => t('The domain names to use for these languages. Leave blank for the default language. Use with caution in a production environment.<strong>Modifying this value may break existing URLs. Use with caution in a production environment.</strong> Example: Specifying "de.example.com" as language domain for German will result in an URL like "http://de.example.com/contact".'),
    '#states' => array(
      'visible' => array(
        ':input[name="locale_language_negotiation_url_part"]' => array(
          'value' => (string) LOCALE_LANGUAGE_NEGOTIATION_URL_DOMAIN,
        ),
      ),
    ),
  );

  $languages = language_list('enabled');
  $prefixes = locale_language_negotiation_url_prefixes();
  $domains = locale_language_negotiation_url_domains();
  foreach ($languages[1] as $langcode => $language) {
    $form['prefix'][$langcode] = array(
      '#type' => 'textfield',
      '#title' => t('%language (%langcode) path prefix', array('%language' => $language->name, '%langcode' => $language->langcode)),
      '#maxlength' => 64,
      '#default_value' => isset($prefixes[$langcode]) ? $prefixes[$langcode] : '',
      '#field_prefix' => url('', array('absolute' => TRUE)) . (variable_get('clean_url', 0) ? '' : '?q=')
    );
    $form['domain'][$langcode] = array(
      '#type' => 'textfield',
      '#title' => t('%language (%langcode) domain', array('%language' => $language->name, '%langcode' => $language->langcode)),
      '#maxlength' => 128,
      '#default_value' => isset($domains[$langcode]) ? $domains[$langcode] : '',
    );
  }

  $form_state['redirect'] = 'admin/config/regional/language/configure';

  $form['actions']['#type'] = 'actions';
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save configuration'),
  );
  return $form;
}

/**
 * Validation handler for url provider configuration.
 *
 * Validate that the prefixes and domains are unique, and make sure that
 * the prefix and domain are only blank for the default.
 */
function locale_language_providers_url_form_validate($form, &$form_state) {
  $languages = locale_language_list();
  $default = language_default();

  // Count repeated values for uniqueness check.
  $count = array_count_values($form_state['values']['prefix']);
  foreach ($languages as $langcode => $name) {
    $value = $form_state['values']['prefix'][$langcode];

    if ($value === '') {
      if ($default->langcode != $langcode && $form_state['values']['locale_language_negotiation_url_part'] == LOCALE_LANGUAGE_NEGOTIATION_URL_PREFIX) {
        // Validation error if the prefix is blank for a non-default language, and value is for selected negotiation type.
        form_error($form['prefix'][$langcode], t('The prefix may only be left blank for the default language.'));
      }
    }
    else if (isset($count[$value]) && $count[$value] > 1) {
      // Validation error if there are two languages with the same domain/prefix.
      form_error($form['prefix'][$langcode], t('The prefix for %language, %value, is not unique.', array( '%language' => $name, '%value' => $value )));
    }
  }

  // Count repeated values for uniqueness check.
  $count = array_count_values($form_state['values']['domain']);
  foreach ($languages as $langcode => $name) {
    $value = $form_state['values']['domain'][$langcode];

    if ($value === '') {
      if ($default->langcode != $langcode && $form_state['values']['locale_language_negotiation_url_part'] == LOCALE_LANGUAGE_NEGOTIATION_URL_DOMAIN) {
        // Validation error if the domain is blank for a non-default language, and value is for selected negotiation type.
        form_error($form['domain'][$langcode], t('The domain may only be left blank for the default language.'));
      }
    }
    else if (isset($count[$value]) && $count[$value] > 1) {
      // Validation error if there are two languages with the same domain/domain.
      form_error($form['domain'][$langcode], t('The domain for %language, %value, is not unique.', array( '%language' => $name, '%value' => $value )));
    }
  }
}

/**
 * Save URL negotiation provider settings.
 */
function locale_language_providers_url_form_submit($form, &$form_state) {

  // Save selected format (prefix or domain).
  variable_set('locale_language_negotiation_url_part', $form_state['values']['locale_language_negotiation_url_part']);

  // Save new domain and prefix values.
  locale_language_negotiation_url_prefixes_save($form_state['values']['prefix']);
  locale_language_negotiation_url_domains_save($form_state['values']['domain']);

  drupal_set_message(t('Configuration saved.'));
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
  $languages = locale_language_list();

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
  $languages = locale_language_list();
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
  include_once DRUPAL_ROOT . '/core/includes/locale.inc';
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
