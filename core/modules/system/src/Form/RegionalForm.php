<?php

namespace Drupal\system\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Locale\CountryManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure regional settings for this site.
 *
 * @internal
 */
class RegionalForm extends ConfigFormBase {

  /**
   * The country manager.
   *
   * @var \Drupal\Core\Locale\CountryManagerInterface
   */
  protected $countryManager;

  /**
   * Constructs a RegionalForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Locale\CountryManagerInterface $country_manager
   *   The country manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, CountryManagerInterface $country_manager) {
    parent::__construct($config_factory);
    $this->countryManager = $country_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('country_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'system_regional_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['system.date'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $countries = $this->countryManager->getList();
    $system_date = $this->config('system.date');

    // Date settings:
    $zones = system_time_zones(NULL, TRUE);

    $form['locale'] = [
      '#type' => 'details',
      '#title' => t('Locale'),
      '#open' => TRUE,
    ];

    $form['locale']['site_default_country'] = [
      '#type' => 'select',
      '#title' => t('Default country'),
      '#empty_value' => '',
      '#default_value' => $system_date->get('country.default'),
      '#options' => $countries,
      '#attributes' => ['class' => ['country-detect']],
    ];

    $form['locale']['date_first_day'] = [
      '#type' => 'select',
      '#title' => t('First day of week'),
      '#default_value' => $system_date->get('first_day'),
      '#options' => [0 => t('Sunday'), 1 => t('Monday'), 2 => t('Tuesday'), 3 => t('Wednesday'), 4 => t('Thursday'), 5 => t('Friday'), 6 => t('Saturday')],
    ];

    $form['timezone'] = [
      '#type' => 'details',
      '#title' => t('Time zones'),
      '#open' => TRUE,
    ];

    $form['timezone']['date_default_timezone'] = [
      '#type' => 'select',
      '#title' => t('Default time zone'),
      '#default_value' => $system_date->get('timezone.default') ?: date_default_timezone_get(),
      '#options' => $zones,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('system.date')
      ->set('country.default', $form_state->getValue('site_default_country'))
      ->set('first_day', $form_state->getValue('date_first_day'))
      ->set('timezone.default', $form_state->getValue('date_default_timezone'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
