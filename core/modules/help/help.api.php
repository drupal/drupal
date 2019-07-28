<?php

/**
 * @file
 * Hooks for the Help system.
 */

use Drupal\Core\Url;

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
 * module. It can be accessed from the page at /admin/help or from the Extend
 * page. If a module implements hook_help() the help system expects module
 * overview help to be provided.
 *
 * For detailed usage examples of:
 * - Module overview help, see content_translation_help(). Module overview
 *   help should follow
 *   @link https://www.drupal.org/node/632280 the standard help template. @endlink
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
 * @return string|array
 *   A render array, localized string, or object that can be rendered into
 *   a string, containing the help text.
 */
function hook_help($route_name, \Drupal\Core\Routing\RouteMatchInterface $route_match) {
  switch ($route_name) {
    // Main module help for the block module.
    case 'help.page.block':
      return '<p>' . t('Blocks are boxes of content rendered into an area, or region, of a web page. The default theme Bartik, for example, implements the regions "Sidebar first", "Sidebar second", "Featured", "Content", "Header", "Footer", etc., and a block may appear in any one of these areas. The <a href=":blocks">blocks administration page</a> provides a drag-and-drop interface for assigning a block to a region, and for controlling the order of blocks within regions.', [':blocks' => Url::fromRoute('block.admin_display')->toString()]) . '</p>';

    // Help for another path in the block module.
    case 'block.admin_display':
      return '<p>' . t('This page provides a drag-and-drop interface for assigning a block to a region, and for controlling the order of blocks within regions. Since not all themes implement the same regions, or display regions in the same way, blocks are positioned on a per-theme basis. Remember that your changes will not be saved until you click the <em>Save blocks</em> button at the bottom of the page.') . '</p>';
  }
}

/**
 * Perform alterations on help page section plugin definitions.
 *
 * Sections for the page at /admin/help are provided by plugins. This hook
 * allows modules to alter the plugin definitions.
 *
 * @param array $info
 *   Array of plugin information exposed by hook page section plugins, altered
 *   by reference.
 *
 * @see \Drupal\help\HelpSectionPluginInterface
 * @see \Drupal\help\Annotation\HelpSection
 * @see \Drupal\help\HelpSectionManager
 */
function hook_help_section_info_alter(&$info) {
  // Alter the header for the module overviews section.
  $info['hook_help']['header'] = t('Overviews of modules');
}

/**
 * @} End of "addtogroup hooks".
 */
