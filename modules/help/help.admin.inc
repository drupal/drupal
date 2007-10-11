<?php
// $Id: help.admin.inc,v 1.2 2007/10/11 09:51:28 goba Exp $

/**
 * @file
 * Admin page callbacks for the help module.
 */

/**
 * Menu callback; prints a page listing a glossary of Drupal terminology.
 */
function help_main() {
  // Add CSS
  drupal_add_css(drupal_get_path('module', 'help') .'/help.css', 'module', 'all', FALSE);

  $output = t('
  <h2>Help topics</h2>
  <p>Help is available on the following items:</p>
  !help_pages
  <h2>Glossary of Drupal terminology</h2>
  <dl>
   <dt>Block</dt><dd>A small box containing information or content placed in a region of a web page (e.g. in a sidebar, below or above the content, or in any other region the current theme allows).</dd>
   <dt>Comment</dt><dd>Text attached to a post intended to clarify, explain, criticize, or express an opinion on the original post.</dd>
   <dt>Node</dt><dd>The basic unit of content in Drupal, often referred to as a "post". All content that can be created using the "create content" menu is a node. Keep in mind that comments, blocks, and users are <em>not</em> nodes.</dd>
   <dt>Published</dt><dd>A post that is viewable by every visitor of the site, regardless of whether he is logged in (see also "Unpublished").</dd>
   <dt>Role</dt><dd>A classification users are placed into for the purpose of setting users\' permissions. A user receives the combined permissions of all roles to which he or she is subscribed.</dd>
   <dt>Taxonomy</dt><dd>A categorization system that allows the building of complex hierarchical or relational structures and tagging of content (see <a href="@taxonomy">taxonomy help</a>).</dd>
   <dt>Unpublished</dt><dd>A post that is only viewable by administrators and moderators.</dd>
   <dt>User</dt><dd>A person who has an account at your Drupal site, and is currently logged in with that account.</dd>
   <dt>Visitor</dt><dd>A person who does not have an account at your Drupal site or a person who has an account at your Drupal site but is <em>not</em> currently logged in with that account. A visitor is also called an "anonymous user".</dd>
  </dl>', array('!help_pages' => help_links_as_list(), '@taxonomy' => url('admin/help/taxonomy')));

  return $output;
}

/**
 * Menu callback; prints a page listing general help for a module.
 */
function help_page($name) {
  $output = '';
  if (module_hook($name, 'help')) {
    $module = drupal_parse_info_file(drupal_get_path('module', $name) .'/'. $name .'.info');
    drupal_set_title($module['name']);

    $temp = module_invoke($name, 'help', "admin/help#$name", drupal_help_arg());
    if (empty($temp)) {
      $output .= t("No help is available for module %module.", array('%module' => $module['name']));
    }
    else {
      $output .= $temp;
    }

    $admin_tasks = system_get_module_admin_tasks($name);
    ksort($admin_tasks);
    $output .= theme('item_list', $admin_tasks, t('@module administration pages', array('@module' => $module['name'])));
  }
  return $output;
}

function help_links_as_list() {
  $empty_arg = drupal_help_arg();
  $module_info = module_rebuild_cache();

  $modules = array();
  foreach (module_implements('help', TRUE) as $module) {
    if (module_invoke($module, 'help', "admin/help#$module", $empty_arg)) {
      $modules[] = $module;
    }
  }
  sort($modules);

  // Output pretty four-column list
  $count = count($modules);
  $break = ceil($count / 4);
  $output = '<div class="clear-block"><div class="help-items"><ul>';
  foreach ($modules as $i => $module) {
    $output .= '<li>'. l($module_info[$module]->info['name'], 'admin/help/'. $module) .'</li>';
    if (($i + 1) % $break == 0 && ($i + 1) != $count) {
      $output .= '</ul></div><div class="help-items'. ($i + 1 == $break * 3 ? ' help-items-last' : '') .'"><ul>';
    }
  }
  $output .= '</ul></div></div>';

  return $output;
}

