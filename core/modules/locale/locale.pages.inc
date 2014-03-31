<?php

/**
 * @file
 * Interface translation summary, editing and deletion user interfaces.
 */

use Drupal\Component\Utility\String;
use Drupal\Core\Language\Language;
use Drupal\Core\Render\Element;
use Drupal\locale\SourceString;
use Drupal\locale\TranslationString;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Page callback: Checks for translation updates and displays the status.
 *
 * Manually checks the translation status without the use of cron.
 *
 * @see locale_menu()
 */
function locale_translation_manual_status() {
  module_load_include('compare.inc', 'locale');

  // Check the translation status of all translatable projects in all languages.
  // First we clear the cached list of projects. Although not strictly
  // necessary, this is helpful in case the project list is out of sync.
  locale_translation_flush_projects();
  locale_translation_check_projects();

  // Execute a batch if required. A batch is only used when remote files
  // are checked.
  if (batch_get()) {
    return batch_process('admin/reports/translations');
  }
  return new RedirectResponse(url('admin/reports/translations', array('absolute' => TRUE)));
}

/**
 * Page callback: Display the current translation status.
 *
 * @see locale_menu()
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
 *   Use \Drupal\locale\Form\LocaleForm::status().
 */
function locale_translation_status_form($form, &$form_state) {
  module_load_include('translation.inc', 'locale');
  module_load_include('compare.inc', 'locale');
  $updates = $options = array();
  $languages_update = $languages_not_found = array();
  $projects_update = array();

  // @todo Calling locale_translation_build_projects() is an expensive way to
  //   get a module name. In follow-up issue http://drupal.org/node/1842362
  //   the project name will be stored to display use, like here.
  $project_data = locale_translation_build_projects();
  $languages = locale_translatable_language_list();
  $status = locale_translation_get_status();

  // Prepare information about projects which have available translation
  // updates.
  if ($languages && $status) {
    foreach ($status as $project) {
      foreach ($project as $langcode => $project_info) {
        // No translation file found for this project-language combination.
        if (empty($project_info->type)) {
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
          $projects_update[$project_info->name] = $project_info->name;
        }
      }
    }
    $languages_not_found = array_diff($languages_not_found, $languages_update);

    // Build data options for the select table.
    foreach($updates as $langcode => $update) {
      $title = String::checkPlain($languages[$langcode]->name);
      $locale_translation_update_info = array('#theme' => 'locale_translation_update_info');
      foreach (array('updates', 'not_found') as $update_status) {
        if (isset($update[$update_status])) {
          $locale_translation_update_info['#' . $update_status] = $update[$update_status];
        }
      }
      $options[$langcode] = array(
        'title' => array(
          'class' => array('label'),
          'data' => array(
            '#title' => $title,
            '#markup' => $title
          ),
        ),
        'status' => array('class' => array('description', 'expand', 'priority-low'), 'data' => drupal_render($locale_translation_update_info)),
      );
    }
    // Sort the table data on language name.
    uasort($options, function ($a, $b) {
      return strcasecmp($a['title']['data']['#title'], $b['title']['data']['#title']);
    });
  }

  $last_checked = \Drupal::state()->get('locale.translation_last_checked');
  $form['last_checked'] = array(
    '#theme' => 'locale_translation_last_check',
    '#last' => $last_checked,
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

  // The projects which require an update. Used by the _submit callback.
  $form['projects_update'] = array(
    '#type' => 'value',
    '#value' => $projects_update,
  );

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

  $form['#attached']['library'][] = 'locale/drupal.locale.admin';

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
    form_set_error('', $form_state, t('Select a language to update.'));
  }
}

/**
 * Form submission handler for locale_translation_status_form().
 */
function locale_translation_status_form_submit($form, &$form_state) {
  module_load_include('fetch.inc', 'locale');
  $langcodes = array_filter($form_state['values']['langcodes']);
  $projects = array_filter($form_state['values']['projects_update']);

  // Set the translation import options. This determines if existing
  // translations will be overwritten by imported strings.
  $options = _locale_translation_default_update_options();

  // If the status was updated recently we can immediately start fetching the
  // translation updates. If the status is expired we clear it an run a batch to
  // update the status and then fetch the translation updates.
  $last_checked = \Drupal::state()->get('locale.translation_last_checked');
  if ($last_checked < REQUEST_TIME - LOCALE_TRANSLATION_STATUS_TTL) {
    locale_translation_clear_status();
    $batch = locale_translation_batch_update_build(array(), $langcodes, $options);
    batch_set($batch);
  }
  else {
    $batch = locale_translation_batch_fetch_build($projects, $langcodes, $options);
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
  foreach (Element::children($form) as $lid) {
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
  $table = array(
    '#type' => 'table',
    '#header' => $header,
    '#rows' => $rows,
    '#empty' => t('No strings available.'),
    '#attributes' => array('class' => array('locale-translate-edit-table')),
  );
  $output .= drupal_render($table);
  $pager = array('#theme' => 'pager');
  $output .= drupal_render($pager);
  return $output;
}

/**
 * Prepares variables for translation status information templates.
 *
 * Translation status information is displayed per language.
 *
 * Default template: locale-translate-edit-form-strings.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - updates: The projects which have updates.
 *   - not_found: The projects which updates are not found.
 *
 * @see locale_translation_status_form()
 */
function template_preprocess_locale_translation_update_info(&$variables) {
  $details = array();

  // Build output for available updates.
  if (isset($variables['updates'])) {
    $releases = array();
    if ($variables['updates']) {
      foreach ($variables['updates'] as $update) {
        $modules[] = $update['name'];
        $releases[] = t('@module (@date)', array('@module' => $update['name'], '@date' => format_date($update['timestamp'], 'html_date')));
      }
      $variables['modules'] = $modules;
    }
    $details['available_updates_list'] = array(
      '#theme' => 'item_list',
      '#items' => $releases,
    );
  }

  // Build output for updates not found.
  if (isset($variables['not_found'])) {
    $releases = array();
    $variables['missing_updates_status'] = format_plural(count($variables['not_found']), 'Missing translations for one project', 'Missing translations for @count projects');
    if ($variables['not_found']) {
      foreach ($variables['not_found'] as $update) {
        $version = $update['version'] ? $update['version'] : t('no version');
        $releases[] = t('@module (@version).', array('@module' => $update['name'], '@version' => $version)) . ' ' . $update['info'];
      }
    }
    $details['missing_updates_list'] = array(
      '#theme' => 'item_list',
      '#items' => $releases,
    );
    // Prefix the missing updates list if there is an available updates lists
    // before it.
    if (!empty($details['available_updates_list']['#items'])) {
      $details['missing_updates_list']['#prefix'] = t('Missing translations for:');
    }
  }
  $variables['details'] = $details;
}

/**
 * Prepares variables for most recent translation update templates.
 *
 * Displays the last time we checked for locale update data. In addition to
 * properly formatting the given timestamp, this function also provides a "Check
 * manually" link that refreshes the available update and redirects back to the
 * same page.
 *
 * Default template: locale-translation-last-check.html.twig.
 *
 * @param $variables
 *   An associative array containing:
 *   - last: The timestamp when the site last checked for available updates.
 *
 * @see locale_translation_status_form()
 */
function template_preprocess_locale_translation_last_check(&$variables) {
  $last = $variables['last'];
  $variables['last_checked'] = ($last != NULL);
  $variables['time'] = format_interval(REQUEST_TIME - $last);
  $variables['link'] = l(t('Check manually'), 'admin/reports/translations/check', array('query' => drupal_get_destination()));
}
