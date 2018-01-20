<?php

namespace Drupal\Core\Entity\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangesDetectionTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\ChangedFieldItemList;
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
  public function validate($entity, Constraint $constraint) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */

    // Untranslatable field restrictions apply only to pending revisions of
    // multilingual entities.
    if ($entity->isNew() || $entity->isDefaultRevision() || !$entity->isTranslatable() || !$entity->getEntityType()->isRevisionable()) {
      return;
    }

    // To avoid unintentional reverts and data losses, we forbid changes to
    // untranslatable fields in pending revisions for multilingual entities. The
    // only case where changes in pending revisions are acceptable is when
    // untranslatable fields affect only the default translation, in which case
    // a pending revision contains only one affected translation. Even in this
    // case, multiple translations would be affected in a single revision, if we
    // allowed changes to untranslatable fields while editing non-default
    // translations, so that is forbidden too.
    if ($this->hasUntranslatableFieldsChanges($entity)) {
      if ($entity->isDefaultTranslationAffectedOnly()) {
        foreach ($entity->getTranslationLanguages(FALSE) as $langcode => $language) {
          if ($entity->getTranslation($langcode)->hasTranslationChanges()) {
            $this->context->addViolation($constraint->message);
            break;
          }
        }
      }
      else {
        $this->context->addViolation($constraint->message);
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
    if (isset($entity->original)) {
      $original = $entity->original;
    }
    else {
      $original = $this->entityTypeManager
        ->getStorage($entity->getEntityTypeId())
        ->loadRevision($entity->getLoadedRevisionId());
    }

    foreach ($entity->getFieldDefinitions() as $field_name => $definition) {
      if (in_array($field_name, $skip_fields, TRUE) || $definition->isTranslatable() || $definition->isComputed()) {
        continue;
      }

      // When saving entities in the user interface, the changed timestamp is
      // automatically incremented by ContentEntityForm::submitForm() even if
      // nothing was actually changed. Thus, the changed time needs to be
      // ignored when determining whether there are any actual changes in the
      // entity.
      $field = $entity->get($field_name);
      if ($field instanceof ChangedFieldItemList) {
        continue;
      }

      $items = $field->filterEmptyItems();
      $original_items = $original->get($field_name)->filterEmptyItems();
      if (!$items->equals($original_items)) {
        return TRUE;
      }
    }

    return FALSE;
  }

}
