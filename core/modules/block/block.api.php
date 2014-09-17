<?php

/**
 * @file
 * Hooks provided by the Block module.
 */

use Drupal\Core\Access\AccessResult;

/**
 * @defgroup block_api Block API
 * @{
 * Information about the classes and interfaces that make up the Block API.
 *
 * Blocks are a combination of a configuration entity and a plugin. The
 * configuration entity stores placement information (theme, region, weight) and
 * any other configuration that is specific to the block. The block plugin does
 * the work of rendering the block's content for display.
 *
 * To define a block in a module you need to:
 * - Define a Block plugin by creating a new class that implements the
 *   \Drupal\Core\Block\BlockPluginInterface, in namespace Plugin\Block under your
 *   module namespace. For more information about creating plugins, see the
 *   @link plugin_api Plugin API topic. @endlink
 * - Usually you will want to extend the \Drupal\Core\Block\BlockBase class, which
 *   provides a common configuration form and utility methods for getting and
 *   setting configuration in the block configuration entity.
 * - Block plugins use the annotations defined by
 *   \Drupal\Core\Block\Annotation\Block. See the
 *   @link annotation Annotations topic @endlink for more information about
 *   annotations.
 *
 * The Block API also makes use of Condition plugins, for conditional block
 * placement. Condition plugins have interface
 * \Drupal\Core\Condition\ConditionInterface, base class
 * \Drupal\Core\Condition\ConditionPluginBase, and go in plugin namespace
 * Plugin\Condition. Again, see the Plugin API and Annotations topics for
 * details of how to create a plugin class and annotate it.
 *
 * There are also several block-related hooks, which allow you to affect
 * the content and access permissions for blocks:
 * - hook_block_view_alter()
 * - hook_block_view_BASE_BLOCK_ID_alter()
 * - hook_block_access()
 *
 * Further information and examples:
 * - \Drupal\system\Plugin\Block\SystemPoweredByBlock provides a simple example
 *   of defining a block.
 * - \Drupal\user\Plugin\Condition\UserRole is a straightforward example of a
 *   block placement condition plugin.
 * - \Drupal\book\Plugin\Block\BookNavigationBlock is an example of a block with
 *   a custom configuration form.
 * - For a more in-depth discussion of the Block API see
 *   https://drupal.org/developing/api/8/block_api
 * - The Examples for Developers project also provides a Block example in
 *   https://drupal.org/project/examples.
 * @}
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the result of \Drupal\Core\Block\BlockBase::build().
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
 * @param \Drupal\Core\Block\BlockPluginInterface $block
 *   The block plugin instance.
 *
 * @see hook_block_view_BASE_BLOCK_ID_alter()
 * @see entity_crud
 *
 * @ingroup block_api
 */
function hook_block_view_alter(array &$build, \Drupal\Core\Block\BlockPluginInterface $block) {
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
 * @param \Drupal\Core\Block\BlockPluginInterface $block
 *   The block plugin instance.
 *
 * @see hook_block_view_alter()
 * @see entity_crud
 *
 * @ingroup block_api
 */
function hook_block_view_BASE_BLOCK_ID_alter(array &$build, \Drupal\Core\Block\BlockPluginInterface $block) {
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
 * @return \Drupal\Core\Access\AccessResultInterface
 *   The access result. If all implementations of this hook return
 *   AccessResultInterface objects whose value is !isAllowed() and
 *   !isForbidden(), then default access rules from
 *   \Drupal\block\BlockAccessControlHandler::checkAccess() are used.
 *
 * @see \Drupal\Core\Entity\EntityAccessControlHandler::access()
 * @see \Drupal\block\BlockAccessControlHandler::checkAccess()
 * @ingroup block_api
 */
function hook_block_access(\Drupal\block\Entity\Block $block, $operation, \Drupal\user\Entity\User $account, $langcode) {
  // Example code that would prevent displaying the 'Powered by Drupal' block in
  // a region different than the footer.
  if ($operation == 'view' && $block->get('plugin') == 'system_powered_by_block') {
    return AccessResult::forbiddenIf($block->get('region') != 'footer')->cacheUntilEntityChanges($block);
  }

  // No opinion.
  return AccessResult::create();
}

/**
 * @} End of "addtogroup hooks".
 */
