<?php

namespace Drupal\views\Plugin\views\cache;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\views\Attribute\ViewsCache;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Simple caching of query results for Views displays.
 *
 * @ingroup views_cache_plugins
 */
#[ViewsCache(
  id: 'time',
  title: new TranslatableMarkup('Time-based'),
  help: new TranslatableMarkup('Simple time-based caching of data.'),
)]
class Time extends CachePluginBase {

  /**
   * {@inheritdoc}
   */
  protected $usesOptions = TRUE;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
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
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Component\Datetime\TimeInterface|null $time
   *   The time service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, DateFormatterInterface $date_formatter, protected ?TimeInterface $time = NULL) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->dateFormatter = $date_formatter;
    if (!$time) {
      @trigger_error('Calling ' . __METHOD__ . ' without the $time argument is deprecated in drupal:10.3.0 and it will be required in drupal:11.0.0. See https://www.drupal.org/node/3395991', E_USER_DEPRECATED);
      $this->time = \Drupal::service('datetime.time');
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('date.formatter'),
      $container->get('datetime.time'),
    );
  }

  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['results_lifespan'] = ['default' => 3600];
    $options['results_lifespan_custom'] = ['default' => 0];
    $options['output_lifespan'] = ['default' => 3600];
    $options['output_lifespan_custom'] = ['default' => 0];

    return $options;
  }

  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $options = [60, 300, 1800, 3600, 21600, 518400];
    $options = array_map([$this->dateFormatter, 'formatInterval'], array_combine($options, $options));
    $options = [0 => $this->t('Never cache')] + $options + ['custom' => $this->t('Custom')];

    $form['results_lifespan'] = [
      '#type' => 'select',
      '#title' => $this->t('Query results'),
      '#description' => $this->t('The length of time raw query results should be cached.'),
      '#options' => $options,
      '#default_value' => $this->options['results_lifespan'],
    ];
    $form['results_lifespan_custom'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Seconds'),
      '#size' => '25',
      '#maxlength' => '30',
      '#description' => $this->t('Length of time in seconds raw query results should be cached.'),
      '#default_value' => $this->options['results_lifespan_custom'],
      '#states' => [
        'visible' => [
          ':input[name="cache_options[results_lifespan]"]' => ['value' => 'custom'],
        ],
      ],
    ];
    $form['output_lifespan'] = [
      '#type' => 'select',
      '#title' => $this->t('Rendered output'),
      '#description' => $this->t('The length of time rendered HTML output should be cached.'),
      '#options' => $options,
      '#default_value' => $this->options['output_lifespan'],
    ];
    $form['output_lifespan_custom'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Seconds'),
      '#size' => '25',
      '#maxlength' => '30',
      '#description' => $this->t('Length of time in seconds rendered HTML output should be cached.'),
      '#default_value' => $this->options['output_lifespan_custom'],
      '#states' => [
        'visible' => [
          ':input[name="cache_options[output_lifespan]"]' => ['value' => 'custom'],
        ],
      ],
    ];
  }

  public function validateOptionsForm(&$form, FormStateInterface $form_state) {
    $custom_fields = ['output_lifespan', 'results_lifespan'];
    foreach ($custom_fields as $field) {
      $cache_options = $form_state->getValue('cache_options');
      if ($cache_options[$field] == 'custom' && !is_numeric($cache_options[$field . '_custom'])) {
        $form_state->setError($form[$field . '_custom'], $this->t('Custom time values must be numeric.'));
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
      $cutoff = $this->time->getRequestTime() - $lifespan;
      return $cutoff;
    }
    else {
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function cacheSetMaxAge($type) {
    $lifespan = $this->getLifespan($type);
    if ($lifespan) {
      return $lifespan;
    }
    else {
      return Cache::PERMANENT;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultCacheMaxAge() {
    // The max age, unless overridden by some other piece of the rendered code
    // is determined by the output time setting.
    return (int) $this->cacheSetMaxAge('output');
  }

}
