<?php

/**
 * @file
 * Contains \Drupal\system\Form\PerformanceForm.
 */

namespace Drupal\system\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure performance settings for this site.
 */
class PerformanceForm extends ConfigFormBase {

  /**
   * The page cache object.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $pageCache;

  /**
   * Constructs a PerformanceForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Cache\CacheBackendInterface $page_cache
   */
  public function __construct(ConfigFactoryInterface $config_factory, CacheBackendInterface $page_cache) {
    parent::__construct($config_factory);

    $this->pageCache = $page_cache;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('cache.page')
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
  public function buildForm(array $form, array &$form_state) {
    $form['#attached']['library'][] = 'system/drupal.system';

    $config = $this->configFactory->get('system.performance');

    $form['clear_cache'] = array(
      '#type' => 'details',
      '#title' => t('Clear cache'),
      '#open' => TRUE,
    );

    $form['clear_cache']['clear'] = array(
      '#type' => 'submit',
      '#value' => t('Clear all caches'),
      '#submit' => array(array($this, 'submitCacheClear')),
    );

    $form['caching'] = array(
      '#type' => 'details',
      '#title' => t('Caching'),
      '#open' => TRUE,
    );

    $period = array(0, 60, 180, 300, 600, 900, 1800, 2700, 3600, 10800, 21600, 32400, 43200, 86400);
    $period = array_map('format_interval', array_combine($period, $period));
    $period[0] = '<' . t('none') . '>';
    $form['caching']['page_cache_maximum_age'] = array(
      '#type' => 'select',
      '#title' => t('Page cache maximum age'),
      '#default_value' => $config->get('cache.page.max_age'),
      '#options' => $period,
      '#description' => t('The maximum time a page can be cached. This is used as the value for max-age in Cache-Control headers.'),
    );

    $form['caching']['cache'] = array(
      '#type' => 'checkbox',
      '#title' => t('Use internal page cache'),
      '#description' => t("If a reverse proxy cache isn't available, use Drupal's internal cache system to store cached pages."),
      '#default_value' => $config->get('cache.page.use_internal'),
    );

    $directory = 'public://';
    $is_writable = is_dir($directory) && is_writable($directory);
    $disabled = !$is_writable;
    $disabled_message = '';
    if (!$is_writable) {
      $disabled_message = ' ' . t('<strong class="error">Set up the <a href="!file-system">public files directory</a> to make these optimizations available.</strong>', array('!file-system' => url('admin/config/media/file-system')));
    }

    $form['bandwidth_optimization'] = array(
      '#type' => 'details',
      '#title' => t('Bandwidth optimization'),
      '#open' => TRUE,
      '#description' => t('External resources can be optimized automatically, which can reduce both the size and number of requests made to your website.') . $disabled_message,
    );

    $form['bandwidth_optimization']['page_compression'] = array(
      '#type' => 'checkbox',
      '#title' => t('Compress cached pages.'),
      '#default_value' => $config->get('response.gzip'),
      '#states' => array(
        'visible' => array(
          'input[name="cache"]' => array('checked' => TRUE),
        ),
      ),
    );
    $form['bandwidth_optimization']['preprocess_css'] = array(
      '#type' => 'checkbox',
      '#title' => t('Aggregate CSS files.'),
      '#default_value' => $config->get('css.preprocess'),
      '#disabled' => $disabled,
    );
    $form['bandwidth_optimization']['preprocess_js'] = array(
      '#type' => 'checkbox',
      '#title' => t('Aggregate JavaScript files.'),
      '#default_value' => $config->get('js.preprocess'),
      '#disabled' => $disabled,
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    drupal_clear_css_cache();
    drupal_clear_js_cache();
    // This form allows page compression settings to be changed, which can
    // invalidate the page cache, so it needs to be cleared on form submit.
    $this->pageCache->deleteAll();

    $this->configFactory->get('system.performance')
      ->set('cache.page.use_internal', $form_state['values']['cache'])
      ->set('cache.page.max_age', $form_state['values']['page_cache_maximum_age'])
      ->set('response.gzip', $form_state['values']['page_compression'])
      ->set('css.preprocess', $form_state['values']['preprocess_css'])
      ->set('js.preprocess', $form_state['values']['preprocess_js'])
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Clears the caches.
   */
  public function submitCacheClear(array &$form, array &$form_state) {
    drupal_flush_all_caches();
    drupal_set_message(t('Caches cleared.'));
  }

}
