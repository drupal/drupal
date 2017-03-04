<?php

/**
 * @file
 * Interface translation summary, editing and deletion user interfaces.
 */

use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

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
  return new RedirectResponse(\Drupal::url('locale.translate_status', [], ['absolute' => TRUE]));
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
 * @see \Drupal\locale\Form\TranslationStatusForm
 */
function template_preprocess_locale_translation_update_info(array &$variables) {
  foreach ($variables['updates'] as $update) {
    $variables['modules'][] = $update['name'];
  }
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
 * @param array $variables
 *   An associative array containing:
 *   - last: The timestamp when the site last checked for available updates.
 *
 * @see \Drupal\locale\Form\TranslationStatusForm
 */
function template_preprocess_locale_translation_last_check(array &$variables) {
  $last = $variables['last'];
  $variables['last_checked'] = ($last != NULL);
  $variables['time'] = \Drupal::service('date.formatter')->formatTimeDiffSince($last);
  $variables['link'] = \Drupal::l(t('Check manually'), new Url('locale.check_translation', [], ['query' => \Drupal::destination()->getAsArray()]));
}
