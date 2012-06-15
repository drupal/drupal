<?php

/**
 * @file
 * Interface translation summary, editing and deletion user interfaces.
 */

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * String search screen.
 */
function locale_translate_page() {
  return array(
    'filter' => drupal_get_form('locale_translate_filter_form'),
    'form' => drupal_get_form('locale_translate_edit_form'),
  );
}

/**
 * Build a string search query.
 */
function locale_translate_query() {
  $filter_values = locale_translate_filter_values();

  $sql_query = db_select('locales_source', 's');
  // Language is sanitized to be one of the possible options in
  // locale_translate_filter_values().
  $sql_query->leftJoin('locales_target', 't', "t.lid = s.lid AND t.language = :langcode", array(':langcode' => $filter_values['langcode']));
  $sql_query->fields('s', array('source', 'location', 'context', 'lid'));
  $sql_query->fields('t', array('translation', 'language', 'customized'));

  if (!empty($filter_values['string'])) {
    $sql_query->condition(db_or()
      ->condition('s.source', '%' . db_like($filter_values['string']) . '%', 'LIKE')
      ->condition('t.translation', '%' . db_like($filter_values['string']) . '%', 'LIKE')
    );
  }

  // Add translation status conditions.
  switch ($filter_values['translation']) {
    case 'translated':
      $sql_query->isNotNull('t.translation');
      if ($filter_values['customized'] != 'all') {
        $sql_query->condition('t.customized', $filter_values['customized']);
      }
      break;

    case 'untranslated':
      $sql_query->isNull('t.translation');
      break;

  }

  $sql_query = $sql_query->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(30);
  return $sql_query->execute();
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
  $default_langcode = drupal_container()->get(LANGUAGE_TYPE_INTERFACE)->langcode;
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
    '#type' => 'fieldset',
    '#title' => t('Filter translatable strings'),
    '#collapsible' => TRUE,
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
      $empty_option = isset($filter['options'][$filter['default']]) ? $filter['options'][$filter['default']] : '<none>';
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
 * User interface for string editing as one table.
 *
 * @ingroup forms
 */
function locale_translate_edit_form($form, &$form_state) {
  $filter_values = locale_translate_filter_values();
  $langcode = $filter_values['langcode'];

  drupal_static_reset('language_list');
  $languages = language_list();

  $langname = isset($langcode) ? $languages[$langcode]->name : "<none>";

  $path = drupal_get_path('module', 'locale');
  $form['#attached']['css'] = array(
    $path . '/locale.admin.css',
  );
  $form['#attached']['js'] = array(
    $path . '/locale.admin.js',
  );

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
    $strings = locale_translate_query();

    $plural_formulas = variable_get('locale_translation_plurals', array());

    foreach ($strings as $string) {
      // Split source to work with plural values.
      $source_array = explode(LOCALE_PLURAL_DELIMITER, $string->source);
      $translation_array = explode(LOCALE_PLURAL_DELIMITER, $string->translation);
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
      $form['strings'][$string->lid]['location'] = array(
        '#type' => 'value',
        '#value' => $string->location,
      );

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
 * Validate string editing form submissions.
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
 * Process string editing form submissions.
 */
function locale_translate_edit_form_submit($form, &$form_state) {
  $langcode = $form_state['values']['langcode'];
  foreach ($form_state['values']['strings'] as $lid => $translations) {
    // Serialize plural variants in one string by LOCALE_PLURAL_DELIMITER.
    $translation_new = implode(LOCALE_PLURAL_DELIMITER, $translations['translations']);
    $translation_old = db_query("SELECT translation FROM {locales_target} WHERE lid = :lid AND language = :language", array(':lid' => $lid, ':language' => $langcode))->fetchField();
    // No translation when all strings are empty.
    $has_translation = FALSE;
    foreach ($translations['translations'] as $string) {
      if (!empty($string)) {
        $has_translation = TRUE;
        break;
      }
    }
    if ($has_translation) {
      // Only update or insert if we have a value to use.
      if (!empty($translation_old) && $translation_old != $translation_new) {
        db_update('locales_target')
          ->fields(array(
            'translation' => $translation_new,
            'customized' => LOCALE_CUSTOMIZED,
          ))
          ->condition('lid', $lid)
          ->condition('language', $langcode)
          ->execute();
      }
      if (empty($translation_old)) {
        db_insert('locales_target')
          ->fields(array(
            'lid' => $lid,
            'translation' => $translation_new,
            'language' => $langcode,
            'customized' => LOCALE_CUSTOMIZED,
          ))
          ->execute();
      }
    }
    elseif (!empty($translation_old)) {
      // Empty translation entered: remove existing entry from database.
      db_delete('locales_target')
        ->condition('lid', $lid)
        ->condition('language', $langcode)
        ->execute();
    }

  }

  drupal_set_message(t('The strings have been saved.'));

  // Keep the user on the current pager page.
  if (isset($_GET['page'])) {
    $form_state['redirect'] = array('admin/config/regional/translate', array('query' => array('page' => $_GET['page'])));
  }

  // Force JavaScript translation file recreation for this language.
  _locale_invalidate_js($langcode);
  // Clear locale cache.
  cache()->deletePrefix('locale:');
}

/**
 * Default theme function for translatione edit form.
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
