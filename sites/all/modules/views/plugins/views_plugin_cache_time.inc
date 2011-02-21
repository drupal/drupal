<?php
// $Id: views_plugin_cache_time.inc,v 1.3.4.4 2010/11/18 00:56:20 merlinofchaos Exp $

/**
 * Simple caching of query results for Views displays.
 */
class views_plugin_cache_time extends views_plugin_cache {
  function option_definition() {
    $options = parent::option_definition();
    $options['results_lifespan'] = array('default' => 3600);
    $options['output_lifespan'] = array('default' => 3600);

    return $options;
  }

  function options_form(&$form, &$form_state) {
    $options = array(60, 300, 1800, 3600, 21600, 518400);
    $options = drupal_map_assoc($options, 'format_interval');
    $options = array(-1 => t('Never cache')) + $options;

    $form['results_lifespan'] = array(
      '#type' => 'select',
      '#title' => t('Query results'),
      '#description' => t('The length of time raw query results should be cached.'),
      '#options' => $options,
      '#default_value' => $this->options['results_lifespan'],
    );
    $form['output_lifespan'] = array(
      '#type' => 'select',
      '#title' => t('Rendered output'),
      '#description' => t('The length of time rendered HTML output should be cached.'),
      '#options' => $options,
      '#default_value' => $this->options['output_lifespan'],
    );
  }

  function summary_title() {
    return format_interval($this->options['results_lifespan'], 1) . '/' . format_interval($this->options['output_lifespan'], 1);
  }

  function cache_expire($type) {
    if ($lifespan = $this->options[$type . '_lifespan']) {
      $cutoff = REQUEST_TIME - $lifespan;
      return $cutoff;
    }
    else {
      return FALSE;
    }
  }

  function cache_set_expire($type) {
    if ($lifespan = $this->options[$type . '_lifespan']) {
      return time() + $lifespan;
    }
    else {
      return CACHE_PERMANENT;
    }
  }
}
