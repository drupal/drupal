<?php

namespace Drupal\content_translation;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides field translation synchronization capabilities.
 */
interface FieldTranslationSynchronizerInterface {

  /**
   * Performs field column synchronization on the given entity.
   *
   * Field column synchronization takes care of propagating any change in the
   * field items order and in the column values themselves to all the available
   * translations. This functionality is provided by defining a
   * 'translation_sync' key for the 'content_translation' module's portion of
   * the field definition's 'third_party_settings', holding an array of
   * column names to be synchronized. The synchronized column values are shared
   * across translations, while the rest varies per-language. This is useful for
   * instance to translate the "alt" and "title" textual elements of an image
   * field, while keeping the same image on every translation.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity whose values should be synchronized.
   * @param string $sync_langcode
   *   The language of the translation whose values should be used as source for
   *   synchronization.
   * @param string $original_langcode
   *   (optional) If a new translation is being created, this should be the
   *   language code of the original values. Defaults to NULL.
   */
  public function synchronizeFields(ContentEntityInterface $entity, $sync_langcode, $original_langcode = NULL);

  /**
   * Synchronize the items of a single field.
   *
   * All the column values of the "active" language are compared to the
   * unchanged values to detect any addition, removal or change in the items
   * order. Subsequently the detected changes are performed on the field items
   * in other available languages.
   *
   * @param array $field_values
   *   The field values to be synchronized.
   * @param array $unchanged_items
   *   The unchanged items to be used to detect changes.
   * @param string $sync_langcode
   *   The language code of the items to use as source values.
   * @param array $translations
   *   An array of all the available language codes for the given field.
   * @param array $columns
   *   An array of column names to be synchronized.
   */
  public function synchronizeItems(array &$field_values, array $unchanged_items, $sync_langcode, array $translations, array $columns);

}
