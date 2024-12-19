<?php

namespace Drupal\Core\Entity\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangesDetectionTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the EntityChanged constraint.
 */
class EntityUntranslatableFieldsConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  use EntityChangesDetectionTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs an EntityUntranslatableFieldsConstraintValidator object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint): void {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    /** @var \Drupal\Core\Entity\Plugin\Validation\Constraint\EntityUntranslatableFieldsConstraint $constraint */

    // Untranslatable field restrictions apply only to revisions of multilingual
    // entities.
    if ($entity->isNew() || !$entity->isTranslatable() || !$entity->getEntityType()->isRevisionable()) {
      return;
    }
    if ($entity->isDefaultRevision() && !$entity->isDefaultTranslationAffectedOnly()) {
      return;
    }

    // To avoid unintentional reverts and data losses, we forbid changes to
    // untranslatable fields in pending revisions for multilingual entities. The
    // only case where changes in pending revisions are acceptable is when
    // untranslatable fields affect only the default translation, in which case
    // a pending revision contains only one affected translation. Even in this
    // case, multiple translations would be affected in a single revision, if we
    // allowed changes to untranslatable fields while editing non-default
    // translations, so that is forbidden too. For the same reason, when changes
    // to untranslatable fields affect all translations, we can only allow them
    // in default revisions.
    if ($this->hasUntranslatableFieldsChanges($entity)) {
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
   * Checks whether an entity has untranslatable field changes.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   A content entity object.
   *
   * @return bool
   *   TRUE if untranslatable fields have changes, FALSE otherwise.
   */
  protected function hasUntranslatableFieldsChanges(ContentEntityInterface $entity) {
    $skip_fields = $this->getFieldsToSkipFromTranslationChangesCheck($entity);
    /** @var \Drupal\Core\Entity\ContentEntityInterface $original */
    $original = $entity->getOriginal();
    if (!$original) {
      /** @var \Drupal\Core\Entity\RevisionableStorageInterface $storage */
      $storage = $this->entityTypeManager
        ->getStorage($entity->getEntityTypeId());
      $original = $storage->loadRevision($entity->getLoadedRevisionId());
    }

    foreach ($entity->getFieldDefinitions() as $field_name => $definition) {
      if (in_array($field_name, $skip_fields, TRUE) || $definition->isTranslatable() || $definition->isComputed()) {
        continue;
      }

      $items = $entity->get($field_name)->filterEmptyItems();
      $original_items = $original->get($field_name)->filterEmptyItems();
      if ($items->hasAffectingChanges($original_items, $entity->getUntranslated()->language()->getId())) {
        return TRUE;
      }
    }

    return FALSE;
  }

}
