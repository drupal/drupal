<?php

/**
 * @file
 * Contains \Drupal\system\Form\RegionalForm.
 */

namespace Drupal\system\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Locale\CountryManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure regional settings for this site.
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
  public function buildForm(array $form, array &$form_state) {
    $countries = $this->countryManager->getList();
    $system_date = $this->config('system.date');

    // Date settings:
    $zones = system_time_zones();

    $form['locale'] = array(
      '#type' => 'details',
      '#title' => t('Locale'),
      '#open' => TRUE,
    );

    $form['locale']['site_default_country'] = array(
      '#type' => 'select',
      '#title' => t('Default country'),
      '#empty_value' => '',
      '#default_value' => $system_date->get('country.default'),
      '#options' => $countries,
      '#attributes' => array('class' => array('country-detect')),
    );

    $form['locale']['date_first_day'] = array(
      '#type' => 'select',
      '#title' => t('First day of week'),
      '#default_value' => $system_date->get('first_day'),
      '#options' => array(0 => t('Sunday'), 1 => t('Monday'), 2 => t('Tuesday'), 3 => t('Wednesday'), 4 => t('Thursday'), 5 => t('Friday'), 6 => t('Saturday')),
    );

    $form['timezone'] = array(
      '#type' => 'details',
      '#title' => t('Time zones'),
      '#open' => TRUE,
    );

    $form['timezone']['date_default_timezone'] = array(
      '#type' => 'select',
      '#title' => t('Default time zone'),
      '#default_value' => $system_date->get('timezone.default') ?: date_default_timezone_get(),
      '#options' => $zones,
    );

    $configurable_timezones = $system_date->get('timezone.user.configurable');
    $form['timezone']['configurable_timezones'] = array(
      '#type' => 'checkbox',
      '#title' => t('Users may set their own time zone.'),
      '#default_value' => $configurable_timezones,
    );

    $form['timezone']['configurable_timezones_wrapper'] =  array(
      '#type' => 'container',
      '#states' => array(
        // Hide the user configured timezone settings when users are forced to use
        // the default setting.
        'invisible' => array(
          'input[name="configurable_timezones"]' => array('checked' => FALSE),
        ),
      ),
    );
    $form['timezone']['configurable_timezones_wrapper']['empty_timezone_message'] = array(
      '#type' => 'checkbox',
      '#title' => t('Remind users at login if their time zone is not set.'),
      '#default_value' => $system_date->get('timezone.user.warn'),
      '#description' => t('Only applied if users may set their own time zone.')
    );

    $form['timezone']['configurable_timezones_wrapper']['user_default_timezone'] = array(
      '#type' => 'radios',
      '#title' => t('Time zone for new users'),
      '#default_value' => $system_date->get('timezone.user.default'),
      '#options' => array(
        DRUPAL_USER_TIMEZONE_DEFAULT => t('Default time zone.'),
        DRUPAL_USER_TIMEZONE_EMPTY   => t('Empty time zone.'),
        DRUPAL_USER_TIMEZONE_SELECT  => t('Users may set their own time zone at registration.'),
      ),
      '#description' => t('Only applied if users may set their own time zone.')
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $this->config('system.date')
      ->set('country.default', $form_state['values']['site_default_country'])
      ->set('first_day', $form_state['values']['date_first_day'])
      ->set('timezone.default', $form_state['values']['date_default_timezone'])
      ->set('timezone.user.configurable', $form_state['values']['configurable_timezones'])
      ->set('timezone.user.warn', $form_state['values']['empty_timezone_message'])
      ->set('timezone.user.default', $form_state['values']['user_default_timezone'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
