<?php
// $Id: menu.admin.inc,v 1.3 2007/09/27 16:59:54 goba Exp $

/**
 * @file
 * Administrative page callbaks for menu module.
 */

/**
 * Menu callback which shows an overview page of all the custom menus and their descriptions.
 */
function menu_overview_page() {
  $result = db_query("SELECT * FROM {menu_custom} ORDER BY title");
  $content = array();
  while ($menu = db_fetch_array($result)) {
    $menu['href'] = 'admin/build/menu-customize/'. $menu['menu_name'];
    $menu['options'] = array();
    $content[] = $menu;
  }
  return theme('admin_block_content', $content);
}

/**
 * Shows for one menu the menu items accessible to the current user and relevant operations.
 */
function menu_overview($menu) {

  $header = array(t('Menu item'), t('Expanded'), array('data' => t('Operations'), 'colspan' => '3'));
  $sql ="
    SELECT m.load_functions, m.to_arg_functions, m.access_callback, m.access_arguments, m.page_callback, m.page_arguments, m.title, m.title_callback, m.title_arguments, m.type, ml.*
    FROM {menu_links} ml LEFT JOIN {menu_router} m ON m.path = ml.router_path
    WHERE ml.menu_name = '%s'
    ORDER BY p1 ASC, p2 ASC, p3 ASC, p4 ASC, p5 ASC, p6 ASC, p7 ASC, p8 ASC, p9 ASC";
  $sql_count = "SELECT COUNT(*) FROM {menu_links} ml WHERE menu_name = '%s'";
  $result = pager_query($sql, 200, 0, $sql_count, $menu['menu_name']);
  $tree = menu_tree_data($result);
  $node_links = array();
  menu_tree_collect_node_links($tree, $node_links);
  menu_tree_check_access($tree, $node_links);
  $rows = _menu_overview_tree($tree);
  $output = theme('table', $header, $rows);
  $output .= theme('pager', NULL, 200, 0);
  return $output;
}

/**
 * Recursive helper function for menu_overview().
 */
function _menu_overview_tree($tree) {
  static $rows = array();
  foreach ($tree as $data) {
    $title = '';
    $item = $data['link'];
    // Don't show callbacks; these have $item['hidden'] < 0.
    if ($item && $item['hidden'] >= 0) {
      $title = str_repeat('&nbsp;&nbsp;', $item['depth'] - 1) . ($item['depth'] > 1 ? '-&nbsp;' : '');
      $title .= l($item['title'], $item['href'], $item['options']);
      // Populate the operations field.
      $operations = array();
      // Set the edit column.
      $operations[] = array('data' => l(t('edit'), 'admin/build/menu/item/'. $item['mlid'] .'/edit'));
      if ($item['hidden']) {
        $title .= ' ('. t('disabled') .')';
        $class = 'menu-disabled';
        $operations[] = array('data' => l(t('enable'), 'admin/build/menu/item/'. $item['mlid'] .'/enable'));
      }
      else {
        $class = 'menu-enabled';
        $operations[] = array('data' => l(t('disable'), 'admin/build/menu/item/'. $item['mlid'] .'/disable'));
      }
      // Only items created by the menu module can be deleted.
      if ($item['module'] == 'menu') {
        $operations[] = array('data' => l(t('delete'), 'admin/build/menu/item/'. $item['mlid'] .'/delete'));
      }
      // Set the reset column.
      elseif ($item['module'] == 'system' && $item['customized']) {
        $operations[] = array('data' => l(t('reset'), 'admin/build/menu/item/'. $item['mlid'] .'/reset'));
      }
      else {
        $operations[] = array('data' => '');
      }
      $row = array(
        array('data' => $title, 'class' => $class),
        array('data' => ($item['has_children'] ? (($item['expanded']) ? t('Yes') : t('No')) : ''), 'class' => $class),
      );
      foreach ($operations as $operation) {
        $operation['class'] = $class;
        $row[] = $operation;
      }
      $rows[] = $row;
    }
    if ($data['below']) {
      _menu_overview_tree($data['below']);
    }
  }
  return $rows;
}

/**
 * Menu callback; enable/disable a menu link.
 *
 * @param $hide
 *   TRUE to not show in the menu tree. FALSE to make the item and its children
 *   reappear in menu tree.
 * @param $item
 *    The menu item.
 */
function menu_flip_item($hide, $item) {

  $item['hidden'] = (bool)$hide;
  $item['customized'] = 1;
  menu_link_save($item);
  drupal_set_message($hide ? t('The menu item has been disabled.') : t('The menu item has been enabled.'));
  drupal_goto('admin/build/menu-customize/'. $item['menu_name']);
}

/**
 * Menu callback; Build the menu link editing form.
 */
function menu_edit_item(&$form_state, $type, $item, $menu) {

  $form['menu'] = array(
    '#type' => 'fieldset',
    '#title' => t('Menu settings'),
    '#collapsible' => FALSE,
    '#tree' => TRUE,
    '#weight' => -2,
    '#attributes' => array('class' => 'menu-item-form'),
  );
  if ($type == 'add' || empty($item)) {
    // This is an add form, initialize the menu link.
    $item = array('link_title' => '', 'mlid' => 0, 'plid' => 0, 'menu_name' => $menu['menu_name'], 'weight' => 0,  'link_path' => '', 'options' => array(), 'module' => 'menu', 'expanded' => 0, 'hidden' => 0, 'has_children' => 0);
  }
  foreach (array('link_path', 'mlid', 'module', 'hidden', 'has_children', 'options') as $key) {
    $form['menu'][$key] = array('#type' => 'value', '#value' => $item[$key]);
  }
  // Any item created or edited via this interface is considered "customized".
  $form['menu']['customized'] = array('#type' => 'value', '#value' => 1);
  $form['menu']['original_item'] = array('#type' => 'value', '#value' => $item);

  $path = $item['link_path'];
  if (isset($item['options']['query'])) {
    $path .= '?'. $item['options']['query'];
  }
  if (isset($item['options']['fragment'])) {
    $path .= '#'. $item['options']['fragment'];
  }
  if ($item['module'] == 'menu') {
    $form['menu']['link_path'] = array(
      '#type' => 'textfield',
      '#title' => t('Path'),
      '#default_value' => $path,
      '#description' => t('The path this menu item links to. This can be an internal Drupal path such as %add-node or an external URL such as %drupal. Enter %front to link to the front page.', array('%front' => '<front>', '%add-node' => 'node/add', '%drupal' => 'http://drupal.org')),
      '#required' => TRUE,
    );
    $form['delete'] = array(
      '#type' => 'submit',
      '#value' => t('Delete'),
      '#access' => $item['mlid'],
      '#submit' => array('menu_item_delete_submit'),
      '#weight' => 10,
    );
  }
  else {
    $form['menu']['_path'] = array(
      '#type' => 'item',
      '#title' => t('Path'),
      '#description' => l($item['link_title'], $item['href'], $item['options']),
    );
  }
  $form['menu']['link_title'] = array('#type' => 'textfield',
    '#title' => t('Menu link title'),
    '#default_value' => $item['link_title'],
    '#description' => t('The link text corresponding to this item that should appear in the menu.'),
    '#required' => TRUE,
  );
  $form['menu']['description'] = array(
    '#type' => 'textarea',
    '#title' => t('Description'),
    '#default_value' => isset($item['options']['attributes']['title']) ? $item['options']['attributes']['title'] : '',
    '#rows' => 1,
    '#description' => t('The description displayed when hovering over a menu item.'),
  );
  $form['menu']['expanded'] = array(
    '#type' => 'checkbox',
    '#title' => t('Expanded'),
    '#default_value' => $item['expanded'],
    '#description' => t('If selected and this menu item has children, the menu will always appear expanded.'),
  );

  // Generate a list of possible parents (not including this item or descendants).
  $options = menu_parent_options(menu_get_menus(), $item);
  $default = $item['menu_name'] .':'. $item['plid'];
  if (!isset($options[$default])) {
    $default = 'navigation:0';
  }
  $form['menu']['parent'] = array(
    '#type' => 'select',
    '#title' => t('Parent item'),
    '#default_value' => $default,
    '#options' => $options,
    '#description' => t('The maximum depth for an item and all its children is fixed at !maxdepth.  Some menu items may not be available as parents if selecting them would exceed this limit.', array('!maxdepth' => MENU_MAX_DEPTH)),
    '#attributes' => array('class' => 'menu-title-select'),
  );
  $form['menu']['weight'] = array(
    '#type' => 'weight',
    '#title' => t('Weight'),
    '#default_value' => $item['weight'],
    '#description' => t('Optional. In the menu, the heavier items will sink and the lighter items will be positioned nearer the top.'),
  );
  $form['submit'] = array('#type' => 'submit', '#value' => t('Save'));


  return $form;
}

/**
 * Validate form values for a menu link being added or edited.
 */
function menu_edit_item_validate($form, &$form_state) {
  $item = &$form_state['values']['menu'];
  if (!menu_path_is_external($item['link_path'])) {
    $parsed_link = parse_url($item['link_path']);
    if (isset($parsed_link['query'])) {
      $item['options']['query'] = $parsed_link['query'];
    }
    if (isset($parsed_link['fragment'])) {
      $item['options']['fragment'] = $parsed_link['fragment'];
    }
    if ($item['link_path'] != $parsed_link['path']) {
      $item['link_path'] = $parsed_link['path'];
    }
  }
  if (!trim($item['link_path']) || !menu_valid_path($item)) {
    form_set_error('link_path', t("The path '@link_path' is either invalid or you do not have access to it.", array('@link_path' => $item['link_path'])));
  }
}

/**
 * Submit function for the delete button on the menu item editing form.
 */
function menu_item_delete_submit($form, &$form_state) {
  $form_state['redirect'] = 'admin/build/menu/item/'. $form_state['values']['menu']['mlid'] .'/delete';
}

/**
 * Process menu and menu item add/edit form submissions.
 */
function menu_edit_item_submit($form, &$form_state) {
  $item = $form_state['values']['menu'];
  $item['options']['attributes']['title'] = $item['description'];
  list($item['menu_name'], $item['plid']) = explode(':', $item['parent']);
  if (!menu_link_save($item)) {
    drupal_set_message(t('There was an error saving the menu link.'), 'error');
  }
  $form_state['redirect'] = 'admin/build/menu-customize/'. $item['menu_name'];
}

/**
 * Menu callback; Build the form that handles the adding/editing of a custom menu.
 */
function menu_edit_menu(&$form_state, $type, $menu = array()) {
  if ($type == 'edit') {
    $form['menu_name'] = array('#type' => 'value', '#value' => $menu['menu_name']);
    $form['#insert'] = FALSE;
    $form['delete'] = array(
      '#type' => 'submit',
      '#value' => t('Delete'),
      '#access' => !in_array($menu['menu_name'], menu_list_system_menus()),
      '#submit' => array('menu_custom_delete_submit'),
      '#weight' => 10,
    );
  }
  else {
    $menu = array('menu_name' => '', 'title' => '', 'description' => '');
    $form['menu_name'] = array(
      '#type' => 'textfield',
      '#title' => t('Menu name'),
      '#maxsize' => MENU_MAX_MENU_NAME_LENGTH_UI,
      '#description' => t('The machine-readable name of this menu. This text will be used for constructing the URL of the <em>menu overview</em> page for this menu. This name may consist of only of lowercase letters, numbers, and hyphens, and must be unique.'),
      '#required' => TRUE,
    );
    $form['#insert'] = TRUE;
  }
  $form['#title'] = $menu['title'];
  $form['title'] = array(
    '#type' => 'textfield',
    '#title' => t('Title'),
    '#default_value' => $menu['title'],
    '#required' => TRUE,
  );
  $form['description'] = array(
    '#type' => 'textarea',
    '#title' => t('Description'),
    '#default_value' => $menu['description'],
  );
  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save'),
  );

  return $form;
}

/**
 * Submit function for the 'Delete' button on the menu editing form.
 */
function menu_custom_delete_submit($form, &$form_state) {
  $form_state['redirect'] = 'admin/build/menu-customize/'. $form_state['values']['menu_name'] .'/delete';
}

/**
 * Menu callback; check access and get a confirm form for deletion of a custom menu.
 */
function menu_delete_menu_page($menu) {
  // System-defined menus may not be deleted.
  if (in_array($menu['menu_name'], menu_list_system_menus())) {
    drupal_access_denied();
    return;
  }
  return drupal_get_form('menu_delete_menu_confirm', $menu);
}

/**
 * Build a confirm form for deletion of a custom menu.
 */
function menu_delete_menu_confirm(&$form_state, $menu) {
  $form['#menu'] = $menu;
  $caption = '';
  $num_links = db_result(db_query("SELECT COUNT(*) FROM {menu_links} WHERE menu_name = '%s'", $menu['menu_name']));
  if ($num_links) {
    $caption .= '<p>'. format_plural($num_links, '<strong>Warning:</strong> There is currently 1 menu item in %title. It will be deleted (system-defined items will be reset).', '<strong>Warning:</strong> There are currently @count menu items in %title. They will be deleted (system-defined items will be reset).', array('%title' => $menu['title'])) .'</p>';
  }
  $caption .= '<p>'. t('This action cannot be undone.') .'</p>';
  return confirm_form($form, t('Are you sure you want to delete the custom menu %title?', array('%title' => $menu['title'])), 'admin/build/menu-customize/'. $menu['menu_name'], $caption, t('Delete'));
}

/**
 * Delete a custom menu and all items in it.
 */
function menu_delete_menu_confirm_submit($form, &$form_state) {
  $menu = $form['#menu'];
  $form_state['redirect'] = 'admin/build/menu';
  // System-defined menus may not be deleted - only menus defined by this module.
  if (in_array($menu['menu_name'], menu_list_system_menus())  || !db_result(db_query("SELECT COUNT(*) FROM {menu_custom} WHERE menu_name = '%s'", $menu['menu_name']))) {
    return;
  }
  // Reset all the menu links defined by the system via hook_menu.
  $result = db_query("SELECT * FROM {menu_links} ml INNER JOIN {menu_router} m ON ml.router_path = m.path WHERE ml.menu_name = '%s' AND ml.module = 'system' ORDER BY m.number_parts ASC", $menu['menu_name']);
  while ($item = db_fetch_array($result)) {
    menu_reset_item($item);
  }
  // Delete all links to the overview page for this menu.
  $result = db_query("SELECT mlid FROM {menu_links} ml WHERE ml.link_path = '%s'", 'admin/build/menu-customize/'. $menu['menu_name']);
  while ($m = db_fetch_array($result)) {
    menu_link_delete($m['mlid']);
  }
  // Delete all the links in the menu and the menu from the list of custom menus.
  db_query("DELETE FROM {menu_links} WHERE menu_name = '%s'", $menu['menu_name']);
  db_query("DELETE FROM {menu_custom} WHERE menu_name = '%s'", $menu['menu_name']);
  // Delete all the blocks for this menu.
  db_query("DELETE FROM {blocks} WHERE module = 'menu' AND delta = '%s'", $menu['menu_name']);
  db_query("DELETE FROM {blocks_roles} WHERE module = 'menu' AND delta = '%s'", $menu['menu_name']);
  menu_cache_clear_all();
  cache_clear_all();
  $t_args = array('%title' => $menu['title']);
  drupal_set_message(t('The custom menu %title has been deleted.', $t_args));
  watchdog('menu', 'Deleted custom menu %title and all its menu items.', $t_args, WATCHDOG_NOTICE);
}

/**
 * Validates the human and machine-readable names when adding or editing a menu.
 */
function menu_edit_menu_validate($form, &$form_state) {
  $item = $form_state['values'];
  if (preg_match('/[^a-z0-9-]/', $item['menu_name'])) {
    form_set_error('menu_name', t('The menu name may only consist of lowercase letters, numbers, and hyphens.'));
  }
  if (strlen($item['menu_name']) > MENU_MAX_MENU_NAME_LENGTH_UI) {
    form_set_error('menu_name', format_plural(MENU_MAX_MENU_NAME_LENGTH_UI, "The menu name can't be longer than 1 character.", "The menu name can't be longer than @count characters."));
  }
  if ($form['#insert']) {
    // We will add 'menu-' to the menu name to help avoid name-space conflicts.
    $item['menu_name'] = 'menu-'. $item['menu_name'];
    if (db_result(db_query("SELECT menu_name FROM {menu_custom} WHERE menu_name = '%s'", $item['menu_name'])) ||
      db_result(db_query_range("SELECT menu_name FROM {menu_links} WHERE menu_name = '%s'", $item['menu_name'], 0, 1))) {
      form_set_error('menu_name', t('The menu already exists.'));
    }
  }
}

/**
 * Submit function for adding or editing a custom menu.
 */
function menu_edit_menu_submit($form, &$form_state) {
  $menu = $form_state['values'];
  $path = 'admin/build/menu-customize/';
  if ($form['#insert']) {
    // Add 'menu-' to the menu name to help avoid name-space conflicts.
    $menu['menu_name'] = 'menu-'. $menu['menu_name'];
    $link['link_title'] = $menu['title'];
    $link['link_path'] = $path . $menu['menu_name'];
    $link['router_path'] = $path .'%';
    $link['module'] = 'menu';
    $link['plid'] = db_result(db_query("SELECT mlid FROM {menu_links} WHERE link_path = '%s' AND module = '%s'", 'admin/build/menu', 'system'));
    menu_link_save($link);
    db_query("INSERT INTO {menu_custom} (menu_name, title, description) VALUES ('%s', '%s', '%s')", $menu['menu_name'], $menu['title'], $menu['description']);
  }
  else {
    db_query("UPDATE {menu_custom} SET title = '%s', description = '%s' WHERE menu_name = '%s'", $menu['title'], $menu['description'], $menu['menu_name']);
    $result = db_query("SELECT mlid FROM {menu_links} WHERE link_path = '%s'", $path . $menu['menu_name']);
    while ($m = db_fetch_array($result)) {
      $link = menu_link_load($m['mlid']);
      $link['link_title'] = $menu['title'];
      menu_link_save($link);
    }
  }
  $form_state['redirect'] = $path . $menu['menu_name'];
}

/**
 * Menu callback; Check access and present a confirm form for deleting a menu link.
 */
function menu_item_delete_page($item) {
  // Links defined via hook_menu may not be deleted.
  if ($item['module'] == 'system') {
    drupal_access_denied();
    return;
  }
  return drupal_get_form('menu_item_delete_form', $item);
}

/**
 * Build a confirm form for deletion of a single menu link.
 */
function menu_item_delete_form(&$form_state, $item) {
  $form['#item'] = $item;
  return confirm_form($form, t('Are you sure you want to delete the custom menu item %item?', array('%item' => $item['link_title'])), 'admin/build/menu-customize/'. $item['menu_name']);
}

/**
 * Process menu delete form submissions.
 */
function menu_item_delete_form_submit($form, &$form_state) {
  $item = $form['#item'];
  menu_link_delete($item['mlid']);
  $t_args = array('%title' => $item['link_title']);
  drupal_set_message(t('The menu item %title has been deleted.', $t_args));
  watchdog('menu', 'Deleted menu item %title.', $t_args, WATCHDOG_NOTICE);
  $form_state['redirect'] = 'admin/build/menu-customize/'. $item['menu_name'];
}

/**
 * Menu callback; reset a single modified item.
 */
function menu_reset_item_confirm(&$form_state, $item) {
  $form['item'] = array('#type' => 'value', '#value' => $item);
  return confirm_form($form, t('Are you sure you want to reset the item %item to its default values?', array('%item' => $item['link_title'])), 'admin/build/menu-customize/'. $item['menu_name'], t('Any customizations will be lost. This action cannot be undone.'), t('Reset'));
}

/**
 * Process menu reset item form submissions.
 */
function menu_reset_item_confirm_submit($form, &$form_state) {
  $item = $form_state['values']['item'];
  $new_item = menu_reset_item($item);
  drupal_set_message(t('The menu item was reset to its default settings.'));
  $form_state['redirect'] = 'admin/build/menu-customize/'. $new_item['menu_name'];
}

/**
 * Menu callback; Build the form presenting menu configuration options.
 */
function menu_configure() {
  $form['intro'] = array(
    '#type' => 'item',
    '#value' => t('The menu module allows on-the-fly creation of menu links in the content authoring forms. The following option sets the default menu in which a new link will be added.'),
  );

  $menu_options = menu_get_menus();
  $form['menu_default_node_menu'] = array('#type' => 'select',
    '#title' => t('Default menu for content'),
    '#default_value' => variable_get('menu_default_node_menu', 'navigation'),
    '#options' => $menu_options,
    '#description' => t('Choose the menu to be the default in the menu options in the content authoring form.'),
  );

  $secondary_options = array('secondary-links' => $menu_options['secondary-links'],  'primary-links' => $menu_options['primary-links']);
  $form["menu_secondary_links_source"] = array(
    '#type' => 'radios',
    '#title' => t('Source for the secondary links'),
    '#default_value' => variable_get('menu_secondary_links_source', 'secondary-links'),
    '#options' => $secondary_options,
    '#tree' => FALSE,
    '#description' => t('Select what should be displayed as the secondary links . If %primary is choosen the children of the active primary menu link (if any) will be shown instead of the links in the %secondary menu.', array('%secondary' => $menu_options['secondary-links'],  '%primary' => $menu_options['primary-links'])),
  );

  return system_settings_form($form);
}

