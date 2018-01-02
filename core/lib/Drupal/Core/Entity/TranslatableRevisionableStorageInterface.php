<?php

namespace Drupal\Core\Entity;

/**
 * A storage that supports translatable and revisionable entity types.
 */
interface TranslatableRevisionableStorageInterface extends TranslatableStorageInterface, RevisionableStorageInterface {

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
