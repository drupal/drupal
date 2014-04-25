<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\ContentEntityForm.
 */

namespace Drupal\Core\Entity;

use Drupal\Core\Entity\Display\EntityFormDisplayInterface;
use Drupal\entity\Entity\EntityFormDisplay;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Entity form variant for content entity types.
 *
 * @see \Drupal\Core\ContentEntityBase
 */
class ContentEntityForm extends EntityForm implements ContentEntityFormInterface {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a ContentEntityForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, array &$form_state) {
    $form = parent::form($form, $form_state);
    $this->getFormDisplay($form_state)->buildForm($this->entity, $form, $form_state);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validate(array $form, array &$form_state) {
    $this->updateFormLangcode($form_state);
    $entity = $this->buildEntity($form, $form_state);
    $this->getFormDisplay($form_state)->validateFormValues($entity, $form, $form_state);

    // @todo Remove this.
    // Execute legacy global validation handlers.
    unset($form_state['validate_handlers']);
    form_execute_handlers('validate', $form, $form_state);
  }

  /**
   * Initialize the form state and the entity before the first form build.
   */
  protected function init(array &$form_state) {
    // Ensure we act on the translation object corresponding to the current form
    // language.
    $langcode = $this->getFormLangcode($form_state);
    $this->entity = $this->entity->getTranslation($langcode);

    $form_display = EntityFormDisplay::collectRenderDisplay($this->entity, $this->getOperation());
    $this->setFormDisplay($form_display, $form_state);

    parent::init($form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormLangcode(array &$form_state) {
    if (empty($form_state['langcode'])) {
      // Imply a 'view' operation to ensure users edit entities in the same
      // language they are displayed. This allows to keep contextual editing
      // working also for multilingual entities.
      $form_state['langcode'] = $this->entityManager->getTranslationFromContext($this->entity)->language()->id;
    }
    return $form_state['langcode'];
  }

  /**
   * {@inheritdoc}
   */
  public function isDefaultFormLangcode(array $form_state) {
    return $this->getFormLangcode($form_state) == $this->entity->getUntranslated()->language()->id;
  }

  /**
   * {@inheritdoc}
   */
  protected function copyFormValuesToEntity(EntityInterface $entity, array $form, array &$form_state) {
    // First, extract values from widgets.
    $extracted = $this->getFormDisplay($form_state)->extractFormValues($entity, $form, $form_state);

    // Then extract the values of fields that are not rendered through widgets,
    // by simply copying from top-level form values. This leaves the fields
    // that are not being edited within this form untouched.
    foreach ($form_state['values'] as $name => $values) {
      if ($entity->hasField($name) && !isset($extracted[$name])) {
        $entity->set($name, $values);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFormDisplay(array $form_state) {
    return isset($form_state['form_display']) ? $form_state['form_display'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setFormDisplay(EntityFormDisplayInterface $form_display, array &$form_state) {
    $form_state['form_display'] = $form_display;
    return $this;
  }

}
