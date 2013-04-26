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
 * Perform alterations to the content of a block.
 *
 * This hook allows you to modify any data returned by hook_block_view().
 *
 * Note that instead of hook_block_view_alter(), which is called for all blocks,
 * you can also use hook_block_view_ID_alter() to alter a specific block, or
 * hook_block_view_NAME_alter() to alter a specific block instance.
 *
 * @param array $build
 *   A renderable array of data, as returned from the build() implementation of
 *   the plugin that defined the block:
 *   - #title: The default localized title of the block.
 * @param \Drupal\block\BlockPluginInterface $block
 *   The block instance.
 *
 * @see hook_block_view_ID_alter()
 * @see hook_block_view_NAME_alter()
 */
function hook_block_view_alter(array &$build, \Drupal\block\Plugin\Core\Entity\Block $block) {
  // Remove the contextual links on all blocks that provide them.
  if (is_array($build) && isset($build['#contextual_links'])) {
    unset($build['#contextual_links']);
  }
  // Add a theme wrapper function defined by the current module to all blocks
  // provided by the "somemodule" module.
  if (is_array($build) && $block instanceof SomeBlockClass) {
    $build['#theme_wrappers'][] = 'mymodule_special_block';
  }
}

/**
 * Perform alterations to a specific block.
 *
 * Modules can implement hook_block_view_ID_alter() to modify a specific block,
 * rather than implementing hook_block_view_alter().
 *
 * @param array $build
 *   A renderable array of data, as returned from the build() implementation of
 *   the plugin that defined the block:
 *   - #title: The default localized title of the block.
 * @param \Drupal\block\BlockPluginInterface $block
 *   The block instance.
 *
 * @todo Add a more specific example of a block ID, and illustrate how this is
 *   different from hook_block_view_NAME_alter().
 *
 * @see hook_block_view_alter()
 * @see hook_block_view_NAME_alter()
 */
function hook_block_view_ID_alter(array &$build, \Drupal\block\BlockPluginInterface $block) {
  // This code will only run for a specific block. For example, if ID
  // in the function definition above is set to "someid", the code
  // will only run on the "someid" block.

  // Change the title of the "someid" block.
  $build['#title'] = t('New title of the block');
}

/**
 * Perform alterations to a specific block instance.
 *
 * Modules can implement hook_block_view_NAME_alter() to modify a specific block
 * instance, rather than implementing hook_block_view_alter().
 *
 * @param array $build
 *   A renderable array of data, as returned from the build() implementation of
 *   the plugin that defined the block:
 *   - #title: The default localized title of the block.
 * @param \Drupal\block\BlockPluginInterface $block
 *   The block instance.
 *
 * @todo NAME is ambiguous, and so is the example here. Use a more specific
 *   example to illustrate what the block instance name will look like, and
 *   also illustrate how it is different from hook_block_view_ID().
 *
 * @see hook_block_view_alter()
 * @see hook_block_view_ID_alter()
 */
function hook_block_view_NAME_alter(array &$build, \Drupal\block\BlockPluginInterface $block) {
  // This code will only run for a specific block instance. For example, if NAME
  // in the function definition above is set to "someid", the code will only run
  // on the "someid" block.

  // Change the title of the "someid" block.
  $build['#title'] = t('New title of the block');
}

/**
 * Define access for a specific block instance.
 *
 * This hook is invoked by the access methods of the block plugin system and
 * should be used to alter the block access rules defined by a module from
 * another module.
 *
 * @param \Drupal\block\Plugin\Core\Entity\Block $block
 *   The block instance.
 *
 * @return bool
 *   TRUE will allow access whereas FALSE will deny access to the block.
 *
 * @see \Drupal\block\BlockBase::access()
 * @see \Drupal\block\BlockBase::blockAccess()
 */
function hook_block_access(\Drupal\block\Plugin\Core\Entity\Block $block) {
  // Example code that would prevent displaying the 'Powered by Drupal' block in
  // a region different than the footer.
  if ($block->get('plugin') == 'system_powered_by_block' && $block->get('region') != 'footer') {
    return FALSE;
  }
}

/**
 * @} End of "addtogroup hooks".
 */
