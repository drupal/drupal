<?php

/**
 * @file
 * Hooks for the Help system.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Provide online user help.
 *
 * By implementing hook_help(), a module can make documentation available to
 * the user for the module as a whole, or for specific pages. Help for
 * developers should usually be provided via function header comments in the
 * code, or in special API example files.
 *
 * The page-specific help information provided by this hook appears in the
 * Help block (provided by the core Help module), if the block is displayed on
 * that page. The module overview help information is displayed by the Help
 * module. It can be accessed from the page at admin/help or from the Extend
 * Extend page.
 *
 * For detailed usage examples of:
 * - Module overview help, see content_translation_help(). Module overview
 *   help should follow
 *   @link https://drupal.org/node/632280 the standard help template. @endlink
 * - Page-specific help using only routes, see book_help().
 * - Page-specific help using routes and $request, see block_help().
 *
 * @param string $route_name
 *   For page-specific help, use the route name as identified in the
 *   module's routing.yml file. For module overview help, the route name
 *   will be in the form of "help.page.$modulename".
 * @param Drupal\Core\Routing\RouteMatchInterface $route_match
 *   The current route match. This can be used to generate different help
 *   output for different pages that share the same route.
 *
 * @return string
 *   A localized string containing the help text.
 */
function hook_help($route_name, \Drupal\Core\Routing\RouteMatchInterface $route_match) {
  switch ($route_name) {
    // Main module help for the block module.
    case 'help.page.block':
      return '<p>' . t('Blocks are boxes of content rendered into an area, or region, of a web page. The default theme Bartik, for example, implements the regions "Sidebar first", "Sidebar second", "Featured", "Content", "Header", "Footer", etc., and a block may appear in any one of these areas. The <a href="!blocks">blocks administration page</a> provides a drag-and-drop interface for assigning a block to a region, and for controlling the order of blocks within regions.', array('!blocks' => \Drupal::url('block.admin_display'))) . '</p>';

    // Help for another path in the block module.
    case 'block.admin_display':
      return '<p>' . t('This page provides a drag-and-drop interface for assigning a block to a region, and for controlling the order of blocks within regions. Since not all themes implement the same regions, or display regions in the same way, blocks are positioned on a per-theme basis. Remember that your changes will not be saved until you click the <em>Save blocks</em> button at the bottom of the page.') . '</p>';
  }
}

/**
 * @} End of "addtogroup hooks".
 */
