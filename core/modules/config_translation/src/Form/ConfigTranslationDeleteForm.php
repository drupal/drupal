<?php

/**
 * @file
 * Contains \Drupal\config_translation\Form\ConfigTranslationDeleteForm.
 */

namespace Drupal\config_translation\Form;

use Drupal\config_translation\ConfigMapperManagerInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\language\ConfigurableLanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Builds a form to delete configuration translation.
 */
class ConfigTranslationDeleteForm extends ConfirmFormBase {

  /**
   * The language manager.
   *
   * @var \Drupal\language\ConfigurableLanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The configuration mapper manager.
   *
   * @var \Drupal\config_translation\ConfigMapperManagerInterface
   */
  protected $configMapperManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The configuration translation to be deleted.
   *
   * @var \Drupal\config_translation\ConfigMapperInterface
   */
  protected $mapper;

  /**
   * The language of configuration translation.
   *
   * @var \Drupal\Core\Language\LanguageInterface
   */
  protected $language;

  /**
   * Constructs a ConfigTranslationDeleteForm.
   *
   * @param \Drupal\language\ConfigurableLanguageManagerInterface $language_manager
   *   The language override configuration storage.
   * @param \Drupal\config_translation\ConfigMapperManagerInterface $config_mapper_manager
   *   The configuration mapper manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(ConfigurableLanguageManagerInterface $language_manager, ConfigMapperManagerInterface $config_mapper_manager, ModuleHandlerInterface $module_handler) {
    $this->languageManager = $language_manager;
    $this->configMapperManager = $config_mapper_manager;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('language_manager'),
      $container->get('plugin.manager.config_translation.mapper'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the @language translation of %label?', array('%label' => $this->mapper->getTitle(), '@language' => $this->language->name));
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url($this->mapper->getOverviewRouteName(), $this->mapper->getOverviewRouteParameters());
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'config_translation_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL, $plugin_id = NULL, $langcode = NULL) {
    /** @var \Drupal\config_translation\ConfigMapperInterface $mapper */
    $mapper = $this->configMapperManager->createInstance($plugin_id);
    $mapper->populateFromRequest($request);

    $language = language_load($langcode);
    if (!$language) {
      throw new NotFoundHttpException();
    }

    $this->mapper = $mapper;
    $this->language = $language;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    foreach ($this->mapper->getConfigNames() as $name) {
      $this->languageManager->getLanguageConfigOverride($this->language->id, $name)->delete();
    }

    // Flush all persistent caches.
    $this->moduleHandler->invokeAll('cache_flush');
    foreach (Cache::getBins() as $service_id => $cache_backend) {
      $cache_backend->deleteAll();
    }

    drupal_set_message($this->t('@language translation of %label was deleted', array('%label' => $this->mapper->getTitle(), '@language' => $this->language->name)));

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
