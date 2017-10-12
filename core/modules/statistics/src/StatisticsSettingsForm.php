<?php

namespace Drupal\statistics;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure statistics settings for this site.
 *
 * @internal
 */
class StatisticsSettingsForm extends ConfigFormBase {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a \Drupal\statistics\StatisticsSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler) {
    parent::__construct($config_factory);

    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'statistics_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['statistics.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('statistics.settings');

    // Content counter settings.
    $form['content'] = [
      '#type' => 'details',
      '#title' => t('Content viewing counter settings'),
      '#open' => TRUE,
    ];
    $form['content']['statistics_count_content_views'] = [
      '#type' => 'checkbox',
      '#title' => t('Count content views'),
      '#default_value' => $config->get('count_content_views'),
      '#description' => t('Increment a counter each time content is viewed.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('statistics.settings')
      ->set('count_content_views', $form_state->getValue('statistics_count_content_views'))
      ->save();

    // The popular statistics block is dependent on these settings, so clear the
    // block plugin definitions cache.
    if ($this->moduleHandler->moduleExists('block')) {
      \Drupal::service('plugin.manager.block')->clearCachedDefinitions();
    }

    parent::submitForm($form, $form_state);
  }

}
