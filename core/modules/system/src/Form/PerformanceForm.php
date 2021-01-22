<?php

namespace Drupal\system\Form;

use Drupal\Core\Asset\AssetCollectionOptimizerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure performance settings for this site.
 *
 * @internal
 */
class PerformanceForm extends ConfigFormBase {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The CSS asset collection optimizer service.
   *
   * @var \Drupal\Core\Asset\AssetCollectionOptimizerInterface
   */
  protected $cssCollectionOptimizer;

  /**
   * The JavaScript asset collection optimizer service.
   *
   * @var \Drupal\Core\Asset\AssetCollectionOptimizerInterface
   */
  protected $jsCollectionOptimizer;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a PerformanceForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Asset\AssetCollectionOptimizerInterface $css_collection_optimizer
   *   The CSS asset collection optimizer service.
   * @param \Drupal\Core\Asset\AssetCollectionOptimizerInterface $js_collection_optimizer
   *   The JavaScript asset collection optimizer service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(ConfigFactoryInterface $config_factory, DateFormatterInterface $date_formatter, AssetCollectionOptimizerInterface $css_collection_optimizer, AssetCollectionOptimizerInterface $js_collection_optimizer, ModuleHandlerInterface $module_handler) {
    parent::__construct($config_factory);

    $this->dateFormatter = $date_formatter;
    $this->cssCollectionOptimizer = $css_collection_optimizer;
    $this->jsCollectionOptimizer = $js_collection_optimizer;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('date.formatter'),
      $container->get('asset.css.collection_optimizer'),
      $container->get('asset.js.collection_optimizer'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'system_performance_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['system.performance'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attached']['library'][] = 'system/drupal.system';

    $config = $this->config('system.performance');

    $form['clear_cache'] = [
      '#type' => 'details',
      '#title' => $this->t('Clear cache'),
      '#open' => TRUE,
    ];

    $form['clear_cache']['clear'] = [
      '#type' => 'submit',
      '#value' => $this->t('Clear all caches'),
      '#submit' => ['::submitCacheClear'],
    ];

    $form['caching'] = [
      '#type' => 'details',
      '#title' => $this->t('Caching'),
      '#open' => TRUE,
    ];
    // Identical options to the ones for block caching.
    // @see \Drupal\Core\Block\BlockBase::buildConfigurationForm()
    $period = [0, 60, 180, 300, 600, 900, 1800, 2700, 3600, 10800, 21600, 32400, 43200, 86400];
    $period = array_map([$this->dateFormatter, 'formatInterval'], array_combine($period, $period));
    $period[0] = '<' . $this->t('no caching') . '>';
    $form['caching']['page_cache_maximum_age'] = [
      '#type' => 'select',
      '#title' => $this->t('Browser and proxy cache maximum age'),
      '#default_value' => $config->get('cache.page.max_age'),
      '#options' => $period,
      '#description' => $this->t('This is used as the value for max-age in Cache-Control headers.'),
    ];
    $form['caching']['internal_page_cache'] = [
      '#markup' => $this->t('Drupal provides an <a href=":module_enable">Internal Page Cache module</a> that is recommended for small to medium-sized websites.', [':module_enable' => Url::fromRoute('system.modules_list')->toString()]),
      '#access' => !$this->moduleHandler->moduleExists('page_cache'),
    ];

    $directory = 'public://';
    $is_writable = is_dir($directory) && is_writable($directory);
    $disabled = !$is_writable;
    $disabled_message = '';
    if (!$is_writable) {
      $disabled_message = ' ' . $this->t('<strong class="error">Set up the <a href=":file-system">public files directory</a> to make these optimizations available.</strong>', [':file-system' => Url::fromRoute('system.file_system_settings')->toString()]);
    }

    $form['bandwidth_optimization'] = [
      '#type' => 'details',
      '#title' => $this->t('Bandwidth optimization'),
      '#open' => TRUE,
      '#description' => $this->t('External resources can be optimized automatically, which can reduce both the size and number of requests made to your website.') . $disabled_message,
    ];

    $form['bandwidth_optimization']['preprocess_css'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Aggregate CSS files'),
      '#default_value' => $config->get('css.preprocess'),
      '#disabled' => $disabled,
    ];
    $form['bandwidth_optimization']['preprocess_js'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Aggregate JavaScript files'),
      '#default_value' => $config->get('js.preprocess'),
      '#disabled' => $disabled,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->cssCollectionOptimizer->deleteAll();
    $this->jsCollectionOptimizer->deleteAll();

    $this->config('system.performance')
      ->set('cache.page.max_age', $form_state->getValue('page_cache_maximum_age'))
      ->set('css.preprocess', $form_state->getValue('preprocess_css'))
      ->set('js.preprocess', $form_state->getValue('preprocess_js'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Clears the caches.
   */
  public function submitCacheClear(array &$form, FormStateInterface $form_state) {
    drupal_flush_all_caches();
    $this->messenger()->addStatus($this->t('Caches cleared.'));
  }

}
