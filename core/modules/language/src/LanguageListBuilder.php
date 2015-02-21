<?php

/**
 * @file
 * Contains \Drupal\language\Form\LanguageListBuilder.
 */

namespace Drupal\language;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\Entity\DraggableListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Render\Element;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class to build a listing of language entities.
 *
 * @see \Drupal\language\Entity\ConfigurableLanguage
 */
class LanguageListBuilder extends DraggableListBuilder {

  /**
   * {@inheritdoc}
   */
  protected $entitiesKey = 'languages';

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('language_manager'),
      $container->get('config.factory')
    );
  }

  /**
   * Constructs a new EntityListController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage controller class.
   * @param \Drupal\Core\Language\LanguageManagerInterface
   *   The language manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, LanguageManagerInterface $language_manager, ConfigFactoryInterface $config_factory) {
    parent::__construct($entity_type, $storage);
    $this->languageManager = $language_manager;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    $entities = $this->storage->loadByProperties(array('locked' => FALSE));

    // Sort the entities using the entity class's sort() method.
    // See \Drupal\Core\Config\Entity\ConfigEntityBase::sort().
    uasort($entities, array($this->entityType->getClass(), 'sort'));
    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'language_admin_overview_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = array(
        'label' => t('Name'),
        'default' => t('Default'),
      ) + parent::buildHeader();
    return $header;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $this->getLabel($entity);
    $row['default'] = array(
      '#type' => 'radio',
      '#parents' => array('site_default_language'),
      '#title' => t('Set @title as default', array('@title' => $entity->label())),
      '#title_display' => 'invisible',
      '#return_value' => $entity->id(),
      '#id' => 'edit-site-default-language-' . $entity->id(),
    );
    // Mark the right language as default in the form.
    if ($entity->id() == $this->languageManager->getDefaultLanguage()->getId()) {
      $row['default']['#default_value'] = $entity->id();
    }
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $form[$this->entitiesKey]['#languages'] = $this->entities;
    $form['actions']['submit']['#value'] = t('Save configuration');
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!isset($this->entities[$form_state->getValue('site_default_language')])) {
      $form_state->setErrorByName('site_default_language', $this->t('Selected default language no longer exists.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    // Save the default language if changed.
    $new_id = $form_state->getValue('site_default_language');
    if ($new_id != $this->languageManager->getDefaultLanguage()->getId()) {
      $this->configFactory->getEditable('system.site')->set('langcode', $new_id)->save();
      $this->languageManager->reset();
    }

    if ($this->languageManager instanceof ConfigurableLanguageManagerInterface) {
      $this->languageManager->updateLockedLanguageWeights();
    }

    drupal_set_message(t('Configuration saved.'));
    // Force the redirection to the page with the language we have just
    // selected as default.
    $form_state->setRedirectUrl($this->entities[$new_id]->urlInfo('collection', array('language' => $this->entities[$new_id])));
  }

}
