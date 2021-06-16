<?php

namespace Drupal\content_translation;

use Drupal\Core\Config\Entity\ThirdPartySettingsInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Provides field translation synchronization capabilities.
 */
class FieldTranslationSynchronizer implements FieldTranslationSynchronizerInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The field type plugin manager.
   *
   * @var \Drupal\Core\Field\FieldTypePluginManagerInterface
   */
  protected $fieldTypeManager;

  /**
   * Constructs a FieldTranslationSynchronizer object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_manager
   *   The field type plugin manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, FieldTypePluginManagerInterface $field_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->fieldTypeManager = $field_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldSynchronizedProperties(FieldDefinitionInterface $field_definition) {
    $properties = [];
    $settings = $this->getFieldSynchronizationSettings($field_definition);
    foreach ($settings as $group => $translatable) {
      if (!$translatable) {
        $field_type_definition = $this->fieldTypeManager->getDefinition($field_definition->getType());
        if (!empty($field_type_definition['column_groups'][$group]['columns'])) {
          $properties = array_merge($properties, $field_type_definition['column_groups'][$group]['columns']);
        }
      }
    }
    return $properties;
  }

  /**
   * Returns the synchronization settings for the specified field.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   A field definition.
   *
   * @return string[]
   *   An array of synchronized field property names.
   */
  protected function getFieldSynchronizationSettings(FieldDefinitionInterface $field_definition) {
    if ($field_definition instanceof ThirdPartySettingsInterface && $field_definition->isTranslatable()) {
      return $field_definition->getThirdPartySetting('content_translation', 'translation_sync', []);
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function synchronizeFields(ContentEntityInterface $entity, $sync_langcode, $original_langcode = NULL) {
    $translations = $entity->getTranslationLanguages();

    // If we have no information about what to sync to, if we are creating a new
    // entity, if we have no translations for the current entity and we are not
    // creating one, then there is nothing to synchronize.
    if (empty($sync_langcode) || $entity->isNew() || count($translations) < 2) {
      return;
    }

    // If the entity language is being changed there is nothing to synchronize.
    $entity_unchanged = $this->getOriginalEntity($entity);
    if ($entity->getUntranslated()->language()->getId() != $entity_unchanged->getUntranslated()->language()->getId()) {
      return;
    }

    if ($entity->isNewRevision()) {
      if ($entity->isDefaultTranslationAffectedOnly()) {
        // If changes to untranslatable fields are configured to affect only the
        // default translation, we need to skip synchronization in pending
        // revisions, otherwise multiple translations would be affected.
        if (!$entity->isDefaultRevision()) {
          return;
        }
        // When this mode is enabled, changes to synchronized properties are
        // allowed only in the default translation, thus we need to make sure this
        // is always used as source for the synchronization process.
        else {
          $sync_langcode = $entity->getUntranslated()->language()->getId();
        }
      }
      elseif ($entity->isDefaultRevision()) {
        // If a new default revision is being saved, but a newer default
        // revision was created meanwhile, use any other translation as source
        // for synchronization, since that will have been merged from the
        // default revision. In this case the actual language does not matter as
        // synchronized properties are the same for all the translations in the
        // default revision.
        /** @var \Drupal\Core\Entity\ContentEntityInterface $default_revision */
        $default_revision = $this->entityTypeManager
          ->getStorage($entity->getEntityTypeId())
          ->load($entity->id());
        if ($default_revision->getLoadedRevisionId() !== $entity->getLoadedRevisionId()) {
          $other_langcodes = array_diff_key($default_revision->getTranslationLanguages(), [$sync_langcode => FALSE]);
          if ($other_langcodes) {
            $sync_langcode = key($other_langcodes);
          }
        }
      }
    }

    /** @var \Drupal\Core\Field\FieldItemListInterface $items */
    foreach ($entity as $field_name => $items) {
      $field_definition = $items->getFieldDefinition();
      $field_type_definition = $this->fieldTypeManager->getDefinition($field_definition->getType());
      $column_groups = $field_type_definition['column_groups'];

      // Sync if the field is translatable, not empty, and the synchronization
      // setting is enabled.
      if (($translation_sync = $this->getFieldSynchronizationSettings($field_definition)) && !$items->isEmpty()) {
        // Retrieve all the untranslatable column groups and merge them into
        // single list.
        $groups = array_keys(array_diff($translation_sync, array_filter($translation_sync)));

        // If a group was selected has the require_all_groups_for_translation
        // flag set, there are no untranslatable columns. This is done because
        // the UI adds JavaScript that disables the other checkboxes, so their
        // values are not saved.
        foreach (array_filter($translation_sync) as $group) {
          if (!empty($column_groups[$group]['require_all_groups_for_translation'])) {
            $groups = [];
            break;
          }
        }
        if (!empty($groups)) {
          $columns = [];
          foreach ($groups as $group) {
            $info = $column_groups[$group];
            // A missing 'columns' key indicates we have a single-column group.
            $columns = array_merge($columns, isset($info['columns']) ? $info['columns'] : [$group]);
          }
          if (!empty($columns)) {
            $values = [];
            foreach ($translations as $langcode => $language) {
              $values[$langcode] = $entity->getTranslation($langcode)->get($field_name)->getValue();
            }

            // If a translation is being created, the original values should be
            // used as the unchanged items. In fact there are no unchanged items
            // to check against.
            $langcode = $original_langcode ?: $sync_langcode;
            $unchanged_items = $entity_unchanged->getTranslation($langcode)->get($field_name)->getValue();
            $this->synchronizeItems($values, $unchanged_items, $sync_langcode, array_keys($translations), $columns);

            foreach ($translations as $langcode => $language) {
              $entity->getTranslation($langcode)->get($field_name)->setValue($values[$langcode]);
            }
          }
        }
      }
    }
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
   * {@inheritdoc}
   */
  public function synchronizeItems(array &$values, array $unchanged_items, $sync_langcode, array $translations, array $properties) {
    $source_items = $values[$sync_langcode];

    // Make sure we can detect any change in the source items.
    $change_map = [];

    // By picking the maximum size between updated and unchanged items, we make
    // sure to process also removed items.
    $total = max([count($source_items), count($unchanged_items)]);

    // As a first step we build a map of the deltas corresponding to the column
    // values to be synchronized. Recording both the old values and the new
    // values will allow us to detect any change in the order of the new items
    // for each column.
    for ($delta = 0; $delta < $total; $delta++) {
      foreach (['old' => $unchanged_items, 'new' => $source_items] as $key => $items) {
        if ($item_id = $this->itemHash($items, $delta, $properties)) {
          $change_map[$item_id][$key][] = $delta;
        }
      }
    }

    // Backup field values and the change map.
    $original_field_values = $values;
    $original_change_map = $change_map;

    // Reset field values so that no spurious one is stored. Source values must
    // be preserved in any case.
    $values = [$sync_langcode => $source_items];

    // Update field translations.
    foreach ($translations as $langcode) {

      // We need to synchronize only values different from the source ones.
      if ($langcode != $sync_langcode) {
        // Reinitialize the change map as it is emptied while processing each
        // language.
        $change_map = $original_change_map;

        // By using the maximum cardinality we ensure to process removed items.
        for ($delta = 0; $delta < $total; $delta++) {
          // By inspecting the map we built before we can tell whether a value
          // has been created or removed. A changed value will be interpreted as
          // a new value, in fact it did not exist before.
          $created = TRUE;
          $removed = TRUE;
          $old_delta = NULL;
          $new_delta = NULL;

          if ($item_id = $this->itemHash($source_items, $delta, $properties)) {
            if (!empty($change_map[$item_id]['old'])) {
              $old_delta = array_shift($change_map[$item_id]['old']);
            }
            if (!empty($change_map[$item_id]['new'])) {
              $new_delta = array_shift($change_map[$item_id]['new']);
            }
            $created = $created && !isset($old_delta);
            $removed = $removed && !isset($new_delta);
          }

          // If an item has been removed we do not store its translations.
          if ($removed) {
            continue;
          }
          // If a synchronized column has changed or has been created from
          // scratch we need to replace the values for this language as a
          // combination of the values that need to be synced from the source
          // items and the other columns from the existing values. This only
          // works if the delta exists in the language.
          elseif ($created && !empty($original_field_values[$langcode][$delta])) {
            $values[$langcode][$delta] = $this->createMergedItem($source_items[$delta], $original_field_values[$langcode][$delta], $properties);
          }
          // If the delta doesn't exist, copy from the source language.
          elseif ($created) {
            $values[$langcode][$delta] = $source_items[$delta];
          }
          // Otherwise the current item might have been reordered.
          elseif (isset($old_delta) && isset($new_delta)) {
            // If for any reason the old value is not defined for the current
            // language we fall back to the new source value, this way we ensure
            // the new values are at least propagated to all the translations.
            // If the value has only been reordered we just move the old one in
            // the new position.
            $item = isset($original_field_values[$langcode][$old_delta]) ? $original_field_values[$langcode][$old_delta] : $source_items[$new_delta];
            // When saving a default revision starting from a pending revision,
            // we may have desynchronized field values, so we make sure that
            // untranslatable properties are synchronized, even if in any other
            // situation this would not be necessary.
            $values[$langcode][$new_delta] = $this->createMergedItem($source_items[$new_delta], $item, $properties);
          }
        }
      }
    }
  }

  /**
   * Creates a merged item.
   *
   * @param array $source_item
   *   An item containing the untranslatable properties to be synchronized.
   * @param array $target_item
   *   An item containing the translatable properties to be kept.
   * @param string[] $properties
   *   An array of properties to be synchronized.
   *
   * @return array
   *   A merged item array.
   */
  protected function createMergedItem(array $source_item, array $target_item, array $properties) {
    $property_keys = array_flip($properties);
    $item_properties_to_sync = array_intersect_key($source_item, $property_keys);
    $item_properties_to_keep = array_diff_key($target_item, $property_keys);
    return $item_properties_to_sync + $item_properties_to_keep;
  }

  /**
   * Computes a hash code for the specified item.
   *
   * @param array $items
   *   An array of field items.
   * @param int $delta
   *   The delta identifying the item to be processed.
   * @param array $properties
   *   An array of column names to be synchronized.
   *
   * @returns string
   *   A hash code that can be used to identify the item.
   */
  protected function itemHash(array $items, $delta, array $properties) {
    $values = [];

    if (isset($items[$delta])) {
      foreach ($properties as $property) {
        if (!empty($items[$delta][$property])) {
          $value = $items[$delta][$property];
          // String and integer values are by far the most common item values,
          // thus we special-case them to improve performance.
          $values[] = is_string($value) || is_int($value) ? $value : hash('sha256', serialize($value));
        }
        else {
          // Explicitly track also empty values.
          $values[] = '';
        }
      }
    }

    return implode('.', $values);
  }

}
