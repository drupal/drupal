<?php

/**
 * @file
 * Admin page callbacks for the Statistics module.
 */

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Form constructor for the statistics administration form.
 *
 * @ingroup forms
 * @see statistics_settings_form_submit().
 */
function statistics_settings_form($form, &$form_state) {
  $config = config('statistics.settings');

  // Content counter settings.
  $form['content'] = array(
    '#type' => 'details',
    '#title' => t('Content viewing counter settings'),
  );
  $form['content']['statistics_count_content_views'] = array(
    '#type' => 'checkbox',
    '#title' => t('Count content views'),
    '#default_value' => $config->get('count_content_views'),
    '#description' => t('Increment a counter each time content is viewed.'),
  );

  return system_config_form($form, $form_state);
}

/**
 * Form submission handler for statistics_settings_form().
 */
function statistics_settings_form_submit($form, &$form_state) {
  config('statistics.settings')
    ->set('count_content_views', $form_state['values']['statistics_count_content_views'])
    ->save();
  // The popular statistics block is dependent on these settings, so clear the
  // block plugin definitions cache.
  if (module_exists('block')) {
    drupal_container()->get('plugin.manager.block')->clearCachedDefinitions();
  }
}
