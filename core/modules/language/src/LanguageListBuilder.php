<?php

/**
 * @file
 * Contains \Drupal\language\Form\LanguageListBuilder.
 */

namespace Drupal\language;

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
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('language_manager')
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
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, LanguageManagerInterface $language_manager) {
    parent::__construct($entity_type, $storage);
    $this->languageManager = $language_manager;
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
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    // Mark the right language as default in the form.
    $default = \Drupal::languageManager()->getDefaultLanguage();
    foreach (Element::children($form[$this->entitiesKey]) as $key) {
      if ($key == $default->getId()) {
        $form[$this->entitiesKey][$key]['default']['#default_value'] = $default->getId();
      }
    }

    $form[$this->entitiesKey]['#languages'] = $this->entities;
    $form['actions']['submit']['#value'] = t('Save configuration');
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    // Save the default language.
    foreach ($form_state->getValue($this->entitiesKey) as $id => $value) {
      if (isset($this->entities[$id]) && ($id == $form_state->getValue('site_default_language'))) {
        \Drupal::configFactory()->getEditable('system.site')->set('langcode', $form_state->getValue('site_default_language'))->save();
      }
    }

    $this->languageManager->reset();
    if ($this->languageManager instanceof ConfigurableLanguageManagerInterface) {
      $this->languageManager->updateLockedLanguageWeights();
    }

    drupal_set_message(t('Configuration saved.'));
    // Force the redirection to the page with the language we have just
    // selected as default.
    $form_state->setRedirect('entity.configurable_language.collection', array(), array('language' => $this->entities[$form_state->getValue('site_default_language')]));
  }

}
