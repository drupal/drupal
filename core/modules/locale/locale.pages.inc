<?php

/**
 * @file
 * Interface translation summary, editing and deletion user interfaces.
 */

use Drupal\Core\Language\Language;
use Drupal\locale\SourceString;
use Drupal\locale\TranslationString;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Page callback: Shows the string search screen.
 *
 * @see locale_menu()
 */
function locale_translate_page() {
  return array(
    'filter' => drupal_get_form('locale_translate_filter_form'),
    'form' => drupal_get_form('locale_translate_edit_form'),
  );
}

/**
 * Builds a string search query and returns an array of string objects.
 *
 * @return array
 *   Array of Drupal\locale\TranslationString objects.
 */
function locale_translate_filter_load_strings() {
  $filter_values = locale_translate_filter_values();

  // Language is sanitized to be one of the possible options in
  // locale_translate_filter_values().
  $conditions = array('language' => $filter_values['langcode']);
  $options = array('pager limit' => 30, 'translated' => TRUE, 'untranslated' => TRUE);

  // Add translation status conditions and options.
  switch ($filter_values['translation']) {
    case 'translated':
      $conditions['translated'] = TRUE;
      if ($filter_values['customized'] != 'all') {
        $conditions['customized'] = $filter_values['customized'];
      }
      break;

    case 'untranslated':
      $conditions['translated'] = FALSE;
      break;

  }

  if (!empty($filter_values['string'])) {
    $options['filters']['source'] = $filter_values['string'];
    if ($options['translated']) {
      $options['filters']['translation'] = $filter_values['string'];
    }
  }

  return locale_storage()->getTranslations($conditions, $options);
}

/**
 * Build array out of search criteria specified in request variables.
 */
function locale_translate_filter_values() {
  $filter_values = &drupal_static(__FUNCTION__);
  if (!isset($filter_values)) {
    $filter_values = array();
    $filters = locale_translate_filters();
    foreach ($filters as $key => $filter) {
      $filter_values[$key] = $filter['default'];
      // Let the filter defaults be overwritten by parameters in the URL.
      if (isset($_GET[$key])) {
        // Only allow this value if it was among the options, or
        // if there were no fixed options to filter for.
        if (!isset($filter['options']) || isset($filter['options'][$_GET[$key]])) {
          $filter_values[$key] = $_GET[$key];
        }
      }
      elseif (isset($_SESSION['locale_translate_filter'][$key])) {
        // Only allow this value if it was among the options, or
        // if there were no fixed options to filter for.
        if (!isset($filter['options']) || isset($filter['options'][$_SESSION['locale_translate_filter'][$key]])) {
          $filter_values[$key] = $_SESSION['locale_translate_filter'][$key];
        }
      }
    }
  }
  return $filter_values;
}

/**
 * List locale translation filters that can be applied.
 */
function locale_translate_filters() {
  $filters = array();

  // Get all languages, except English.
  drupal_static_reset('language_list');
  $languages = language_list();
  $language_options = array();
  foreach ($languages as $langcode => $language) {
    if ($langcode != 'en' || locale_translate_english()) {
      $language_options[$langcode] = $language->name;
    }
  }

  // Pick the current interface language code for the filter.
  $default_langcode = language(Language::TYPE_INTERFACE)->langcode;
  if (!isset($language_options[$default_langcode])) {
    $available_langcodes = array_keys($language_options);
    $default_langcode = array_shift($available_langcodes);
  }

  $filters['string'] = array(
    'title' => t('String contains'),
    'description' => t('Leave blank to show all strings. The search is case sensitive.'),
    'default' => '',
  );

  $filters['langcode'] = array(
    'title' => t('Translation language'),
    'options' => $language_options,
    'default' => $default_langcode,
  );

  $filters['translation'] = array(
    'title' => t('Search in'),
    'options' => array(
      'all' => t('Both translated and untranslated strings'),
      'translated' => t('Only translated strings'),
      'untranslated' => t('Only untranslated strings'),
    ),
    'default' => 'all',
  );

  $filters['customized'] = array(
    'title' => t('Translation type'),
    'options' => array(
      'all' => t('All'),
      LOCALE_NOT_CUSTOMIZED => t('Non-customized translation'),
      LOCALE_CUSTOMIZED => t('Customized translation'),
    ),
    'states' => array(
      'visible' => array(
        ':input[name=translation]' => array('value' => 'translated'),
      ),
    ),
    'default' => 'all',
  );

  return $filters;
}

/**
 * Return form for locale translation filters.
 *
 * @ingroup forms
 */
function locale_translate_filter_form($form, &$form_state) {
  $filters = locale_translate_filters();
  $filter_values = locale_translate_filter_values();

  $form['#attached']['css'] = array(
    drupal_get_path('module', 'locale') . '/locale.admin.css',
  );

  $form['filters'] = array(
    '#type' => 'details',
    '#title' => t('Filter translatable strings'),
    '#collapsed' => FALSE,
  );
  foreach ($filters as $key => $filter) {
    // Special case for 'string' filter.
    if ($key == 'string') {
      $form['filters']['status']['string'] = array(
        '#type' => 'search',
        '#title' => $filter['title'],
        '#description' => $filter['description'],
        '#default_value' => $filter_values[$key],
      );
    }
    else {
      $empty_option = isset($filter['options'][$filter['default']]) ? $filter['options'][$filter['default']] : '- None -';
      $form['filters']['status'][$key] = array(
        '#title' => $filter['title'],
        '#type' => 'select',
        '#empty_value' => $filter['default'],
        '#empty_option' => $empty_option,
        '#size' => 0,
        '#options' => $filter['options'],
        '#default_value' => $filter_values[$key],
      );
      if (isset($filter['states'])) {
        $form['filters']['status'][$key]['#states'] = $filter['states'];
      }
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
  if (!empty($_SESSION['locale_translate_filter'])) {
    $form['filters']['actions']['reset'] = array(
      '#type' => 'submit',
      '#value' => t('Reset'),
    );
  }

  return $form;
}

/**
 * Process result from locale translation filter form.
 */
function locale_translate_filter_form_submit($form, &$form_state) {
  $op = $form_state['values']['op'];
  $filters = locale_translate_filters();
  switch ($op) {
    case t('Filter'):
      foreach ($filters as $name => $filter) {
        if (isset($form_state['values'][$name])) {
          $_SESSION['locale_translate_filter'][$name] = $form_state['values'][$name];
        }
      }
      break;

    case t('Reset'):
      $_SESSION['locale_translate_filter'] = array();
      break;

  }

  $form_state['redirect'] = 'admin/config/regional/translate/translate';
}

/**
 * Form constructor for the string editing form.
 *
 * @see locale_menu()
 * @see locale_translate_edit_form_validate()
 * @see locale_translate_edit_form_submit()
 *
 * @ingroup forms
 */
function locale_translate_edit_form($form, &$form_state) {
  $filter_values = locale_translate_filter_values();
  $langcode = $filter_values['langcode'];

  drupal_static_reset('language_list');
  $languages = language_list();

  $langname = isset($langcode) ? $languages[$langcode]->name : "- None -";

  $path = drupal_get_path('module', 'locale');
  $form['#attached']['css'] = array(
    $path . '/locale.admin.css',
  );
  $form['#attached']['library'][] = array('locale', 'drupal.locale.admin');

  $form['langcode'] = array(
    '#type' => 'value',
    '#value' => $filter_values['langcode'],
  );

  $form['strings'] = array(
    '#type' => 'item',
    '#tree' => TRUE,
    '#language' => $langname,
    '#theme' => 'locale_translate_edit_form_strings',
  );

  if (isset($langcode)) {
    $strings = locale_translate_filter_load_strings();

    $plural_formulas = Drupal::state()->get('locale.translation.plurals') ?: array();

    foreach ($strings as $string) {
      // Cast into source string, will do for our purposes.
      $source = new SourceString($string);
      // Split source to work with plural values.
      $source_array = $source->getPlurals();
      $translation_array = $string->getPlurals();
      if (count($source_array) == 1) {
        // Add original string value and mark as non-plural.
        $form['strings'][$string->lid]['plural'] = array(
          '#type' => 'value',
          '#value' => 0,
        );
        $form['strings'][$string->lid]['original'] = array(
          '#type' => 'item',
          '#title' => t('Source string'),
          '#title_display' => 'invisible',
          '#markup' => check_plain($source_array[0]),
        );
      }
      else {
        // Add original string value and mark as plural.
        $form['strings'][$string->lid]['plural'] = array(
          '#type' => 'value',
          '#value' => 1,
        );
        $form['strings'][$string->lid]['original_singular'] = array(
          '#type' => 'item',
          '#title' => t('Singular form'),
          '#markup' => check_plain($source_array[0]),
        );
        $form['strings'][$string->lid]['original_plural'] = array(
          '#type' => 'item',
          '#title' => t('Plural form'),
          '#markup' => check_plain($source_array[1]),
        );
      }
      if (!empty($string->context)) {
        $form['strings'][$string->lid]['context'] = array(
          '#type' => 'value',
          '#value' => check_plain($string->context),
        );
      }
      // Approximate the number of rows to use in the default textarea.
      $rows = min(ceil(str_word_count($source_array[0]) / 12), 10);
      if (empty($form['strings'][$string->lid]['plural']['#value'])) {
        $form['strings'][$string->lid]['translations'][0] = array(
          '#type' => 'textarea',
          '#title' => t('Translated string'),
          '#title_display' => 'invisible',
          '#rows' => $rows,
          '#default_value' => $translation_array[0],
        );
      }
      else {
        // Dealing with plural strings.
        if (isset($plural_formulas[$langcode]['plurals']) && $plural_formulas[$langcode]['plurals'] > 2) {
          // Add a textarea for each plural variant.
          for ($i = 0; $i < $plural_formulas[$langcode]['plurals']; $i++) {
            $form['strings'][$string->lid]['translations'][$i] = array(
              '#type' => 'textarea',
              '#title' => ($i == 0 ? t('Singular form') : format_plural($i, 'First plural form', '@count. plural form')),
              '#rows' => $rows,
              '#default_value' => isset($translation_array[$i]) ? $translation_array[$i] : '',
            );
          }
        }
        else {
          // Fallback for unknown number of plurals.
          $form['strings'][$string->lid]['translations'][0] = array(
            '#type' => 'textarea',
            '#title' => t('Singular form'),
            '#rows' => $rows,
            '#default_value' => $translation_array[0],
          );
          $form['strings'][$string->lid]['translations'][1] = array(
            '#type' => 'textarea',
            '#title' => t('Plural form'),
            '#rows' => $rows,
            '#default_value' => isset($translation_array[1]) ? $translation_array[1] : '',
          );
        }
      }
    }
    if (count(element_children($form['strings']))) {
      $form['actions'] = array('#type' => 'actions');
      $form['actions']['submit'] = array('#type' => 'submit', '#value' => t('Save translations'));
    }
  }
  return $form;
}

/**
 * Form validation handler for locale_translate_edit_form().
 *
 * @see locale_translate_edit_form_submit()
 */
function locale_translate_edit_form_validate($form, &$form_state) {
  $langcode = $form_state['values']['langcode'];
  foreach ($form_state['values']['strings'] as $lid => $translations) {
    foreach ($translations['translations'] as $key => $value) {
      if (!locale_string_is_safe($value)) {
        form_set_error("strings][$lid][translations][$key", t('The submitted string contains disallowed HTML: %string', array('%string' => $value)));
        form_set_error("translations][$langcode][$key", t('The submitted string contains disallowed HTML: %string', array('%string' => $value)));
        watchdog('locale', 'Attempted submission of a translation string with disallowed HTML: %string', array('%string' => $value), WATCHDOG_WARNING);
      }
    }
  }
}

/**
 * Form submission handler for locale_translate_edit_form().
 *
 * @see locale_translate_edit_form_validate()
 */
function locale_translate_edit_form_submit($form, &$form_state) {
  $langcode = $form_state['values']['langcode'];
  $updated = array();

  // Preload all translations for strings in the form.
  $lids = array_keys($form_state['values']['strings']);
  $existing_translation_objects = array();
  foreach (locale_storage()->getTranslations(array('lid' => $lids, 'language' => $langcode, 'translated' => TRUE)) as $existing_translation_object) {
    $existing_translation_objects[$existing_translation_object->lid] = $existing_translation_object;
  }

  foreach ($form_state['values']['strings'] as $lid => $new_translation) {
    $existing_translation = isset($existing_translation_objects[$lid]);

    // Plural translations are saved in a delimited string. To be able to
    // compare the new strings with the existing strings a string in the same format is created.
    $new_translation_string_delimited = implode(LOCALE_PLURAL_DELIMITER, $new_translation['translations']);

    // Generate an imploded string without delimiter, to be able to run
    // empty() on it.
    $new_translation_string = implode('', $new_translation['translations']);

    $is_changed = FALSE;

    if ($existing_translation && $existing_translation_objects[$lid]->translation != $new_translation_string_delimited) {
      // If there is an existing translation in the DB and the new translation
      // is not the same as the existing one.
      $is_changed = TRUE;
    }
    elseif (!$existing_translation && !empty($new_translation_string)) {
      // Newly entered translation.
      $is_changed = TRUE;
    }

    if ($is_changed) {
      // Only update or insert if we have a value to use.
      $target = isset($existing_translation_objects[$lid]) ? $existing_translation_objects[$lid] : locale_storage()->createTranslation(array('lid' => $lid, 'language' => $langcode));
      $target->setPlurals($new_translation['translations'])
        ->setCustomized()
        ->save();
      $updated[] = $target->getId();
    }
    if (empty($new_translation_string) && isset($existing_translation_objects[$lid])) {
      // Empty new translation entered: remove existing entry from database.
      $existing_translation_objects[$lid]->delete();
      $updated[] = $lid;
    }
  }

  drupal_set_message(t('The strings have been saved.'));

  // Keep the user on the current pager page.
  if (isset($_GET['page'])) {
    $form_state['redirect'] = array('admin/config/regional/translate', array('query' => array('page' => $_GET['page'])));
  }

  if ($updated) {
    // Clear cache and refresh configuration and JavaScript translations.
    _locale_refresh_translations(array($langcode), $updated);
    _locale_refresh_configuration(array($langcode), $updated);
  }

}

/**
 * Page callback: Checks for translation updates and displays the translations status.
 *
 * Manually checks the translation status without the use of cron.
 *
 * @see locale_menu()
 */
function locale_translation_manual_status() {
  module_load_include('compare.inc', 'locale');

  // Check translation status of all translatable project in all languages.
  // First we clear the cached list of projects. Although not strictly
  // nescessary, this is helpfull in case the project list is out of sync.
  locale_translation_flush_projects();
  locale_translation_check_projects();

  // Execute a batch if required. A batch is only used when remote files
  // are checked.
  if (batch_get()) {
    batch_process('admin/reports/translations');
  }
  drupal_goto('admin/reports/translations');
}

/**
 * Page callback: Display the current translation status.
 *
 * @see locale_menu()
 */
function locale_translation_status_form($form, &$form_state) {
  module_load_include('translation.inc', 'locale');
  module_load_include('compare.inc', 'locale');
  $updates = $options = array();
  $languages_update = $languages_not_found = array();

  // @todo Calling locale_translation_build_projects() is an expensive way to
  //   get a module name. In follow-up issue http://drupal.org/node/1842362
  //   the project name will be stored to display use, like here.
  $project_data = locale_translation_build_projects();
  $languages = locale_translatable_language_list();
  $projects = locale_translation_get_projects();
  $status = Drupal::state()->get('locale.translation_status');

  // Prepare information about projects which have available translation
  // updates.
  if ($languages && $status) {
    foreach ($status as $project_id => $project) {
      foreach ($project as $langcode => $project_info) {
        // No translation file found for this project-language combination.
        if (!isset($project_info->type)) {
          $updates[$langcode]['not_found'][] = array(
            'name' => $project_info->name == 'drupal' ? t('Drupal core') : $project_data[$project_info->name]->info['name'],
            'version' => $project_info->version,
            'info' => _locale_translation_status_debug_info($project_info),
          );
          $languages_not_found[$langcode] = $langcode;
        }
        // Translation update found for this project-language combination.
        elseif ($project_info->type == LOCALE_TRANSLATION_LOCAL || $project_info->type == LOCALE_TRANSLATION_REMOTE ) {
          $local = isset($project_info->files[LOCALE_TRANSLATION_LOCAL]) ? $project_info->files[LOCALE_TRANSLATION_LOCAL] : NULL;
          $remote = isset($project_info->files[LOCALE_TRANSLATION_REMOTE]) ? $project_info->files[LOCALE_TRANSLATION_REMOTE] : NULL;
          $recent = _locale_translation_source_compare($local, $remote) == LOCALE_TRANSLATION_SOURCE_COMPARE_LT ? $remote : $local;
          $updates[$langcode]['updates'][] = array(
            'name' => $project_data[$project_info->name]->info['name'],
            'version' => $project_info->version,
            'timestamp' => $recent->timestamp,
          );
          $languages_update[$langcode] = $langcode;
        }
      }
    }
    $languages_not_found = array_diff($languages_not_found, $languages_update);

    // Build data options for the select table.
    foreach($updates as $langcode => $update) {
      $options[$langcode] = array(
        'title' => check_plain($languages[$langcode]->name),
        'status' => array('class' => array('description', 'expand', 'priority-low'), 'data' => theme('locale_translation_update_info', $update)),
      );
    }
    // Sort the table data on language name.
    uasort($options, 'drupal_sort_title');
  }

  $last_checked = Drupal::state()->get('locale.translation_last_checked');
  $form['last_checked'] = array(
    '#markup' => '<p>' . theme('locale_translation_last_check', array('last' => $last_checked)) . '</p>',
  );

  $header = array(
    'title' => array(
      'data' => t('Language'),
      'class' => array('title'),
    ),
    'status' => array(
      'data' => t('Status'),
      'class' => array('status', 'priority-low'),
    ),
  );

  if (!$languages) {
    $empty = t('No translatable languages available. <a href="@add_language">Add a language</a> first.', array('@add_language' => url('admin/config/regional/language')));
  }
  elseif ($status) {
    $empty = t('All translations up to date.');
  }
  else  {
    $empty = t('No translation status available. <a href="@check">Check manually</a>.', array('@check' => url('admin/reports/translations/check')));
  }

  $form['langcodes'] = array(
    '#type' => 'tableselect',
    '#header' => $header,
    '#options' => $options,
    '#default_value' => $languages_update,
    '#empty' => $empty,
    '#js_select' => TRUE,
    '#multiple' => TRUE,
    '#required' => TRUE,
    '#not_found' => $languages_not_found,
    '#after_build' => array('locale_translation_language_table'),
  );

  $form['#attached']['library'][] = array('locale', 'drupal.locale.admin');
  $form['#attached']['css'] = array(drupal_get_path('module', 'locale') . '/locale.admin.css');

  $form['actions'] = array('#type' => 'actions');
  if ($languages_update) {
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Update translations'),
    );
  }

  return $form;
}

/**
 * Form validation handler for locale_translation_status_form().
 */
function locale_translation_status_form_validate($form, &$form_state) {
  // Check if a language has been selected. 'tableselect' doesn't.
  if (!array_filter($form_state['values']['langcodes'])) {
    form_set_error('', t('Select a language to update.'));
  }
}

/**
 * Form submission handler for locale_translation_status_form().
 */
function locale_translation_status_form_submit($form, &$form_state) {
  module_load_include('fetch.inc', 'locale');
  $langcodes = array_filter($form_state['values']['langcodes']);

  // Set the translation import options. This determines if existing
  // translations will be overwritten by imported strings.
  $options = _locale_translation_default_update_options();

  // If the status was updated recently we can immediately start fetching the
  // translation updates. If the status is expired we clear it an run a batch to
  // update the status and then fetch the translation updates.
  $last_checked = Drupal::state()->get('locale.translation_last_checked');
  if ($last_checked < REQUEST_TIME - LOCALE_TRANSLATION_STATUS_TTL) {
    locale_translation_clear_status();
    $batch = locale_translation_batch_update_build(array(), $langcodes, $options);
    batch_set($batch);
  }
  else {
    $batch = locale_translation_batch_fetch_build(array(), $langcodes, $options);
    batch_set($batch);
  }
}

/**
 * Form element callback: After build changes to the language update table.
 *
 * Adds labels to the languages and removes checkboxes from languages from which
 * translation files could not be found.
 */
function locale_translation_language_table($form_element) {
  // Add labels to Language names.
  foreach ($form_element['#options'] as $langcode => $option) {
    $id = $form_element[$langcode]['#id'];
    $title = $option['title'];
    $form_element['#options'][$langcode]['title'] = '<label for="' . $form_element[$langcode]['#id'] . '" class="language-name">' . $title . '</label>';
  }

  // Remove checkboxes of languages without updates.
  if ($form_element['#not_found']) {
    foreach ($form_element['#not_found'] as $langcode) {
      $form_element[$langcode] = array();
    }
  }
  return $form_element;
}

/**
 * Provides debug info for projects in case translation files are not found.
 *
 * Translations files are being fetched either from Drupal translation server
 * and local files or only from the local filesystem depending on the
 * "Translation source" setting at admin/config/regional/translate/settings.
 * This method will produce debug information including the respective path(s)
 * based on this setting.
 *
 * Translations for development versions are never fetched, so the debug info
 * for that is a fixed message.
 *
 * @param array $source
 *   An array which is the project information of the source.
 *
 * @return string
 *   The string which contains debug information.
 */
function _locale_translation_status_debug_info($source) {
  $remote_path = isset($source->files['remote']->uri) ? $source->files['remote']->uri : '';
  $local_path = isset($source->files['local']->uri) ? $source->files['local']->uri : '';

  if (strpos($source->version, 'dev') !== FALSE) {
    return t('No translation files are provided for development releases.');
  }
  if (locale_translation_use_remote_source() && $remote_path && $local_path) {
    return t('File not found at %remote_path nor at %local_path', array(
      '%remote_path' => $remote_path,
      '%local_path' => $local_path,
    ));
  }
  elseif ($local_path) {
    return t('File not found at %local_path', array('%local_path' => $local_path));
  }
  return t('Translation file location could not be determined.');
}

/**
 * Returns HTML for translation edit form.
 *
 * @param array $variables
 *   An associative array containing:
 *   - form: The form that contains the language information.
 *
 * @see locale_translate_edit_form()
 * @ingroup themeable
 */
function theme_locale_translate_edit_form_strings($variables) {
  $output = '';
  $form = $variables['form'];
  $header = array(
    t('Source string'),
    t('Translation for @language', array('@language' => $form['#language'])),
  );
  $rows = array();
  foreach (element_children($form) as $lid) {
    $string = $form[$lid];
    if ($string['plural']['#value']) {
      $source = drupal_render($string['original_singular']) . '<br />' . drupal_render($string['original_plural']);
    }
    else {
      $source = drupal_render($string['original']);
    }
    $source .= empty($string['context']) ? '' : '<br /><small>' . t('In Context') . ':&nbsp;' . $string['context']['#value'] . '</small>';
    $rows[] = array(
      array('data' => $source),
      array('data' => $string['translations']),
    );
  }
  $output .= theme('table', array(
    'header' => $header,
    'rows' => $rows,
    'empty' => t('No strings available.'),
    'attributes' => array('class' => array('locale-translate-edit-table')),
  ));
  $output .= theme('pager');
  return $output;
}

/**
 * Returns HTML for translation status information per language.
 *
 * @param array $variables
 *   An associative array containing:
 *   - updates: The projects which have updates.
 *   - not_found: The projects which updates are not found.
 *
 * @see locale_translation_status_form()
 * @ingroup themeable
 */
function theme_locale_translation_update_info($variables) {
  $description = $details = '';

  // Build output for available updates.
  if (isset($variables['updates'])) {
    if ($variables['updates']) {
      $releases = array();
      foreach ($variables['updates'] as $update) {
        $modules[] = $update['name'];
        $releases[] = t('@module (@date)', array('@module' => $update['name'], '@date' => format_date($update['timestamp'], 'html_date')));
      }
    }
    $description = '<span class="text">' . t('Updates for: @modules', array('@modules' => implode(', ', $modules))) . '</span>';
    $details = theme('item_list', array('items' => $releases));
  }

  // Build output for updates not found.
  if (isset($variables['not_found'])) {
    if (empty($description)) {
      $description = '<span class="text">' . format_plural(count($variables['not_found']), 'Missing translations for one project', 'Missing translations for @count projects') . '</span>';
    }
    if ($variables['not_found']) {
      $releases = array();
      foreach ($variables['not_found'] as $update) {
        $version = $update['version'] ? $update['version'] : t('no version');
        $releases[] = t('@module (@version).', array('@module' => $update['name'], '@version' => $version)) . ' ' . $update['info'];
      }
    }
    if ($details) {
      $details .= t('Missing translations for:');
    }
    $details .= theme('item_list', array('items' => $releases));
  }

  $output = '<div class="inner" tabindex="0" role="button">';
  $output .= '<span class="update-description-prefix element-invisible">Show description</span>' . $description;
  $output .= $details ? '<div class="details">' . $details . '</div>' : '';
  $output .= '</div>';
  return $output;
}

/**
 * Returns HTML for the last time we checked for update data.
 *
 * In addition to properly formatting the given timestamp, this function also
 * provides a "Check manually" link that refreshes the available update and
 * redirects back to the same page.
 *
 * @param $variables
 *   An associative array containing:
 *   - last: The timestamp when the site last checked for available updates.
 *
 * @see locale_translation_status_form()
 * @ingroup themeable
 */
function theme_locale_translation_last_check($variables) {
  $last = $variables['last'];
  $output = '<div class="locale checked">';
  $output .= $last ? t('Last checked: @time ago', array('@time' => format_interval(REQUEST_TIME - $last))) : t('Last checked: never');
  $output .= ' <span class="check-manually">(' . l(t('Check manually'), 'admin/reports/translations/check', array('query' => drupal_get_destination())) . ')</span>';
  $output .= "</div>\n";
  return $output;
}
