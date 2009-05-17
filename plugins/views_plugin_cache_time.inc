<?php

/**
 * @file
 * Definition of views_plugin_cache_time.
 */

/**
 * Simple caching of query results for Views displays.
 *
 * @ingroup views_cache_plugins
 */
class views_plugin_cache_time extends views_plugin_cache {
  function option_definition() {
    $options = parent::option_definition();
    $options['results_lifespan'] = array('default' => 3600);
    $options['results_lifespan_custom'] = array('default' => 0);
    $options['output_lifespan'] = array('default' => 3600);
    $options['output_lifespan_custom'] = array('default' => 0);

    return $options;
  }

  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
    $options = array(60, 300, 1800, 3600, 21600, 518400);
    $options = drupal_map_assoc($options, 'format_interval');
    $options = array(-1 => t('Never cache')) + $options + array('custom' => t('Custom'));

    $form['results_lifespan'] = array(
      '#type' => 'select',
      '#title' => t('Query results'),
      '#description' => t('The length of time raw query results should be cached.'),
      '#options' => $options,
      '#default_value' => $this->options['results_lifespan'],
    );
    $form['results_lifespan_custom'] = array(
      '#type' => 'textfield',
      '#title' => t('Seconds'),
      '#size' => '25',
      '#maxlength' => '30',
      '#description' => t('Length of time in seconds raw query results should be cached.'),
      '#default_value' => $this->options['results_lifespan_custom'],
      '#process' => array('form_process_select','ctools_dependent_process'),
      '#dependency' => array(
        'edit-cache-options-results-lifespan' => array('custom'),
      ),
    );
    $form['output_lifespan'] = array(
      '#type' => 'select',
      '#title' => t('Rendered output'),
      '#description' => t('The length of time rendered HTML output should be cached.'),
      '#options' => $options,
      '#default_value' => $this->options['output_lifespan'],
    );
    $form['output_lifespan_custom'] = array(
      '#type' => 'textfield',
      '#title' => t('Seconds'),
      '#size' => '25',
      '#maxlength' => '30',
      '#description' => t('Length of time in seconds rendered HTML output should be cached.'),
      '#default_value' => $this->options['output_lifespan_custom'],
      '#process' => array('form_process_select','ctools_dependent_process'),
      '#dependency' => array(
        'edit-cache-options-output-lifespan' => array('custom'),
      ),
    );
  }

  function options_validate(&$form, &$form_state) {
    $custom_fields = array('output_lifespan', 'results_lifespan');
    foreach ($custom_fields as $field) {
      if ($form_state['values']['cache_options'][$field] == 'custom' && !is_numeric($form_state['values']['cache_options'][$field . '_custom'])) {
        form_error($form[$field .'_custom'], t('Custom time values must be numeric.'));
      }
    }
  }

  function summary_title() {
    $custom_fields = array('output_lifespan', 'results_lifespan');
    foreach ($custom_fields as $field) {
      ${$field} = $this->options[$field] == 'custom' ? $this->options[$field . '_custom'] : $this->options[$field];
    }
    return format_interval($results_lifespan, 1) . '/' . format_interval($output_lifespan, 1);
  }

  function cache_expire($type) {
    $lifespan = $this->options[$type . '_lifespan'] == 'custom' ? $this->options[$type . '_lifespan_custom'] : $this->options[$type . '_lifespan'];
    if ($lifespan) {
      $cutoff = REQUEST_TIME - $lifespan;
      return $cutoff;
    }
    else {
      return FALSE;
    }
  }

  function cache_set_expire($type) {
    $lifespan = $this->options[$type . '_lifespan'] == 'custom' ? $this->options[$type . '_lifespan_custom'] : $this->options[$type . '_lifespan'];
    if ($lifespan) {
      return time() + $lifespan;
    }
    else {
      return CACHE_PERMANENT;
    }
  }
}
