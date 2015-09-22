<?php

/**
 * @file
 * Contains \Drupal\system\Form\PerformanceForm.
 */

namespace Drupal\system\Form;

use Drupal\Core\Asset\AssetCollectionOptimizerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure performance settings for this site.
 */
class PerformanceForm extends ConfigFormBase {

  /**
   * The render cache bin.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $renderCache;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
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
   * Constructs a PerformanceForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Cache\CacheBackendInterface $render_cache
   * @param \Drupal\Core\Datetime\DateFormatter $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Asset\AssetCollectionOptimizerInterface $css_collection_optimizer
   *   The CSS asset collection optimizer service.
   * @param \Drupal\Core\Asset\AssetCollectionOptimizerInterface $js_collection_optimizer
   *   The JavaScript asset collection optimizer service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, CacheBackendInterface $render_cache, DateFormatter $date_formatter, AssetCollectionOptimizerInterface $css_collection_optimizer, AssetCollectionOptimizerInterface $js_collection_optimizer) {
    parent::__construct($config_factory);

    $this->renderCache = $render_cache;
    $this->dateFormatter = $date_formatter;
    $this->cssCollectionOptimizer = $css_collection_optimizer;
    $this->jsCollectionOptimizer = $js_collection_optimizer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('cache.render'),
      $container->get('date.formatter'),
      $container->get('asset.css.collection_optimizer'),
      $container->get('asset.js.collection_optimizer')
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

    $form['clear_cache'] = array(
      '#type' => 'details',
      '#title' => t('Clear cache'),
      '#open' => TRUE,
    );

    $form['clear_cache']['clear'] = array(
      '#type' => 'submit',
      '#value' => t('Clear all caches'),
      '#submit' => array('::submitCacheClear'),
    );

    $form['caching'] = array(
      '#type' => 'details',
      '#title' => t('Caching'),
      '#open' => TRUE,
      '#description' => $this->t('Note: Drupal provides an internal page cache module that is recommended for small to medium-sized websites.'),
    );
    // Identical options to the ones for block caching.
    // @see \Drupal\Core\Block\BlockBase::buildConfigurationForm()
    $period = array(0, 60, 180, 300, 600, 900, 1800, 2700, 3600, 10800, 21600, 32400, 43200, 86400);
    $period = array_map(array($this->dateFormatter, 'formatInterval'), array_combine($period, $period));
    $period[0] = '<' . t('no caching') . '>';
    $form['caching']['page_cache_maximum_age'] = array(
      '#type' => 'select',
      '#title' => t('Page cache maximum age'),
      '#default_value' => $config->get('cache.page.max_age'),
      '#options' => $period,
      '#description' => t('The maximum time a page can be cached by browsers and proxies. This is used as the value for max-age in Cache-Control headers.'),
    );

    $directory = 'public://';
    $is_writable = is_dir($directory) && is_writable($directory);
    $disabled = !$is_writable;
    $disabled_message = '';
    if (!$is_writable) {
      $disabled_message = ' ' . t('<strong class="error">Set up the <a href=":file-system">public files directory</a> to make these optimizations available.</strong>', array(':file-system' => $this->url('system.file_system_settings')));
    }

    $form['bandwidth_optimization'] = array(
      '#type' => 'details',
      '#title' => t('Bandwidth optimization'),
      '#open' => TRUE,
      '#description' => t('External resources can be optimized automatically, which can reduce both the size and number of requests made to your website.') . $disabled_message,
    );

    $form['bandwidth_optimization']['preprocess_css'] = array(
      '#type' => 'checkbox',
      '#title' => t('Aggregate CSS files'),
      '#default_value' => $config->get('css.preprocess'),
      '#disabled' => $disabled,
    );
    $form['bandwidth_optimization']['preprocess_js'] = array(
      '#type' => 'checkbox',
      '#title' => t('Aggregate JavaScript files'),
      '#default_value' => $config->get('js.preprocess'),
      '#disabled' => $disabled,
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->cssCollectionOptimizer->deleteAll();
    $this->jsCollectionOptimizer->deleteAll();
    // This form allows page compression settings to be changed, which can
    // invalidate cached pages in the render cache, so it needs to be cleared on
    // form submit.
    $this->renderCache->deleteAll();

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
    drupal_set_message(t('Caches cleared.'));
  }

}
