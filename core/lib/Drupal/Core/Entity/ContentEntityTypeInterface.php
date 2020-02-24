<?php

namespace Drupal\Core\Entity;

/**
 * Provides an interface for a content entity type and its metadata.
 */
interface ContentEntityTypeInterface extends EntityTypeInterface {

  /**
   * Gets an array of entity revision metadata keys.
   *
   * @param bool $include_backwards_compatibility_field_names
   *   (optional and deprecated) Whether to provide the revision keys on a
   *   best-effort basis by looking at the base fields defined by the entity
   *   type. Note that this parameter will be removed in Drupal 9.0.0. Defaults
   *   to TRUE.
   *
   * @return array
   *   An array describing how the Field API can extract revision metadata
   *   information of this entity type:
   *   - revision_log_message: The name of the property that contains description
   *     of the changes that were made in the current revision.
   *   - revision_user: The name of the property that contains the user ID of
   *     the author of the current revision.
   *   - revision_created: The name of the property that contains the timestamp
   *     of the current revision.
   */
  public function getRevisionMetadataKeys($include_backwards_compatibility_field_names = TRUE);

  /**
   * Gets a specific entity revision metadata key.
   *
   * @param string $key
   *   The name of the entity revision metadata key to return.
   *
   * @return string|bool
   *   The entity revision metadata key, or FALSE if it does not exist.
   *
   * @see self::getRevisionMetadataKeys()
   */
  public function getRevisionMetadataKey($key);

  /**
   * Indicates if a given entity revision metadata key exists.
   *
   * @param string $key
   *   The name of the entity revision metadata key to check.
   *
   * @return bool
   *   TRUE if a given entity revision metadata key exists, FALSE otherwise.
   */
  public function hasRevisionMetadataKey($key);

  /**
   * Sets a revision metadata key.
   *
   * @param string $key
   *   The name of the entity revision metadata key to set.
   * @param string|null $field_name
   *   The name of the entity field key to use for the revision metadata key. If
   *   NULL is passed, the value of the revision metadata key is unset.
   *
   * @return $this
   */
  public function setRevisionMetadataKey($key, $field_name);

}
