<?php

/**
 * @file
 * Hooks provided by the Block module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the result of \Drupal\block\BlockBase::build().
 *
 * This hook is called after the content has been assembled in a structured
 * array and may be used for doing processing which requires that the complete
 * block content structure has been built.
 *
 * If the module wishes to act on the rendered HTML of the block rather than
 * the structured content array, it may use this hook to add a #post_render
 * callback. Alternatively, it could also implement hook_preprocess_HOOK() for
 * block.html.twig. See drupal_render() and _theme() documentation respectively
 * for details.
 *
 * In addition to hook_block_view_alter(), which is called for all blocks, there
 * is hook_block_view_BASE_BLOCK_ID_alter(), which can be used to target a
 * specific block or set of similar blocks.
 *
 * @param array &$build
 *   A renderable array of data, as returned from the build() implementation of
 *   the plugin that defined the block:
 *   - #title: The default localized title of the block.
 * @param \Drupal\block\BlockPluginInterface $block
 *   The block plugin instance.
 *
 * @see hook_block_view_BASE_BLOCK_ID_alter()
 */
function hook_block_view_alter(array &$build, \Drupal\block\BlockPluginInterface $block) {
  // Remove the contextual links on all blocks that provide them.
  if (isset($build['#contextual_links'])) {
    unset($build['#contextual_links']);
  }
}

/**
 * Provide a block plugin specific block_view alteration.
 *
 * In this hook name, BASE_BLOCK_ID refers to the block implementation's plugin
 * id, regardless of whether the plugin supports derivatives. For example, for
 * the \Drupal\system\Plugin\Block\SystemPoweredByBlock block, this would be
 * 'system_powered_by_block' as per that class's annotation. And for the
 * \Drupal\system\Plugin\Block\SystemMenuBlock block, it would be
 * 'system_menu_block' as per that class's annotation, regardless of which menu
 * the derived block is for.
 *
 * @param array $build
 *   A renderable array of data, as returned from the build() implementation of
 *   the plugin that defined the block:
 *   - #title: The default localized title of the block.
 * @param \Drupal\block\BlockPluginInterface $block
 *   The block plugin instance.
 *
 * @see hook_block_view_alter()
 */
function hook_block_view_BASE_BLOCK_ID_alter(array &$build, \Drupal\block\BlockPluginInterface $block) {
  // Change the title of the specific block.
  $build['#title'] = t('New title of the block');
}

/**
 * Control access to a block instance.
 *
 * Modules may implement this hook if they want to have a say in whether or not
 * a given user has access to perform a given operation on a block instance.
 *
 * @param \Drupal\block\Entity\Block $block
 *   The block instance.
 * @param string $operation
 *   The operation to be performed, e.g., 'view', 'create', 'delete', 'update'.
 * @param \Drupal\user\Entity\User $account
 *   The user object to perform the access check operation on.
 * @param string $langcode
 *   The language code to perform the access check operation on.
 *
 * @return bool|null
 *   FALSE denies access. TRUE allows access unless another module returns
 *   FALSE. If all modules return NULL, then default access rules from
 *   \Drupal\block\BlockAccessController::checkAccess() are used.
 *
 * @see \Drupal\Core\Entity\EntityAccessController::access()
 * @see \Drupal\block\BlockAccessController::checkAccess()
 */
function hook_block_access(\Drupal\block\Entity\Block $block, $operation, \Drupal\user\Entity\User $account, $langcode) {
  // Example code that would prevent displaying the 'Powered by Drupal' block in
  // a region different than the footer.
  if ($operation == 'view' && $block->get('plugin') == 'system_powered_by_block' && $block->get('region') != 'footer') {
    return FALSE;
  }
}

/**
 * @} End of "addtogroup hooks".
 */
