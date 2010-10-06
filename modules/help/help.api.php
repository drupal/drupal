<?php
// $Id$

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
 * @param $path
 *   The router menu path, as defined in hook_menu(), for the help that is
 *   being requested; e.g., 'admin/people' or 'user/register'.  If the router
 *   path includes a wildcard, then this will appear in $path as %, even if it
 *   is a named %autoloader wildcard in the hook_menu() implementation; for
 *   example, node pages would have $path equal to 'node/%' or 'node/%/view'.
 *   To provide a help page for a whole module with a listing on admin/help,
 *   your hook implementation should match a path with a special descriptor
 *   after a "#" sign:
 *     'admin/help#modulename'
 *       The main module help text, displayed on the admin/help/modulename
 *       page and linked to from the admin/help page.
 * @param $arg
 *   An array that corresponds to the return value of the arg() function, for
 *   modules that want to provide help that is specific to certain values
 *   of wildcards in $path. For example, you could provide help for the path
 *   'user/1' by looking for the path 'user/%' and $arg[1] == '1'. This given
 *   array should always be used rather than directly invoking arg(), because
 *   your hook implementation may be called for other purposes besides building
 *   the current page's help. Note that depending on which module is invoking
 *   hook_help, $arg may contain only empty strings. Regardless, $arg[0] to
 *   $arg[11] will always be set.
 * @return
 *   A localized string containing the help text.
 */
function hook_help($path, $arg) {
  switch ($path) {
    // Main module help for the block module
    case 'admin/help#block':
      return '<p>' . t('Blocks are boxes of content rendered into an area, or region, of a web page. The default theme Bartik, for example, implements the regions "Sidebar first", "Sidebar second", "Featured", "Content", "Header", "Footer", etc., and a block may appear in any one of these areas. The <a href="@blocks">blocks administration page</a> provides a drag-and-drop interface for assigning a block to a region, and for controlling the order of blocks within regions.', array('@blocks' => url('admin/structure/block'))) . '</p>';

    // Help for another path in the block module
    case 'admin/structure/block':
      return '<p>' . t('This page provides a drag-and-drop interface for assigning a block to a region, and for controlling the order of blocks within regions. Since not all themes implement the same regions, or display regions in the same way, blocks are positioned on a per-theme basis. Remember that your changes will not be saved until you click the <em>Save blocks</em> button at the bottom of the page.') . '</p>';
  }
}

/**
 * @} End of "addtogroup hooks".
 */
