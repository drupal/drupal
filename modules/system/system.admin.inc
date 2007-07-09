<?php
// $Id: system.admin.inc,v 1.4 2007/07/09 18:08:15 dries Exp $

/**
 * Provide the administration overview page.
 */
function system_main_admin_page($arg = NULL) {
  // If we received an argument, they probably meant some other page.
  // Let's 404 them since the menu system cannot be told we do not
  // accept arguments.
  if (isset($arg) && substr($arg, 0, 3) != 'by-') {
    return drupal_not_found();
  }

  // Check for status report errors.
  if (system_status(TRUE)) {
    drupal_set_message(t('One or more problems were detected with your Drupal installation. Check the <a href="@status">status report</a> for more information.', array('@status' => url('admin/logs/status'))), 'error');
  }
  $result = db_query("
    SELECT *
    FROM {menu_links} ml
    INNER JOIN {menu_router} m ON ml.router_path = m.path
    WHERE ml.link_path like 'admin/%' AND ml.link_path != 'admin/help' AND ml.depth = 2 AND ml.menu_name = 'navigation' AND hidden = 0
    ORDER BY p1 ASC, p2 ASC, p3 ASC");
  $blocks = array();
  while ($item = db_fetch_array($result)) {
    _menu_link_translate($item);
    if (!$item['access']) {
      continue;
    }
    $block = $item;
    $block['content'] = '';
    if ($item['block_callback'] && function_exists($item['block_callback'])) {
      $function = $item['block_callback'];
      $block['content'] .= $function();
    }
    $block['content'] .= theme('admin_block_content', system_admin_menu_block($item));
    $blocks[] = $block;
  }
  return theme('admin_page', $blocks);
}


/**
 * Provide a single block from the administration menu as a page.
 * This function is often a destination for these blocks.
 * For example, 'admin/content/types' needs to have a destination to be valid
 * in the Drupal menu system, but too much information there might be
 * hidden, so we supply the contents of the block.
 */
function system_admin_menu_block_page() {
  $item = menu_get_item();
  $content = system_admin_menu_block($item);

  $output = theme('admin_block_content', $content);
  return $output;
}
