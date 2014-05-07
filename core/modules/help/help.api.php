<?php

/**
 * @file
 * Hooks provided by the Help module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Provide online user help.
 *
 * By implementing hook_help(), a module can make documentation available to
 * the user for the module as a whole, or for specific paths.  Help for
 * developers should usually be provided via function header comments in the
 * code, or in special API example files.
 *
 * For a detailed usage example, see page_example.module.
 *
 * @param string $route_name
 *   For a specific page, use the route name as identified in the module's
 *   routing.yml file. For the help overview page, the route name will be in the
 *   form of "help.page.$modulename".
 * @param \Symfony\Component\HttpFoundation\Request $request
 *   The current request.
 *
 * @return string
 *   A localized string containing the help text.
 */
function hook_help($route_name, \Symfony\Component\HttpFoundation\Request $request) {
  switch ($route_name) {
    // Main module help for the block module.
    case 'help.page.block':
      return '<p>' . t('Blocks are boxes of content rendered into an area, or region, of a web page. The default theme Bartik, for example, implements the regions "Sidebar first", "Sidebar second", "Featured", "Content", "Header", "Footer", etc., and a block may appear in any one of these areas. The <a href="@blocks">blocks administration page</a> provides a drag-and-drop interface for assigning a block to a region, and for controlling the order of blocks within regions.', array('@blocks' => url('admin/structure/block'))) . '</p>';

    // Help for another path in the block module.
    case 'block.admin_display':
      return '<p>' . t('This page provides a drag-and-drop interface for assigning a block to a region, and for controlling the order of blocks within regions. Since not all themes implement the same regions, or display regions in the same way, blocks are positioned on a per-theme basis. Remember that your changes will not be saved until you click the <em>Save blocks</em> button at the bottom of the page.') . '</p>';
  }
}

/**
 * @} End of "addtogroup hooks".
 */
