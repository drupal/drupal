<?php
// $Id: block.api.php,v 1.11 2010/04/22 09:12:35 webchick Exp $

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
 * Any module can export a block (or blocks) to be displayed by defining
 * the _block hook. This hook is called by theme.inc to display a block,
 * and also by block.module to procure the list of available blocks.
 *
 * @return
 *   An associative array whose keys define the $delta
 *   for each block and whose values contain the block descriptions. Each
 *   block description is itself an associative array, with the following
 *   key-value pairs:
 *   - 'info': (required) The human-readable name of the block.
 *   - 'cache': A bitmask of flags describing how the block should behave with
 *     respect to block caching. The following shortcut bitmasks are provided
 *     as constants in common.inc:
 *     - DRUPAL_CACHE_PER_ROLE (default): The block can change depending on the
 *       roles the user viewing the page belongs to.
 *     - DRUPAL_CACHE_PER_USER: The block can change depending on the user
 *       viewing the page. This setting can be resource-consuming for sites
 *       with large number of users, and should only be used when
 *       DRUPAL_CACHE_PER_ROLE is not sufficient.
 *     - DRUPAL_CACHE_PER_PAGE: The block can change depending on the page
 *       being viewed.
 *     - DRUPAL_CACHE_GLOBAL: The block is the same for every user on every
 *       page where it is visible.
 *     - DRUPAL_NO_CACHE: The block should not get cached.
 *   - 'weight', 'status', 'region', 'visibility', 'pages':
 *     You can give your blocks an explicit weight, enable them, limit them to
 *     given pages, etc. These settings will be registered when the block is first
 *     loaded at admin/block, and from there can be changed manually via block
 *     administration.
 *     Note that if you set a region that isn't available in a given theme, the
 *     block will be registered instead to that theme's default region (the first
 *     item in the _regions array).
 *
 * After completing your blocks, do not forget to enable them in the
 * block admin menu.
 *
 * For a detailed usage example, see block_example.module.
 */
function hook_block_info() {
  $blocks['exciting'] = array(
    'info' => t('An exciting block provided by Mymodule.'),
    'weight' => 0,
    'status' => 1,
    'region' => 'sidebar_first',
    // DRUPAL_CACHE_PER_ROLE will be assumed for block 0.
  );

  $blocks['amazing'] = array(
    'info' => t('An amazing block provided by Mymodule.'),
    'cache' => DRUPAL_CACHE_PER_ROLE | DRUPAL_CACHE_PER_PAGE,
  );

  return $blocks;
}

/**
 * Change block definition before saving to the database.
 *
 * @param $blocks
 *   A multidimensional array of blocks keyed by the defining module and delta
 *   the value is a block as seen in hook_block_info(). This hook is fired
 *   after the blocks are collected from hook_block_info() and the database,
 *   right before saving back to the database.
 * @param $theme
 *   The theme these blocks belong to.
 * @param $code_blocks
 *   The blocks as defined in hook_block_info before overwritten by the
 *   database data.
 */
function hook_block_info_alter(&$blocks, $theme, $code_blocks) {
  // Disable the login block.
  $blocks['user']['login']['status'] = 0;
}

/**
 * Configuration form for the block.
 *
 * @param $delta
 *   Which block to return. This is a descriptive string used to identify
 *   blocks within each module and also within the theme system.
 *   The $delta for each block is defined within the array that your module
 *   returns when the hook_block_info() implementation is called.
 * @return
 *   Optionally return the configuration form.
 *
 * For a detailed usage example, see block_example.module.
 */
function hook_block_configure($delta = '') {
  if ($delta == 'exciting') {
    $form['items'] = array(
      '#type' => 'select',
      '#title' => t('Number of items'),
      '#default_value' => variable_get('mymodule_block_items', 0),
      '#options' => array('1', '2', '3'),
    );
    return $form;
  }
}

/**
 * Save the configuration options.
 *
 * @param $delta
 *   Which block to save the settings for. This is a descriptive string used
 *   to identify blocks within each module and also within the theme system.
 *   The $delta for each block is defined within the array that your module
 *   returns when the hook_block_info() implementation is called.
 * @param $edit
 *   The submitted form data from the configuration form.
 *
 * For a detailed usage example, see block_example.module.
 */
function hook_block_save($delta = '', $edit = array()) {
  if ($delta == 'exciting') {
    variable_set('mymodule_block_items', $edit['items']);
  }
}

/**
 * Process the block when enabled in a region in order to view its contents.
 *
 * @param $delta
 *   Which block to return. This is a descriptive string used to identify
 *   blocks within each module and also within the theme system.
 *   The $delta for each block is defined within the array that your module
 *   returns when the hook_block_info() implementation is called.
 * @return
 *   An array which must define a 'subject' element and a 'content' element
 *   defining the block indexed by $delta.
 *
 * The functions mymodule_display_block_exciting and _amazing, as used in the
 * example, should of course be defined somewhere in your module and return the
 * content you want to display to your users. If the "content" element is empty,
 * no block will be displayed even if "subject" is present.
 *
 * For a detailed usage example, see block_example.module.
 */
function hook_block_view($delta = '') {
  switch ($delta) {
    case 'exciting':
      $block = array(
        'subject' => t('Default title of the exciting block'),
        'content' => mymodule_display_block_exciting(),
      );
      break;

    case 'amazing':
      $block = array(
        'subject' => t('Default title of the amazing block'),
        'content' => mymodule_display_block_amazing(),
      );
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
 *   An array of data, as returned from the hook_block_view() implementation of
 *   the module that defined the block:
 *   - subject: The localized title of the block.
 *   - content: Either a string or a renderable array representing the content
 *     of the block. You should check that the content is an array before trying
 *     to modify parts of the renderable structure.
 * @param $block
 *   The block object, as loaded from the database, having the main properties:
 *   - module: The name of the module that defined the block.
 *   - delta: The identifier for the block within that module, as defined within
 *     hook_block_info().
 *
 * @see hook_block_view_alter()
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
 * Note that this hook fires before hook_block_view_alter(). Therefore, all
 * implementations of hook_block_view_MODULE_DELTA_alter() will run before all
 * implementations of hook_block_view_alter(), regardless of the module order.
 *
 * @param $data
 *   An array of data, as returned from the hook_block_view() implementation of
 *   the module that defined the block:
 *   - subject: The localized title of the block.
 *   - content: Either a string or a renderable array representing the content
 *     of the block. You should check that the content is an array before trying
 *     to modify parts of the renderable structure.
 * @param $block
 *   The block object, as loaded from the database, having the main properties:
 *   - module: The name of the module that defined the block.
 *   - delta: The identifier for the block within that module, as defined within
 *     hook_block_info().
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
 * block list contains the block definitions not the rendered blocks. The blocks
 * are rendered after the modules have had a chance to manipulate the block
 * list.
 * Alternatively you can set $block->content here, which will override the
 * content of the block and prevent hook_block_view() from running.
 *
 * @param $blocks
 *   An array of $blocks, keyed by $bid
 *
 * This example shows how to achieve language specific visibility setting for
 * blocks.
 */
function hook_block_list_alter(&$blocks) {
  global $language, $theme_key;

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
