<?php

/**
 * @file
 * Post update functions for Layout Builder.
 */

use Drupal\Core\Config\Entity\ConfigEntityUpdater;
use Drupal\layout_builder\Entity\LayoutEntityDisplayInterface;
use Drupal\layout_builder\TempStoreIdentifierInterface;
use Drupal\user\Entity\Role;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Drupal\layout_builder\Plugin\SectionStorage\OverridesSectionStorage;

/**
 * Rebuild plugin dependencies for all entity view displays.
 */
function layout_builder_post_update_rebuild_plugin_dependencies(&$sandbox = NULL) {
  $storage = \Drupal::entityTypeManager()->getStorage('entity_view_display');
  if (!isset($sandbox['ids'])) {
    $sandbox['ids'] = $storage->getQuery()->accessCheck(FALSE)->execute();
    $sandbox['count'] = count($sandbox['ids']);
  }

  for ($i = 0; $i < 10 && count($sandbox['ids']); $i++) {
    $id = array_shift($sandbox['ids']);
    if ($display = $storage->load($id)) {
      $display->save();
    }
  }

  $sandbox['#finished'] = empty($sandbox['ids']) ? 1 : ($sandbox['count'] - count($sandbox['ids'])) / $sandbox['count'];
}

/**
 * Ensure all extra fields are properly stored on entity view displays.
 *
 * Previously
 * \Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay::setComponent()
 * was not correctly setting the configuration for extra fields. This function
 * calls setComponent() for all extra field components to ensure the updated
 * logic is invoked on all extra fields to correct the settings.
 */
function layout_builder_post_update_add_extra_fields(&$sandbox = NULL) {
  $entity_field_manager = \Drupal::service('entity_field.manager');
  \Drupal::classResolver(ConfigEntityUpdater::class)->update($sandbox, 'entity_view_display', function (LayoutEntityDisplayInterface $display) use ($entity_field_manager) {
    if (!$display->isLayoutBuilderEnabled()) {
      return FALSE;
    }

    $extra_fields = $entity_field_manager->getExtraFields($display->getTargetEntityTypeId(), $display->getTargetBundle());
    $components = $display->getComponents();
    // Sort the components to avoid them being reordered by setComponent().
    uasort($components, 'Drupal\Component\Utility\SortArray::sortByWeightElement');
    $result = FALSE;
    foreach ($components as $name => $component) {
      if (isset($extra_fields['display'][$name])) {
        $display->setComponent($name, $component);
        $result = TRUE;
      }
    }
    return $result;
  });
}

/**
 * Clear caches due to changes to section storage annotation changes.
 */
function layout_builder_post_update_section_storage_context_definitions() {
  // Empty post-update hook.
}

/**
 * Clear caches due to changes to annotation changes to the Overrides plugin.
 */
function layout_builder_post_update_overrides_view_mode_annotation() {
  // Empty post-update hook.
}

/**
 * Clear caches due to routing changes for the new discard changes form.
 */
function layout_builder_post_update_cancel_link_to_discard_changes_form() {
  // Empty post-update hook.
}

/**
 * Clear caches due to the removal of the layout_is_rebuilding query string.
 */
function layout_builder_post_update_remove_layout_is_rebuilding() {
  // Empty post-update hook.
}

/**
 * Clear caches due to routing changes to move the Layout Builder UI to forms.
 */
function layout_builder_post_update_routing_entity_form() {
  // Empty post-update hook.
}

/**
 * Clear caches to discover new blank layout plugin.
 */
function layout_builder_post_update_discover_blank_layout_plugin() {
  // Empty post-update hook.
}

/**
 * Clear caches due to routing changes to changing the URLs for defaults.
 */
function layout_builder_post_update_routing_defaults() {
  // Empty post-update hook.
}

/**
 * Clear caches due to new link added to Layout Builder's contextual links.
 */
function layout_builder_post_update_discover_new_contextual_links() {
  // Empty post-update hook.
}

/**
 * Fix Layout Builder tempstore keys of existing entries.
 */
function layout_builder_post_update_fix_tempstore_keys() {
  /** @var \Drupal\layout_builder\SectionStorage\SectionStorageManagerInterface $section_storage_manager */
  $section_storage_manager = \Drupal::service('plugin.manager.layout_builder.section_storage');
  /** @var \Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface $key_value_factory */
  $key_value_factory = \Drupal::service('keyvalue.expirable');

  // Loop through each section storage type.
  foreach (array_keys($section_storage_manager->getDefinitions()) as $section_storage_type) {
    $key_value = $key_value_factory->get("tempstore.shared.layout_builder.section_storage.$section_storage_type");
    foreach ($key_value->getAll() as $key => $value) {
      $contexts = $section_storage_manager->loadEmpty($section_storage_type)->deriveContextsFromRoute($key, [], '', []);
      if ($section_storage = $section_storage_manager->load($section_storage_type, $contexts)) {

        // Some overrides were stored with an incorrect view mode value. Update
        // the view mode on the temporary section storage, if necessary.
        if ($section_storage_type === 'overrides') {
          $view_mode = $value->data['section_storage']->getContextValue('view_mode');
          $new_view_mode = $section_storage->getContextValue('view_mode');
          if ($view_mode !== $new_view_mode) {
            $value->data['section_storage']->setContextValue('view_mode', $new_view_mode);
            $key_value->set($key, $value);
          }
        }

        // The previous tempstore key names were exact matches with the section
        // storage ID. Attempt to load the corresponding section storage and
        // rename the tempstore entry if the section storage provides a more
        // granular tempstore key.
        if ($section_storage instanceof TempStoreIdentifierInterface) {
          $new_key = $section_storage->getTempstoreKey();
          if ($key !== $new_key) {
            if ($key_value->has($new_key)) {
              $key_value->delete($new_key);
            }
            $key_value->rename($key, $new_key);
          }
        }
      }
    }
  }
}

/**
 * Clear caches due to config schema additions.
 */
function layout_builder_post_update_section_third_party_settings_schema() {
  // Empty post-update hook.
}

/**
 * Clear caches due to dependency changes in the layout_builder render element.
 */
function layout_builder_post_update_layout_builder_dependency_change() {
  // Empty post-update hook.
}

/**
 * Add new custom block permission to all roles with 'configure any layout'.
 */
function layout_builder_post_update_update_permissions() {
  foreach (Role::loadMultiple() as $role) {
    if ($role->hasPermission('configure any layout')) {
      $role->grantPermission('create and edit custom blocks')->save();
    }
  }
}

/**
 * Set the layout builder field as non-translatable where possible.
 */
function layout_builder_post_update_make_layout_untranslatable() {
  /** @var \Drupal\Core\Entity\EntityFieldManagerInterface $field_manager */
  $field_manager = \Drupal::service('entity_field.manager');
  $field_map = $field_manager->getFieldMap();
  foreach ($field_map as $entity_type_id => $field_infos) {
    if (isset($field_infos[OverridesSectionStorage::FIELD_NAME]['bundles'])) {
      $non_translatable_bundle_count = 0;
      foreach ($field_infos[OverridesSectionStorage::FIELD_NAME]['bundles'] as $bundle) {
        // The field map can contain stale information. If the field does not
        // exist, ignore it. The field map will be rebuilt when the cache is
        // cleared at the end of the update process.
        if (!$field_config = FieldConfig::loadByName($entity_type_id, $bundle, OverridesSectionStorage::FIELD_NAME)) {
          continue;
        }
        if (!$field_config->isTranslatable()) {
          $non_translatable_bundle_count++;
          // The layout field is already configured to be non-translatable so it
          // does not need to be updated.
          continue;
        }
        if (_layout_builder_bundle_has_no_translations($entity_type_id, $bundle) || _layout_builder_bundle_has_no_layouts($entity_type_id, $bundle)) {
          // Either none of the entities have layouts or none of them have
          // translations. In either case it is safe to set the field to be
          // non-translatable.
          $field_config->setTranslatable(FALSE);
          $field_config->save();
          $non_translatable_bundle_count++;
        }
      }
      // Set the field storage to untranslatable if the field config for each
      // bundle is now untranslatable. This removes layout fields for the
      // entity type from the Content Translation configuration form.
      if (count($field_infos[OverridesSectionStorage::FIELD_NAME]['bundles']) === $non_translatable_bundle_count) {
        $field_storage = FieldStorageConfig::loadByName($entity_type_id, OverridesSectionStorage::FIELD_NAME);
        $field_storage->setTranslatable(FALSE);
        $field_storage->save();
      }
    }
  }
}

/**
 * Determines if there are zero layout overrides for the bundle.
 *
 * @param string $entity_type_id
 *   The entity type ID.
 * @param string $bundle
 *   The bundle name.
 *
 * @return bool
 *   TRUE if there are zero layout overrides for the bundle, otherwise FALSE.
 */
function _layout_builder_bundle_has_no_layouts($entity_type_id, $bundle) {
  $entity_update_manager = \Drupal::entityDefinitionUpdateManager();
  $entity_type = $entity_update_manager->getEntityType($entity_type_id);
  $bundle_key = $entity_type->getKey('bundle');
  $query = \Drupal::entityTypeManager()->getStorage($entity_type_id)->getQuery();
  if ($bundle_key) {
    $query->condition($bundle_key, $bundle);
  }
  if ($entity_type->isRevisionable()) {
    $query->allRevisions();
  }
  $query->exists(OverridesSectionStorage::FIELD_NAME)
    ->accessCheck(FALSE)
    ->range(0, 1);
  $results = $query->execute();
  return empty($results);
}

/**
 * Determines if there are zero translations for the bundle.
 *
 * @param string $entity_type_id
 *   The entity type ID.
 * @param string $bundle
 *   The bundle name.
 *
 * @return bool
 *   TRUE if there are zero translations for the bundle, otherwise FALSE.
 */
function _layout_builder_bundle_has_no_translations($entity_type_id, $bundle) {
  $entity_update_manager = \Drupal::entityDefinitionUpdateManager();
  $entity_type = $entity_update_manager->getEntityType($entity_type_id);
  if (!$entity_type->isTranslatable()) {
    return TRUE;
  }
  $query = \Drupal::entityTypeManager()->getStorage($entity_type_id)->getQuery();
  $bundle_key = $entity_type->getKey('bundle');
  if ($entity_type->hasKey('default_langcode')) {
    if ($bundle_key) {
      $query->condition($bundle_key, $bundle);
    }
    if ($entity_type->isRevisionable()) {
      $query->allRevisions();
    }
    $query->condition($entity_type->getKey('default_langcode'), 0)
      ->accessCheck(FALSE)
      ->range(0, 1);
    $results = $query->execute();
    return empty($results);
  }
  // A translatable entity type should always have a default_langcode key. If it
  // doesn't we have no way to determine if there are translations.
  return FALSE;
}
