<?php

/**
 * @file
 */

use Drupal\Core\Hook\Attribute\ProceduralHookScanStop;
use Drupal\Core\Link;
use Drupal\Core\Url;

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
#[ProceduralHookScanStop]
function template_preprocess_locale_translation_update_info(array &$variables): void {
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
function template_preprocess_locale_translation_last_check(array &$variables): void {
  $last = $variables['last'];
  $variables['last_checked'] = ($last != NULL);
  $variables['time'] = $variables['last_checked'] ? \Drupal::service('date.formatter')->formatTimeDiffSince($last) : NULL;
  $variables['link'] = Link::fromTextAndUrl(t('Check manually'), Url::fromRoute('locale.check_translation', [], ['query' => \Drupal::destination()->getAsArray()]))->toString();
}
