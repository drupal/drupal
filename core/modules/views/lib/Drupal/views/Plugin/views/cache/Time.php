<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\cache\Time.
 */

namespace Drupal\views\Plugin\views\cache;

use Drupal\Core\Cache\Cache;

/**
 * Simple caching of query results for Views displays.
 *
 * @ingroup views_cache_plugins
 *
 * @ViewsCache(
 *   id = "time",
 *   title = @Translation("Time-based"),
 *   help = @Translation("Simple time-based caching of data.")
 * )
 */
class Time extends CachePluginBase {

  /**
   * Overrides Drupal\views\Plugin\Plugin::$usesOptions.
   */
  protected $usesOptions = TRUE;

  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['results_lifespan'] = array('default' => 3600);
    $options['results_lifespan_custom'] = array('default' => 0);
    $options['output_lifespan'] = array('default' => 3600);
    $options['output_lifespan_custom'] = array('default' => 0);

    return $options;
  }

  public function buildOptionsForm(&$form, &$form_state) {
    parent::buildOptionsForm($form, $form_state);
    $options = array(60, 300, 1800, 3600, 21600, 518400);
    $options = array_map('format_interval', array_combine($options, $options));
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
      '#states' => array(
        'visible' => array(
          ':input[name="cache_options[results_lifespan]"]' => array('value' => 'custom'),
        ),
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
      '#states' => array(
        'visible' => array(
          ':input[name="cache_options[output_lifespan]"]' => array('value' => 'custom'),
        ),
      ),
    );
  }

  public function validateOptionsForm(&$form, &$form_state) {
    $custom_fields = array('output_lifespan', 'results_lifespan');
    foreach ($custom_fields as $field) {
      if ($form_state['values']['cache_options'][$field] == 'custom' && !is_numeric($form_state['values']['cache_options'][$field . '_custom'])) {
        form_error($form[$field .'_custom'], $form_state, t('Custom time values must be numeric.'));
      }
    }
  }

  public function summaryTitle() {
    $results_lifespan = $this->getLifespan('results');
    $output_lifespan = $this->getLifespan('output');
    return format_interval($results_lifespan, 1) . '/' . format_interval($output_lifespan, 1);
  }

  protected function getLifespan($type) {
    $lifespan = $this->options[$type . '_lifespan'] == 'custom' ? $this->options[$type . '_lifespan_custom'] : $this->options[$type . '_lifespan'];
    return $lifespan;
  }

  protected function cacheExpire($type) {
    $lifespan = $this->getLifespan($type);
    if ($lifespan) {
      $cutoff = REQUEST_TIME - $lifespan;
      return $cutoff;
    }
    else {
      return FALSE;
    }
  }

  protected function cacheSetExpire($type) {
    $lifespan = $this->getLifespan($type);
    if ($lifespan) {
      return time() + $lifespan;
    }
    else {
      return Cache::PERMANENT;
    }
  }

}
