<?php
// $Id$

/**
 * @file
 * Hooks provided by the Block module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * List of all blocks defined by the module.
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
 *     as constants in block.module:
 *     - BLOCK_CACHE_PER_ROLE (default): The block can change depending on the
 *       roles the user viewing the page belongs to.
 *     - BLOCK_CACHE_PER_USER: The block can change depending on the user
 *       viewing the page. This setting can be resource-consuming for sites
 *       with large number of users, and should only be used when
 *       BLOCK_CACHE_PER_ROLE is not sufficient.
 *     - BLOCK_CACHE_PER_PAGE: The block can change depending on the page
 *       being viewed.
 *     - BLOCK_CACHE_GLOBAL: The block is the same for every user on every
 *       page where it is visible.
 *     - BLOCK_NO_CACHE: The block should not get cached.
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
function hook_block_list() {
  $blocks['exciting'] = array(
    'info' => t('An exciting block provided by Mymodule.'),
    'weight' => 0,
    'status' => 1,
    'region' => 'sidebar_first',
    // BLOCK_CACHE_PER_ROLE will be assumed for block 0.
  );

  $blocks['amazing'] = array(
    'info' => t('An amazing block provided by Mymodule.'),
    'cache' => BLOCK_CACHE_PER_ROLE | BLOCK_CACHE_PER_PAGE,
  );

  return $blocks;
}

/**
 * Configuration form for the block.
 *
 * @param $delta
 *   Which block to return. This is a descriptive string used to identify
 *   blocks within each module and also within the theme system.
 *   The $delta for each block is defined within the array that your module
 *   returns when the hook_block_list() implementation is called.
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
 *   returns when the hook_block_list() implementation is called.
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
 *   returns when the hook_block_list() implementation is called.
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
    if ($block->theme != $theme_key || $block->status != 1) {
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
