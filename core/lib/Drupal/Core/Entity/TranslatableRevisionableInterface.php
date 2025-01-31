<?php

namespace Drupal\Core\Entity;

/**
 * Provides methods for an entity to support revision translation.
 */
interface TranslatableRevisionableInterface extends TranslatableInterface, RevisionableInterface {

  /**
   * Checks whether this is the latest revision affecting this translation.
   *
   * @return bool
   *   TRUE if this revision is the latest one affecting the active translation,
   *   FALSE otherwise.
   */
  public function isLatestTranslationAffectedRevision();

  /**
   * Marks the current revision translation as affected.
   *
   * Setting the revision translation affected flag through the setter or
   * through the field directly will always enforce it, which will be used by
   * the entity storage to determine if the flag should be recomputed or the set
   * value should be used instead.
   *
   * @param bool|null $affected
   *   The flag value. A NULL value can be specified to reset the current value
   *   and make sure a new value will be computed by the system.
   *
   * @return $this
   *
   * @see \Drupal\Core\Entity\ContentEntityStorageBase::populateAffectedRevisionTranslations()
   */
  public function setRevisionTranslationAffected($affected);

  /**
   * Checks whether the current translation is affected by the current revision.
   *
   * @return bool
   *   TRUE if the entity object is affected by the current revision, FALSE
   *   otherwise.
   */
  public function isRevisionTranslationAffected();

  /**
   * Checks if the revision translation affected flag value has been enforced.
   *
   * @return bool
   *   TRUE if revision translation affected flag is enforced, FALSE otherwise.
   *
   * @internal
   */
  public function isRevisionTranslationAffectedEnforced();

  /**
   * Enforces the revision translation affected flag value.
   *
   * Note that this method call will not have any influence on the storage if
   * the value of the revision translation affected flag is NULL which is used
   * as an indication for the storage to recompute the flag.
   *
   * @param bool $enforced
   *   If TRUE, the value of the revision translation affected flag will be
   *   enforced so that on entity save the entity storage will not recompute it.
   *   Otherwise the storage will recompute it.
   *
   * @return $this
   *
   * @internal
   *
   * @see \Drupal\Core\Entity\ContentEntityInterface::setRevisionTranslationAffected()
   */
  public function setRevisionTranslationAffectedEnforced($enforced);

  /**
   * Checks if untranslatable fields should affect only the default translation.
   *
   * @return bool
   *   TRUE if untranslatable fields should affect only the default translation,
   *   FALSE otherwise.
   */
  public function isDefaultTranslationAffectedOnly();

}
