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
 * By implementing hook_help(), a module can make documentation
 * available to the engine or to other modules. All user help should be
 * returned using this hook; developer help should be provided with
 * Doxygen/api.module comments.
 *
 * @param $path
 *   A Drupal menu router path the help is being requested for, e.g.
 *   admin/node or user/edit. If the router path includes a % wildcard,
 *   then this will appear in the path - for example all node pages will
 *   have the path node/% or node/%/view.
 *   Also recognizes special descriptors after a "#" sign. Some examples:
 *   - admin/help#modulename
 *     The module's help text, displayed on the admin/help page and through
 *     the module's individual help link.
 *   - user/help#modulename
 *     The help for a distributed authorization module (if applicable).
 * @param $arg
 *   An array that corresponds to the return of the arg() function - if a module
 *   needs to provide help for a page with additional parameters after the
 *   Drupal path or help for a specific value for a wildcard in the path, then
 *   the values in this array can be referenced. For example you could provide
 *   help for user/1 by looking for the path user/% and $arg[1] == '1'. This
 *   array should always be used rather than directly invoking arg(). Note that
 *   depending on which module is invoking hook_help, $arg may contain only,
 *   empty strings. Regardless, $arg[0] to $arg[11] will always be set.
 * @return
 *   A localized string containing the help text. Every web link, l(), or
 *   url() must be replaced with %something and put into the final t()
 *   call:
 *   $output .= 'A role defines a group of users that have certain
 *     privileges as defined in %permission.';
 *   $output = t($output, array('%permission' => l(t('user permissions'),
 *     'admin/settings/permission')));
 *
 * For a detailed usage example, see page_example.module.
 */
function hook_help($path, $arg) {
  switch ($path) {
    case 'admin/help#block':
      return '<p>' . t('Blocks are boxes of content that may be rendered into certain regions of your web pages, for example, into sidebars. Blocks are usually generated automatically by modules (e.g., Recent Forum Topics), but administrators can also define custom blocks.') . '</p>';

    case 'admin/structure/block':
      return t('<p>Blocks are boxes of content that may be rendered into certain regions of your web pages, for example, into sidebars. They are usually generated automatically by modules, but administrators can create blocks manually.</p>
<p>If you want certain blocks to disable themselves temporarily during high server loads, check the "Throttle" box. You can configure the auto-throttle on the <a href="@throttle">throttle configuration page</a> after having enabled the throttle module.</p>
<p>You can configure the behavior of each block (for example, specifying on which pages and for what users it will appear) by clicking the "configure" link for each block.</p>', array('@throttle' => url('admin/settings/throttle')));
  }
}

/**
 * @} End of "addtogroup hooks".
 */
