<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\cache\Time.
 */

namespace Drupal\views\Plugin\views\cache;

use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Render\RenderCacheInterface;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;

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

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * Constructs a Time cache plugin object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The HTML renderer.
   * @param \Drupal\Core\Datetime\DateFormatter $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Render\RenderCacheInterface $render_cache
   *   The render cache service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RendererInterface $renderer, RenderCacheInterface $render_cache, DateFormatter $date_formatter) {
    $this->dateFormatter = $date_formatter;
    parent::__construct($configuration, $plugin_id, $plugin_definition, $renderer, $render_cache);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('renderer'),
      $container->get('render_cache'),
      $container->get('date.formatter')
    );
  }

  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['results_lifespan'] = array('default' => 3600);
    $options['results_lifespan_custom'] = array('default' => 0);
    $options['output_lifespan'] = array('default' => 3600);
    $options['output_lifespan_custom'] = array('default' => 0);

    return $options;
  }

  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $options = array(60, 300, 1800, 3600, 21600, 518400);
    $options = array_map(array($this->dateFormatter, 'formatInterval'), array_combine($options, $options));
    $options = array(-1 => $this->t('Never cache')) + $options + array('custom' => $this->t('Custom'));

    $form['results_lifespan'] = array(
      '#type' => 'select',
      '#title' => $this->t('Query results'),
      '#description' => $this->t('The length of time raw query results should be cached.'),
      '#options' => $options,
      '#default_value' => $this->options['results_lifespan'],
    );
    $form['results_lifespan_custom'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Seconds'),
      '#size' => '25',
      '#maxlength' => '30',
      '#description' => $this->t('Length of time in seconds raw query results should be cached.'),
      '#default_value' => $this->options['results_lifespan_custom'],
      '#states' => array(
        'visible' => array(
          ':input[name="cache_options[results_lifespan]"]' => array('value' => 'custom'),
        ),
      ),
    );
    $form['output_lifespan'] = array(
      '#type' => 'select',
      '#title' => $this->t('Rendered output'),
      '#description' => $this->t('The length of time rendered HTML output should be cached.'),
      '#options' => $options,
      '#default_value' => $this->options['output_lifespan'],
    );
    $form['output_lifespan_custom'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Seconds'),
      '#size' => '25',
      '#maxlength' => '30',
      '#description' => $this->t('Length of time in seconds rendered HTML output should be cached.'),
      '#default_value' => $this->options['output_lifespan_custom'],
      '#states' => array(
        'visible' => array(
          ':input[name="cache_options[output_lifespan]"]' => array('value' => 'custom'),
        ),
      ),
    );
  }

  public function validateOptionsForm(&$form, FormStateInterface $form_state) {
    $custom_fields = array('output_lifespan', 'results_lifespan');
    foreach ($custom_fields as $field) {
      $cache_options = $form_state->getValue('cache_options');
      if ($cache_options[$field] == 'custom' && !is_numeric($cache_options[$field . '_custom'])) {
        $form_state->setError($form[$field .'_custom'], $this->t('Custom time values must be numeric.'));
      }
    }
  }

  public function summaryTitle() {
    $results_lifespan = $this->getLifespan('results');
    $output_lifespan = $this->getLifespan('output');
    return $this->dateFormatter->formatInterval($results_lifespan, 1) . '/' . $this->dateFormatter->formatInterval($output_lifespan, 1);
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
