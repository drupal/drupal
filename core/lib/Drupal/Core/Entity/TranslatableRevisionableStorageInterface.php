<?php

namespace Drupal\Core\Entity;

/**
 * A storage that supports translatable and revisionable entity types.
 */
interface TranslatableRevisionableStorageInterface extends TranslatableStorageInterface, RevisionableStorageInterface {

  /**
   * Creates a new revision starting off from the specified entity object.
   *
   * When dealing with a translatable entity, this will merge the default
   * revision with the active translation of the passed entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface|\Drupal\Core\Entity\RevisionableInterface $entity
   *   The revisionable entity object being modified.
   * @param bool $default
   *   (optional) Whether the new revision should be marked as default. Defaults
   *   to TRUE.
   * @param bool|null $keep_untranslatable_fields
   *   (optional) Whether untranslatable field values should be kept or copied
   *   from the default revision when generating a merged revision. Defaults to
   *   TRUE if the provided entity is the default translation and untranslatable
   *   fields should only affect the default translation, FALSE otherwise.
   *
   * @return \Drupal\Core\Entity\EntityInterface|\Drupal\Core\Entity\RevisionableInterface
   *   A new translatable entity revision object.
   */
  public function createRevision(RevisionableInterface $entity, $default = TRUE, $keep_untranslatable_fields = NULL);

  /**
   * Returns the latest revision affecting the specified translation.
   *
   * @param int|string $entity_id
   *   The entity identifier.
   * @param string $langcode
   *   The language code of the translation.
   *
   * @return int|string|null
   *   A revision ID or NULL if no revision affecting the specified translation
   *   could be found.
   */
  public function getLatestTranslationAffectedRevisionId($entity_id, $langcode);

}
