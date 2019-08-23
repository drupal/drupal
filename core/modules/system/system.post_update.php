<?php

/**
 * @file
 * Post update functions for System.
 */

use Drupal\Core\Config\Entity\ConfigEntityUpdater;
use Drupal\Core\Entity\Display\EntityDisplayInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;

/**
 * Re-save all configuration entities to recalculate dependencies.
 */
function system_post_update_recalculate_configuration_entity_dependencies(&$sandbox = NULL) {
  if (!isset($sandbox['config_names'])) {
    $sandbox['config_names'] = \Drupal::configFactory()->listAll();
    $sandbox['count'] = count($sandbox['config_names']);
  }
  /** @var \Drupal\Core\Config\ConfigManagerInterface $config_manager */
  $config_manager = \Drupal::service('config.manager');

  $count = 0;
  foreach ($sandbox['config_names'] as $key => $config_name) {
    if ($entity = $config_manager->loadConfigEntityByName($config_name)) {
      $entity->save();
    }
    unset($sandbox['config_names'][$key]);
    $count++;
    // Do 50 at a time.
    if ($count == 50) {
      break;
    }
  }

  $sandbox['#finished'] = empty($sandbox['config_names']) ? 1 : ($sandbox['count'] - count($sandbox['config_names'])) / $sandbox['count'];
  return t('Configuration dependencies recalculated');
}

/**
 * Update entity displays to contain the region for each field.
 */
function system_post_update_add_region_to_entity_displays() {
  $entity_save = function (EntityDisplayInterface $entity) {
    // preSave() will fill in the correct region based on the 'type'.
    $entity->save();
  };
  array_map($entity_save, EntityViewDisplay::loadMultiple());
  array_map($entity_save, EntityFormDisplay::loadMultiple());
}

/**
 * Force caches using hashes to be cleared (Twig, render cache, etc.).
 */
function system_post_update_hashes_clear_cache() {
  // Empty post-update hook.
}

/**
 * Force plugin definitions to be cleared.
 *
 * @see https://www.drupal.org/node/2802663
 */
function system_post_update_timestamp_plugins() {
  // Empty post-update hook.
}

/**
 * Clear caches to ensure Classy's message library is always added.
 */
function system_post_update_classy_message_library() {
  // Empty post-update hook.
}

/**
 * Force field type plugin definitions to be cleared.
 *
 * @see https://www.drupal.org/node/2403703
 */
function system_post_update_field_type_plugins() {
  // Empty post-update hook.
}

/**
 * Clear caches due to schema changes in core.entity.schema.yml.
 */
function system_post_update_field_formatter_entity_schema() {
  // Empty post-update hook.
}

/**
 * Clear the library cache and ensure aggregate files are regenerated.
 */
function system_post_update_fix_jquery_extend() {
  // Empty post-update hook.
}

/**
 * Change plugin IDs of actions.
 */
function system_post_update_change_action_plugins() {
  $old_new_action_id_map = [
    'comment_publish_action' => 'entity:publish_action:comment',
    'comment_unpublish_action' => 'entity:unpublish_action:comment',
    'comment_save_action' => 'entity:save_action:comment',
    'node_publish_action' => 'entity:publish_action:node',
    'node_unpublish_action' => 'entity:unpublish_action:node',
    'node_save_action' => 'entity:save_action:node',
  ];

  /** @var \Drupal\system\Entity\Action[] $actions */
  $actions = \Drupal::entityTypeManager()->getStorage('action')->loadMultiple();
  foreach ($actions as $action) {
    if (isset($old_new_action_id_map[$action->getPlugin()->getPluginId()])) {
      $action->setPlugin($old_new_action_id_map[$action->getPlugin()->getPluginId()]);
      $action->save();
    }
  }
}

/**
 * Change plugin IDs of delete actions.
 */
function system_post_update_change_delete_action_plugins() {
  $old_new_action_id_map = [
    'comment_delete_action' => 'entity:delete_action:comment',
    'node_delete_action' => 'entity:delete_action:node',
  ];

  /** @var \Drupal\system\Entity\Action[] $actions */
  $actions = \Drupal::entityTypeManager()->getStorage('action')->loadMultiple();
  foreach ($actions as $action) {
    if (isset($old_new_action_id_map[$action->getPlugin()->getPluginId()])) {
      $action->setPlugin($old_new_action_id_map[$action->getPlugin()->getPluginId()]);
      $action->save();
    }
  }
}

/**
 * Force cache clear for language item callback.
 *
 * @see https://www.drupal.org/node/2851736
 */
function system_post_update_language_item_callback() {
  // Empty post-update hook.
}

/**
 * Update all entity displays that contain extra fields.
 */
function system_post_update_extra_fields(&$sandbox = NULL) {
  $config_entity_updater = \Drupal::classResolver(ConfigEntityUpdater::class);
  $entity_field_manager = \Drupal::service('entity_field.manager');

  $callback = function (EntityDisplayInterface $display) use ($entity_field_manager) {
    $display_context = $display instanceof EntityViewDisplayInterface ? 'display' : 'form';
    $extra_fields = $entity_field_manager->getExtraFields($display->getTargetEntityTypeId(), $display->getTargetBundle());

    // If any extra fields are used as a component, resave the display with the
    // updated component information.
    $needs_save = FALSE;
    if (!empty($extra_fields[$display_context])) {
      foreach ($extra_fields[$display_context] as $name => $extra_field) {
        if ($component = $display->getComponent($name)) {
          $display->setComponent($name, $component);
          $needs_save = TRUE;
        }
      }
    }
    return $needs_save;
  };

  $config_entity_updater->update($sandbox, 'entity_form_display', $callback);
  $config_entity_updater->update($sandbox, 'entity_view_display', $callback);
}

/**
 * Force cache clear to ensure aggregated JavaScript files are regenerated.
 *
 * @see https://www.drupal.org/project/drupal/issues/2995570
 */
function system_post_update_states_clear_cache() {
  // Empty post-update hook.
}

/**
 * Initialize 'expand_all_items' values to system_menu_block.
 */
function system_post_update_add_expand_all_items_key_in_system_menu_block(&$sandbox = NULL) {
  if (!\Drupal::moduleHandler()->moduleExists('block')) {
    return;
  }
  \Drupal::classResolver(ConfigEntityUpdater::class)->update($sandbox, 'block', function ($block) {
    return strpos($block->getPluginId(), 'system_menu_block:') === 0;
  });
}

/**
 * Clear the menu cache.
 *
 * @see https://www.drupal.org/project/drupal/issues/3044364
 */
function system_post_update_clear_menu_cache() {
  // Empty post-update hook.
}

/**
 * Clear the schema cache.
 */
function system_post_update_layout_plugin_schema_change() {
  // Empty post-update hook.
}
