<?php

/**
 * @file
 * Preprocess functions for the System module.
 */

use Drupal\Core\Url;

/**
 * Prepares variables for security advisories fetch error message templates.
 *
 * Default template: system-security-advisories-fetch-error-message.html.twig.
 *
 * @param array $variables
 *   An associative array of template variables.
 */
function template_preprocess_system_security_advisories_fetch_error_message(array &$variables): void {
  $variables['error_message'] = [
    'message' => [
      '#markup' => t('Failed to fetch security advisory data:'),
    ],
    'items' => [
      '#theme' => 'item_list',
      '#items' => [
        'documentation_link' => t('See <a href=":url">Troubleshooting the advisory feed</a> for possible causes and resolutions.', [':url' => 'https://www.drupal.org/docs/updating-drupal/responding-to-critical-security-update-advisories#s-troubleshooting-the-advisory-feed']),
      ],
    ],
  ];
  if (\Drupal::moduleHandler()->moduleExists('dblog') && \Drupal::currentUser()->hasPermission('access site reports')) {
    $options = ['query' => ['type' => ['system']]];
    $dblog_url = Url::fromRoute('dblog.overview', [], $options);
    $variables['error_message']['items']['#items']['dblog'] = t('Check <a href=":url">your local system logs</a> for additional error messages.', [':url' => $dblog_url->toString()]);
  }
  else {
    $variables['error_message']['items']['#items']['logs'] = t('Check your local system logs for additional error messages.');
  }
}
