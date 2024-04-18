<?php

namespace Drupal\system\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Datetime\TimeZoneFormHelper;
use Drupal\Core\Form\ConfigTarget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\RedundantEditableConfigNamesTrait;
use Drupal\Core\Locale\CountryManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure regional settings for this site.
 *
 * @internal
 */
class RegionalForm extends ConfigFormBase {
  use RedundantEditableConfigNamesTrait;

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
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typedConfigManager
   *   The typed config manager.
   * @param \Drupal\Core\Locale\CountryManagerInterface $country_manager
   *   The country manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, TypedConfigManagerInterface $typedConfigManager, CountryManagerInterface $country_manager) {
    parent::__construct($config_factory, $typedConfigManager);
    $this->countryManager = $country_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
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
  public function buildForm(array $form, FormStateInterface $form_state) {
    $countries = $this->countryManager->getList();

    // Date settings:
    $zones = TimeZoneFormHelper::getOptionsListByRegion();

    $form['locale'] = [
      '#type' => 'details',
      '#title' => $this->t('Locale'),
      '#open' => TRUE,
    ];

    $form['locale']['site_default_country'] = [
      '#type' => 'select',
      '#title' => $this->t('Default country'),
      '#empty_value' => '',
      '#config_target' => new ConfigTarget(
        'system.date',
        'country.default',
        toConfig: fn(?string $value) => $value ?: NULL
      ),
      '#options' => $countries,
      '#attributes' => ['class' => ['country-detect']],
    ];

    $form['locale']['date_first_day'] = [
      '#type' => 'select',
      '#title' => $this->t('First day of week'),
      '#config_target' => 'system.date:first_day',
      '#options' => [0 => $this->t('Sunday'), 1 => $this->t('Monday'), 2 => $this->t('Tuesday'), 3 => $this->t('Wednesday'), 4 => $this->t('Thursday'), 5 => $this->t('Friday'), 6 => $this->t('Saturday')],
    ];

    $form['timezone'] = [
      '#type' => 'details',
      '#title' => $this->t('Time zones'),
      '#open' => TRUE,
    ];

    $form['timezone']['date_default_timezone'] = [
      '#type' => 'select',
      '#title' => $this->t('Default time zone'),
      '#config_target' => new ConfigTarget(
        'system.date',
        'timezone.default',
        static::class . '::loadDefaultTimeZone',
      ),
      '#options' => $zones,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Prepares the saved timezone.default property to be displayed in the form.
   *
   * @param string|null $value
   *   The value saved in config.
   *
   * @return string
   *   The value of the form element.
   */
  public static function loadDefaultTimeZone(?string $value): string {
    return $value ?: date_default_timezone_get();
  }

}
