<?php

/**
 * @file
 * Interface translation summary, editing and deletion user interfaces.
 */

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Url;
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
  return new RedirectResponse(\Drupal::url('locale.translate_status', array(), array('absolute' => TRUE)));
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
  $details = array();

  // Build output for available updates.
  if (isset($variables['updates'])) {
    $releases = array();
    if ($variables['updates']) {
      foreach ($variables['updates'] as $update) {
        $modules[] = $update['name'];
        $releases[] = SafeMarkup::format('@module (@date)', array(
          '@module' => $update['name'],
          '@date' => format_date($update['timestamp'], 'html_date'),
        ));
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
    $variables['missing_updates_status'] = \Drupal::translation()->formatPlural(count($variables['not_found']), 'Missing translations for one project', 'Missing translations for @count projects');
    if ($variables['not_found']) {
      foreach ($variables['not_found'] as $update) {
        $version = $update['version'] ? $update['version'] : t('no version');
        $releases[] = SafeMarkup::format('@module (@version). !info', array(
          '@module' => $update['name'],
          '@version' => $version,
          '!info' => $update['info'],
        ));
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
 * @param array $variables
 *   An associative array containing:
 *   - last: The timestamp when the site last checked for available updates.
 *
 * @see \Drupal\locale\Form\TranslationStatusForm
 */
function template_preprocess_locale_translation_last_check(array &$variables) {
  $last = $variables['last'];
  $variables['last_checked'] = ($last != NULL);
  $variables['time'] = \Drupal::service('date.formatter')->formatInterval(REQUEST_TIME - $last);
  $variables['link'] = \Drupal::l(t('Check manually'), new Url('locale.check_translation', array(), array('query' => \Drupal::destination()->getAsArray())));
}
