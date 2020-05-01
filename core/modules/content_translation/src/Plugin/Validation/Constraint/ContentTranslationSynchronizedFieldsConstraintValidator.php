<?php

namespace Drupal\content_translation\Plugin\Validation\Constraint;

use Drupal\content_translation\ContentTranslationManagerInterface;
use Drupal\content_translation\FieldTranslationSynchronizerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Checks that synchronized fields are handled correctly in pending revisions.
 *
 * As for untranslatable fields, two modes are supported:
 * - When changes to untranslatable fields are configured to affect all revision
 *   translations, synchronized field properties can be changed only in default
 *   revisions.
 * - When changes to untranslatable fields affect are configured to affect only
 *   the revision's default translation, synchronized field properties can be
 *   changed only when editing the default translation. This may lead to
 *   temporarily desynchronized values, when saving a pending revision for the
 *   default translation that changes a synchronized property. These are
 *   actually synchronized when saving changes to the default translation as a
 *   new default revision.
 *
 * @see \Drupal\content_translation\Plugin\Validation\Constraint\ContentTranslationSynchronizedFieldsConstraint
 * @see \Drupal\Core\Entity\Plugin\Validation\Constraint\EntityUntranslatableFieldsConstraintValidator
 *
 * @internal
 */
class ContentTranslationSynchronizedFieldsConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The content translation manager.
   *
   * @var \Drupal\content_translation\ContentTranslationManagerInterface
   */
  protected $contentTranslationManager;

  /**
   * The field translation synchronizer.
   *
   * @var \Drupal\content_translation\FieldTranslationSynchronizerInterface
   */
  protected $synchronizer;

  /**
   * ContentTranslationSynchronizedFieldsConstraintValidator constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\content_translation\ContentTranslationManagerInterface $content_translation_manager
   *   The content translation manager.
   * @param \Drupal\content_translation\FieldTranslationSynchronizerInterface $synchronizer
   *   The field translation synchronizer.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ContentTranslationManagerInterface $content_translation_manager, FieldTranslationSynchronizerInterface $synchronizer) {
    $this->entityTypeManager = $entity_type_manager;
    $this->contentTranslationManager = $content_translation_manager;
    $this->synchronizer = $synchronizer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('content_translation.manager'),
      $container->get('content_translation.synchronizer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    /** @var \Drupal\content_translation\Plugin\Validation\Constraint\ContentTranslationSynchronizedFieldsConstraint $constraint */
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $value;
    if ($entity->isNew() || !$entity->getEntityType()->isRevisionable()) {
      return;
    }
    // When changes to untranslatable fields are configured to affect all
    // revision translations, we always allow changes in default revisions.
    if ($entity->isDefaultRevision() && !$entity->isDefaultTranslationAffectedOnly()) {
      return;
    }
    $entity_type_id = $entity->getEntityTypeId();
    if (!$this->contentTranslationManager->isEnabled($entity_type_id, $entity->bundle())) {
      return;
    }
    $synchronized_properties = $this->getSynchronizedPropertiesByField($entity->getFieldDefinitions());
    if (!$synchronized_properties) {
      return;
    }

    /** @var \Drupal\Core\Entity\ContentEntityInterface $original */
    $original = $this->getOriginalEntity($entity);
    $original_translation = $this->getOriginalTranslation($entity, $original);
    if ($this->hasSynchronizedPropertyChanges($entity, $original_translation, $synchronized_properties)) {
      if ($entity->isDefaultTranslationAffectedOnly()) {
        foreach ($entity->getTranslationLanguages(FALSE) as $langcode => $language) {
          if ($entity->getTranslation($langcode)->hasTranslationChanges()) {
            $this->context->addViolation($constraint->defaultTranslationMessage);
            break;
          }
        }
      }
      else {
        $this->context->addViolation($constraint->defaultRevisionMessage);
      }
    }
  }

  /**
   * Checks whether any synchronized property has changes.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity being validated.
   * @param \Drupal\Core\Entity\ContentEntityInterface $original
   *   The original unchanged entity.
   * @param string[][] $synchronized_properties
   *   An associative array of arrays of synchronized field properties keyed by
   *   field name.
   *
   * @return bool
   *   TRUE if changes in synchronized properties were detected, FALSE
   *   otherwise.
   */
  protected function hasSynchronizedPropertyChanges(ContentEntityInterface $entity, ContentEntityInterface $original, array $synchronized_properties) {
    foreach ($synchronized_properties as $field_name => $properties) {
      foreach ($properties as $property) {
        $items = $entity->get($field_name)->getValue();
        $original_items = $original->get($field_name)->getValue();
        if (count($items) !== count($original_items)) {
          return TRUE;
        }
        foreach ($items as $delta => $item) {
          // @todo This loose comparison is not fully reliable. Revisit this
          //   after https://www.drupal.org/project/drupal/issues/2941092.
          if ($items[$delta][$property] != $original_items[$delta][$property]) {
            return TRUE;
          }
        }
      }
    }
    return FALSE;
  }

  /**
   * Returns the original unchanged entity to be used to detect changes.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity being changed.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   *   The unchanged entity.
   */
  protected function getOriginalEntity(ContentEntityInterface $entity) {
    if (!isset($entity->original)) {
      $storage = $this->entityTypeManager->getStorage($entity->getEntityTypeId());
      $original = $entity->isDefaultRevision() ? $storage->loadUnchanged($entity->id()) : $storage->loadRevision($entity->getLoadedRevisionId());
    }
    else {
      $original = $entity->original;
    }
    return $original;
  }

  /**
   * Returns the original translation.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity being validated.
   * @param \Drupal\Core\Entity\ContentEntityInterface $original
   *   The original entity.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   *   The original entity translation object.
   */
  protected function getOriginalTranslation(ContentEntityInterface $entity, ContentEntityInterface $original) {
    // If the language of the default translation is changing, the original
    // translation will be the same as the original entity, but they won't
    // necessarily have the same langcode.
    if ($entity->isDefaultTranslation() && $original->language()->getId() !== $entity->language()->getId()) {
      return $original;
    }
    $langcode = $entity->language()->getId();
    if ($original->hasTranslation($langcode)) {
      $original_langcode = $langcode;
    }
    else {
      $metadata = $this->contentTranslationManager->getTranslationMetadata($entity);
      $original_langcode = $metadata->getSource();
    }
    return $original->getTranslation($original_langcode);
  }

  /**
   * Returns the synchronized properties for every specified field.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface[] $field_definitions
   *   An array of field definitions.
   *
   * @return string[][]
   *   An associative array of arrays of field property names keyed by field
   *   name.
   */
  public function getSynchronizedPropertiesByField(array $field_definitions) {
    $synchronizer = $this->synchronizer;
    $synchronized_properties = array_filter(array_map(
      function (FieldDefinitionInterface $field_definition) use ($synchronizer) {
        return $synchronizer->getFieldSynchronizedProperties($field_definition);
      },
      $field_definitions
    ));
    return $synchronized_properties;
  }

}
