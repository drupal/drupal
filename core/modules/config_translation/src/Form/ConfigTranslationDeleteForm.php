<?php

namespace Drupal\config_translation\Form;

use Drupal\config_translation\ConfigMapperManagerInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\language\ConfigurableLanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Builds a form to delete configuration translation.
 *
 * @internal
 */
class ConfigTranslationDeleteForm extends ConfirmFormBase {

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
   * @param \Drupal\language\ConfigurableLanguageManagerInterface $languageManager
   *   The language override configuration storage.
   * @param \Drupal\config_translation\ConfigMapperManagerInterface $configMapperManager
   *   The configuration mapper manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   */
  public function __construct(
    protected ConfigurableLanguageManagerInterface $languageManager,
    protected ConfigMapperManagerInterface $configMapperManager,
    protected ModuleHandlerInterface $moduleHandler,
  ) {
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
    return $this->t('Are you sure you want to delete the @language translation of %label?', [
      '%label' => $this->mapper->getTitle(),
      '@language' => $this->language->getName(),
    ]);
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
  public function getFormId() {
    return 'config_translation_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?RouteMatchInterface $route_match = NULL, $plugin_id = NULL, $langcode = NULL) {
    /** @var \Drupal\config_translation\ConfigMapperInterface $mapper */
    $mapper = $this->configMapperManager->createInstance($plugin_id);
    $mapper->populateFromRouteMatch($route_match);

    $language = $this->languageManager->getLanguage($langcode);
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
      $this->languageManager->getLanguageConfigOverride($this->language->getId(), $name)->delete();
    }

    // Flush all persistent caches.
    $this->moduleHandler->invokeAll('cache_flush');
    foreach (Cache::getBins() as $cache_backend) {
      $cache_backend->deleteAll();
    }

    $this->messenger()
      ->addStatus($this->t('@language translation of %label was deleted', [
        '%label' => $this->mapper->getTitle(),
        '@language' => $this->language->getName(),
      ]));

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
