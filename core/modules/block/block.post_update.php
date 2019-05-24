<?php

/**
 * @file
 * Post update functions for Block.
 */

/**
 * Disable all blocks with missing context IDs in block_update_8001().
 */
function block_post_update_disable_blocks_with_missing_contexts() {
  // Don't execute the function if block_update_8002() got executed already,
  // which used to do the same. Note: Its okay to check here, because
  // update_do_one() does not update the installed schema version until the
  // batch is finished.
  $module_schema = drupal_get_installed_schema_version('block');

  // The state entry 'block_update_8002_placeholder' is used in order to
  // indicate that the placeholder block_update_8002() function has been
  // executed, so this function needs to be executed as well. If the non
  // placeholder version of block_update_8002() got executed already, the state
  // won't be set and we skip this update.
  if ($module_schema >= 8002 && !\Drupal::state()->get('block_update_8002_placeholder', FALSE)) {
    return;
  }

  // Cleanup the state entry as its no longer needed.
  \Drupal::state()->delete('block_update_8002');

  $block_update_8001 = \Drupal::keyValue('update_backup')->get('block_update_8001', []);

  $block_ids = array_keys($block_update_8001);
  $block_storage = \Drupal::entityTypeManager()->getStorage('block');
  $blocks = $block_storage->loadMultiple($block_ids);
  /** @var $blocks \Drupal\block\BlockInterface[] */
  foreach ($blocks as $block) {
    // This block has had conditions removed due to an inability to resolve
    // contexts in block_update_8001() so disable it.

    // Disable currently enabled blocks.
    if ($block_update_8001[$block->id()]['status']) {
      $block->setStatus(FALSE);
      $block->save();
    }
  }

  // Provides a list of plugin labels, keyed by plugin ID.
  $condition_plugin_id_label_map = array_column(\Drupal::service('plugin.manager.condition')->getDefinitions(), 'label', 'id');

  // Override with the UI labels we are aware of. Sadly they are not machine
  // accessible, see
  // \Drupal\node\Plugin\Condition\NodeType::buildConfigurationForm().
  $condition_plugin_id_label_map['node_type'] = t('Content types');
  $condition_plugin_id_label_map['request_path'] = t('Pages');
  $condition_plugin_id_label_map['user_role'] = t('Roles');

  if (count($block_ids) > 0) {
    $message = t('Encountered an unknown context mapping key coming probably from a contributed or custom module: One or more mappings could not be updated. Please manually review your visibility settings for the following blocks, which are disabled now:');
    $message .= '<ul>';
    foreach ($blocks as $disabled_block_id => $disabled_block) {
      $message .= '<li>' . t('@label (Visibility: @plugin_ids)', [
          '@label' => $disabled_block->get('settings')['label'],
          '@plugin_ids' => implode(', ', array_intersect_key($condition_plugin_id_label_map, array_flip(array_keys($block_update_8001[$disabled_block_id]['missing_context_ids'])))),
        ]) . '</li>';
    }
    $message .= '</ul>';

    return $message;
  }
}

/**
 * Disable blocks that are placed into the "disabled" region.
 */
function block_post_update_disabled_region_update() {
  // An empty update will flush caches, forcing block_rebuild() to run.
}

/**
 * Fix invalid 'negate' values in block visibility conditions.
 */
function block_post_update_fix_negate_in_conditions() {
  $block_storage = \Drupal::entityTypeManager()->getStorage('block');
  /** @var \Drupal\block\BlockInterface[] $blocks */
  $blocks = $block_storage->loadMultiple();
  foreach ($blocks as $block) {
    $block_needs_saving = FALSE;
    // Check each visibility condition for an invalid negate value, and fix it.
    foreach ($block->getVisibilityConditions() as $condition_id => $condition) {
      $configuration = $condition->getConfiguration();
      if (array_key_exists('negate', $configuration) && !is_bool($configuration['negate'])) {
        $configuration['negate'] = (bool) $configuration['negate'];
        $condition->setConfiguration($configuration);
        $block_needs_saving = TRUE;
      }
    }
    if ($block_needs_saving) {
      $block->save();
    }
  }
}
