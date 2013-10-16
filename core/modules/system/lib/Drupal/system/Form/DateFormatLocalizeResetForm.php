<?php

/**
 * @file
 * Contains \Drupal\system\Form\DateFormatLocalizeResetForm.
 */

namespace Drupal\system\Form;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Config\ConfigFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds a form for enabling a module.
 */
class DateFormatLocalizeResetForm extends ConfirmFormBase implements ContainerInjectionInterface {

  /**
   * The language to be reset.
   *
   * @var \Drupal\Core\Language\Language;
   */
  protected $language;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Constructs a DateFormatLocalizeResetForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactory $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'system_date_format_localize_reset_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to reset the date formats for %language to the global defaults?', array(
      '%language' => $this->language->name,
    ));
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Reset');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelRoute() {
    return array(
      'route_name' => 'system.date_format_language_overview',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return t('Resetting will remove all localized date formats for this language. This action cannot be undone.');
  }

  /**
   * {@inheritdoc}
   *
   * @param string $langcode
   *   The language code.
   *
   */
  public function buildForm(array $form, array &$form_state, $langcode = NULL) {
    $this->language = language_load($langcode);

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    foreach (config_get_storage_names_with_prefix('locale.config.' . $this->language->id . '.system.date_format.') as $config_id) {
      $this->configFactory->get($config_id)->delete();
    }
    entity_render_cache_clear();

    $form_state['redirect'] = 'admin/config/regional/date-time/locale';
  }

}
