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
 * Define all blocks provided by the module.
 *
 * This hook declares to Drupal what blocks are provided by your module and can
 * optionally specify initial block configuration settings.
 *
 * In hook_block_info(), each block your module provides is given a unique
 * identifier referred to as "delta" (the array key in the return value). Delta
 * values only need to be unique within your module, and they are used in the
 * following ways:
 * - Passed into the other block hooks in your module as an argument to identify
 *   the block being configured or viewed.
 * - Used to construct the default HTML ID of "block-MODULE-DELTA" applied to
 *   each block when it is rendered. This ID may then be used for CSS styling or
 *   JavaScript programming.
 * - Used to define a theming template suggestion of block__MODULE__DELTA, for
 *   advanced theming possibilities.
 * - Used by other modules to identify your block in hook_block_info_alter() and
 *   other alter hooks.
 * The values of delta can be strings or numbers, but because of the uses above
 * it is preferable to use descriptive strings whenever possible, and only use a
 * numeric identifier if you have to (for instance if your module allows users
 * to create several similar blocks that you identify within your module code
 * with numeric IDs). The maximum length for delta values is 32 bytes.
 *
 * @return
 *   An associative array whose keys define the delta for each block and whose
 *   values contain the block descriptions. Each block description is itself an
 *   associative array, with the following key-value pairs:
 *   - info: (required) The human-readable administrative name of the block.
 *     This is used to identify the block on administration screens, and is not
 *     displayed to non-administrative users.
 *   - cache: (optional) A bitmask describing what kind of caching is
 *     appropriate for the block. Drupal provides the following bitmask
 *     constants for defining cache granularity:
 *     - DRUPAL_CACHE_PER_ROLE (default): The block can change depending on the
 *       roles the user viewing the page belongs to.
 *     - DRUPAL_CACHE_PER_USER: The block can change depending on the user
 *       viewing the page. This setting can be resource-consuming for sites with
 *       large number of users, and should only be used when
 *       DRUPAL_CACHE_PER_ROLE is not sufficient.
 *     - DRUPAL_CACHE_PER_PAGE: The block can change depending on the page being
 *       viewed.
 *     - DRUPAL_CACHE_GLOBAL: The block is the same for every user on every page
 *       where it is visible.
 *     - DRUPAL_CACHE_CUSTOM: The module implements its own caching system.
 *     - DRUPAL_NO_CACHE: The block should not get cached.
 *   - properties: (optional) Array of additional metadata to add to the block.
 *     Common properties include:
 *     - administrative: Boolean that categorizes this block as usable in an
 *       administrative context. This might include blocks that help an
 *       administrator approve/deny comments, or view recently created user
 *       accounts.
 *   - weight: (optional) Initial value for the ordering weight of this block.
 *     Most modules do not provide an initial value, and any value provided can
 *     be modified by a user on the block configuration screen.
 *   - status: (optional) Initial value for block enabled status. (1 = enabled,
 *     0 = disabled). Most modules do not provide an initial value, and any
 *     value provided can be modified by a user on the block configuration
 *     screen.
 *   - region: (optional) Initial value for theme region within which this
 *     block is set. Most modules do not provide an initial value, and any value
 *     provided can be modified by a user on the block configuration screen.
 *     Note: If you set a region that isn't available in the currently enabled
 *     theme, the block will be disabled.
 *   - visibility: (optional) Initial value for the visibility flag, which tells
 *     how to interpret the 'pages' value. Possible values are:
 *     - BLOCK_VISIBILITY_NOTLISTED: Show on all pages except listed pages.
 *       'pages' lists the paths where the block should not be shown.
 *     - BLOCK_VISIBILITY_LISTED: Show only on listed pages. 'pages' lists the
 *       paths where the block should be shown.
 *     - BLOCK_VISIBILITY_PHP: Use custom PHP code to determine visibility.
 *       'pages' gives the PHP code to use.
 *     Most modules do not provide an initial value for 'visibility' or 'pages',
 *     and any value provided can be modified by a user on the block
 *     configuration screen.
 *   - pages: (optional) See 'visibility' above. A string that contains one or
 *     more page paths separated by "\n", "\r", or "\r\n" when 'visibility' is
 *     set to BLOCK_VISIBILITY_NOTLISTED or BLOCK_VISIBILITY_LISTED (example:
 *     "<front>\nnode/1"), or custom PHP code when 'visibility' is set to
 *     BLOCK_VISIBILITY_PHP. Paths may use '*' as a wildcard (matching any
 *     number of characters); '<front>' designates the site's front page. For
 *     BLOCK_VISIBILITY_PHP, the PHP code's return value should be TRUE if the
 *     block is to be made visible or FALSE if the block should not be visible.
 *
 * For a detailed usage example, see block_example.module.
 *
 * @see hook_block_configure()
 * @see hook_block_save()
 * @see hook_block_view()
 * @see hook_block_info_alter()
 */
function hook_block_info() {
  // This example comes from node.module.
  $blocks['syndicate'] = array(
    'info' => t('Syndicate'),
    'cache' => DRUPAL_NO_CACHE
  );

  $blocks['recent'] = array(
    'info' => t('Recent content'),
    // DRUPAL_CACHE_PER_ROLE will be assumed.
  );

  return $blocks;
}

/**
 * Change block definition before saving to the database.
 *
 * @param $blocks
 *   A multidimensional array of blocks keyed by the defining module and delta;
 *   the values are blocks returned by hook_block_info(). This hook is fired
 *   after the blocks are collected from hook_block_info() and the database,
 *   right before saving back to the database.
 * @param $theme
 *   The theme these blocks belong to.
 * @param $code_blocks
 *   The blocks as defined in hook_block_info() before being overwritten by the
 *   database data.
 *
 * @see hook_block_info()
 */
function hook_block_info_alter(&$blocks, $theme, $code_blocks) {
  // Disable the login block.
  $blocks['user']['login']['status'] = 0;
}

/**
 * Define a configuration form for a block.
 *
 * @param $delta
 *   Which block is being configured. This is a unique identifier for the block
 *   within the module, defined in hook_block_info().
 *
 * @return
 *   A configuration form, if one is needed for your block beyond the standard
 *   elements that the block module provides (block title, visibility, etc.).
 *
 * For a detailed usage example, see block_example.module.
 *
 * @see hook_block_info()
 * @see hook_block_save()
 */
function hook_block_configure($delta = '') {
  // This example comes from node.module.
  $form = array();
  if ($delta == 'recent') {
    $form['node_recent_block_count'] = array(
      '#type' => 'select',
      '#title' => t('Number of recent content items to display'),
      '#default_value' => variable_get('node_recent_block_count', 10),
      '#options' => drupal_map_assoc(array(2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 25, 30)),
    );
  }
  return $form;
}

/**
 * Save the configuration options from hook_block_configure().
 *
 * This hook allows you to save the block-specific configuration settings
 * defined within your hook_block_configure().
 *
 * @param $delta
 *   Which block is being configured. This is a unique identifier for the block
 *   within the module, defined in hook_block_info().
 * @param $edit
 *   The submitted form data from the configuration form.
 *
 * For a detailed usage example, see block_example.module.
 *
 * @see hook_block_configure()
 * @see hook_block_info()
 */
function hook_block_save($delta = '', $edit = array()) {
  // This example comes from node.module.
  if ($delta == 'recent') {
    variable_set('node_recent_block_count', $edit['node_recent_block_count']);
  }
}

/**
 * Return a rendered or renderable view of a block.
 *
 * @param $delta
 *   Which block to render. This is a unique identifier for the block
 *   within the module, defined in hook_block_info().
 *
 * @return
 *   Either an empty array so the block will not be shown or an array containing
 *   the following elements:
 *   - subject: The default localized title of the block. If the block does not
 *     have a default title, this should be set to NULL.
 *   - content: The content of the block's body. This may be a renderable array
 *     (preferable) or a string containing rendered HTML content. If the content
 *     is empty the block will not be shown.
 *
 * For a detailed usage example, see block_example.module.
 *
 * @see hook_block_info()
 * @see hook_block_view_alter()
 * @see hook_block_view_MODULE_DELTA_alter()
 */
function hook_block_view($delta = '') {
  // This example is adapted from node.module.
  $block = array();

  switch ($delta) {
    case 'syndicate':
      $block['subject'] = t('Syndicate');
      $block['content'] = array(
        '#theme' => 'feed_icon',
        '#url' => 'rss.xml',
        '#title' => t('Syndicate'),
      );
      break;

    case 'recent':
      if (user_access('access content')) {
        $block['subject'] = t('Recent content');
        if ($nodes = node_get_recent(variable_get('node_recent_block_count', 10))) {
          $block['content'] = array(
            '#theme' => 'node_recent_block',
            '#nodes' => $nodes,
          );
        } else {
          $block['content'] = t('No content available.');
        }
      }
      break;
  }
  return $block;
}

/**
 * Perform alterations to the content of a block.
 *
 * This hook allows you to modify any data returned by hook_block_view().
 *
 * Note that instead of hook_block_view_alter(), which is called for all
 * blocks, you can also use hook_block_view_MODULE_DELTA_alter() to alter a
 * specific block.
 *
 * @param $data
 *   The data as returned from the hook_block_view() implementation of the
 *   module that defined the block. This could be an empty array or NULL value
 *   (if the block is empty) or an array containing:
 *   - subject: The default localized title of the block.
 *   - content: Either a string or a renderable array representing the content
 *     of the block. You should check that the content is an array before trying
 *     to modify parts of the renderable structure.
 * @param $block
 *   The block object, as loaded from the database, having the main properties:
 *   - module: The name of the module that defined the block.
 *   - delta: The unique identifier for the block within that module, as defined
 *     in hook_block_info().
 *
 * @see hook_block_view_MODULE_DELTA_alter()
 * @see hook_block_view()
 */
function hook_block_view_alter(&$data, $block) {
  // Remove the contextual links on all blocks that provide them.
  if (is_array($data['content']) && isset($data['content']['#contextual_links'])) {
    unset($data['content']['#contextual_links']);
  }
  // Add a theme wrapper function defined by the current module to all blocks
  // provided by the "somemodule" module.
  if (is_array($data['content']) && $block->module == 'somemodule') {
    $data['content']['#theme_wrappers'][] = 'mymodule_special_block';
  }
}

/**
 * Perform alterations to a specific block.
 *
 * Modules can implement hook_block_view_MODULE_DELTA_alter() to modify a
 * specific block, rather than implementing hook_block_view_alter().
 *
 * @param $data
 *   The data as returned from the hook_block_view() implementation of the
 *   module that defined the block. This could be an empty array or NULL value
 *   (if the block is empty) or an array containing:
 *   - subject: The localized title of the block.
 *   - content: Either a string or a renderable array representing the content
 *     of the block. You should check that the content is an array before trying
 *     to modify parts of the renderable structure.
 * @param $block
 *   The block object, as loaded from the database, having the main properties:
 *   - module: The name of the module that defined the block.
 *   - delta: The unique identifier for the block within that module, as defined
 *     in hook_block_info().
 *
 * @see hook_block_view_alter()
 * @see hook_block_view()
 */
function hook_block_view_MODULE_DELTA_alter(&$data, $block) {
  // This code will only run for a specific block. For example, if MODULE_DELTA
  // in the function definition above is set to "mymodule_somedelta", the code
  // will only run on the "somedelta" block provided by the "mymodule" module.

  // Change the title of the "somedelta" block provided by the "mymodule"
  // module.
  $data['subject'] = t('New title of the block');
}

/**
 * Act on blocks prior to rendering.
 *
 * This hook allows you to add, remove or modify blocks in the block list. The
 * block list contains the block definitions, not the rendered blocks. The
 * blocks are rendered after the modules have had a chance to manipulate the
 * block list.
 *
 * You can also set $block->content here, which will override the content of the
 * block and prevent hook_block_view() from running.
 *
 * @param $blocks
 *   An array of $blocks, keyed by the block ID.
 */
function hook_block_list_alter(&$blocks) {
  global $language, $theme_key;

  // This example shows how to achieve language specific visibility setting for
  // blocks.

  $result = db_query('SELECT module, delta, language FROM {my_table}');
  $block_languages = array();
  foreach ($result as $record) {
    $block_languages[$record->module][$record->delta][$record->language] = TRUE;
  }

  foreach ($blocks as $key => $block) {
    // Any module using this alter should inspect the data before changing it,
    // to ensure it is what they expect.
    if (!isset($block->theme) || !isset($block->status) || $block->theme != $theme_key || $block->status != 1) {
      // This block was added by a contrib module, leave it in the list.
      continue;
    }

    if (!isset($block_languages[$block->module][$block->delta])) {
      // No language setting for this block, leave it in the list.
      continue;
    }

    if (!isset($block_languages[$block->module][$block->delta][$language->language])) {
      // This block should not be displayed with the active language, remove
      // from the list.
      unset($blocks[$key]);
    }
  }
}

/**
 * @} End of "addtogroup hooks".
 */
