<?php

/**
 * @file
 * Interface translation summary, editing and deletion user interfaces.
 */

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
  $default_langcode = language(LANGUAGE_TYPE_INTERFACE)->langcode;
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

  $langname = isset($langcode) ? $languages[$langcode]->name : "<none>";

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

    $plural_formulas = variable_get('locale_translation_plurals', array());

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
  $strings = array();
  foreach (locale_storage()->getTranslations(array('lid' => $lids, 'language' => $langcode, 'translated' => TRUE)) as $string) {
    $strings[$string->lid] = $string;
  }

  foreach ($form_state['values']['strings'] as $lid => $translations) {
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
      $target = isset($strings[$lid]) ? $strings[$lid] : locale_storage()->createTranslation(array('lid' => $lid, 'language' => $langcode));
      $target->setPlurals($translations['translations'])
        ->setCustomized()
        ->save();
      $updated[] = $target->getId();
    }
    elseif (isset($strings[$lid])) {
      // Empty translation entered: remove existing entry from database.
      $strings[$lid]->delete();
      $updated[] = $lid;
    }
  }

  drupal_set_message(t('The strings have been saved.'));

  // Keep the user on the current pager page.
  if (isset($_GET['page'])) {
    $form_state['redirect'] = array('admin/config/regional/translate', array('query' => array('page' => $_GET['page'])));
  }

  if ($updated) {
    // Clear cache and force refresh of JavaScript translations.
    _locale_refresh_translations(array($langcode), $updated);
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

  locale_translation_flush_projects();
  $projects = locale_translation_get_projects();
  locale_translation_check_projects($projects);

  // Execute a batch if required.
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
function locale_translation_status() {
  $languages = locale_translatable_language_list();
  if (!$languages) {
    drupal_set_message(t('No translatable languages available. <a href="@add_lanuage">Add language</a> first.', array('@add_lanuage' => url('admin/config/regional/language'))), 'warning');
  }

  // @todo Calculate and display the translation status here. See the follow-up
  // issue for translation interface: http://drupal.org/node/1804702
  return 'TODO: Show the translation status here';
}

/**
 * Default theme function for translation edit form.
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
