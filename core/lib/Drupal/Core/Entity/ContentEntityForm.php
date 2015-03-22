<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\ContentEntityForm.
 */

namespace Drupal\Core\Entity;

use Drupal\Core\Entity\Display\EntityFormDisplayInterface;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Form\FormStateInterface;
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
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    // Content entity forms do not use the parent's #after_build callback
    // because they only need to rebuild the entity in the validation and the
    // submit handler because Field API uses its own #after_build callback for
    // its widgets.
    unset($form['#after_build']);

    $this->getFormDisplay($form_state)->buildForm($this->entity, $form, $form_state);
    // Allow modules to act before and after form language is updated.
    $form['#entity_builders']['update_form_langcode'] = [$this, 'updateFormLangcode'];
    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * Note that extending classes should not override this method to add entity
   * validation logic, but define further validation constraints using the
   * entity validation API and/or provide a new validation constraint if
   * necessary. This is the only way to ensure that the validation logic
   * is correctly applied independently of form submissions; e.g., for REST
   * requests.
   * For more information about entity validation, see
   * https://www.drupal.org/node/2015613.
   */
  public function validate(array $form, FormStateInterface $form_state) {
    $entity = $this->buildEntity($form, $form_state);
    $this->getFormDisplay($form_state)->validateFormValues($entity, $form, $form_state);

    // @todo Remove this.
    // Execute legacy global validation handlers.
    $form_state->setValidateHandlers([]);
    \Drupal::service('form_validator')->executeValidateHandlers($form, $form_state);
    return $entity;
  }

  /**
   * Initializes the form state and the entity before the first form build.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function init(FormStateInterface $form_state) {
    // Ensure we act on the translation object corresponding to the current form
    // language.
    $this->initFormLangcodes($form_state);
    $langcode = $this->getFormLangcode($form_state);
    $this->entity = $this->entity->getTranslation($langcode);

    $form_display = EntityFormDisplay::collectRenderDisplay($this->entity, $this->getOperation());
    $this->setFormDisplay($form_display, $form_state);

    parent::init($form_state);
  }

  /**
   * Initializes form language code values.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function initFormLangcodes(FormStateInterface $form_state) {
    // Store the entity default language to allow checking whether the form is
    // dealing with the original entity or a translation.
    if (!$form_state->has('entity_default_langcode')) {
      $form_state->set('entity_default_langcode', $this->entity->getUntranslated()->language()->getId());
    }
    // This value might have been explicitly populated to work with a particular
    // entity translation. If not we fall back to the most proper language based
    // on contextual information.
    if (!$form_state->has('langcode')) {
      // Imply a 'view' operation to ensure users edit entities in the same
      // language they are displayed. This allows to keep contextual editing
      // working also for multilingual entities.
      $form_state->set('langcode', $this->entityManager->getTranslationFromContext($this->entity)->language()->getId());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFormLangcode(FormStateInterface $form_state) {
    $this->initFormLangcodes($form_state);
    return $form_state->get('langcode');
  }

  /**
   * {@inheritdoc}
   */
  public function isDefaultFormLangcode(FormStateInterface $form_state) {
    $this->initFormLangcodes($form_state);
    return $form_state->get('langcode') == $form_state->get('entity_default_langcode');
  }

  /**
   * {@inheritdoc}
   */
  protected function copyFormValuesToEntity(EntityInterface $entity, array $form, FormStateInterface $form_state) {
    // First, extract values from widgets.
    $extracted = $this->getFormDisplay($form_state)->extractFormValues($entity, $form, $form_state);

    // Then extract the values of fields that are not rendered through widgets,
    // by simply copying from top-level form values. This leaves the fields
    // that are not being edited within this form untouched.
    foreach ($form_state->getValues() as $name => $values) {
      if ($entity->hasField($name) && !isset($extracted[$name])) {
        $entity->set($name, $values);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFormDisplay(FormStateInterface $form_state) {
    return $form_state->get('form_display');
  }

  /**
   * {@inheritdoc}
   */
  public function setFormDisplay(EntityFormDisplayInterface $form_display, FormStateInterface $form_state) {
    $form_state->set('form_display', $form_display);
    return $this;
  }

  /**
   * Updates the form language to reflect any change to the entity language.
   *
   * There are use cases for modules to act both before and after form language
   * being updated, thus the update is performed through an entity builder
   * callback, which allows to support both cases.
   *
   * @param string $entity_type_id
   *   The entity type identifier.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity updated with the submitted values.
   * @param array $form
   *   The complete form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @see \Drupal\Core\Entity\ContentEntityForm::form()
   */
  public function updateFormLangcode($entity_type_id, EntityInterface $entity, array $form, FormStateInterface $form_state) {
    // Update the form language as it might have changed.
    if ($this->isDefaultFormLangcode($form_state)) {
      $langcode = $entity->language()->getId();
      $form_state->set('langcode', $langcode);
    }
  }

}
