<?php

namespace Drupal\Core\Entity\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the EntityChanged constraint.
 */
class EntityChangedConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint) {
    if (isset($entity)) {
      /** @var \Drupal\Core\Entity\EntityInterface $entity */
      if (!$entity->isNew()) {
        $saved_entity = \Drupal::entityManager()->getStorage($entity->getEntityTypeId())->loadUnchanged($entity->id());
        // Ensure that all the entity translations are the same as or newer
        // than their current version in the storage in order to avoid
        // reverting other changes. In fact the entity object that is being
        // saved might contain an older entity translation when different
        // translations are being concurrently edited.
        if ($saved_entity) {
          $common_translation_languages = array_intersect_key($entity->getTranslationLanguages(), $saved_entity->getTranslationLanguages());
          foreach (array_keys($common_translation_languages) as $langcode) {
            // Merely comparing the latest changed timestamps across all
            // translations is not sufficient since other translations may have
            // been edited and saved in the meanwhile. Therefore, compare the
            // changed timestamps of each entity translation individually.
            if ($saved_entity->getTranslation($langcode)->getChangedTime() > $entity->getTranslation($langcode)->getChangedTime()) {
              $this->context->addViolation($constraint->message);
              break;
            }
          }
        }
      }
    }
  }

}
