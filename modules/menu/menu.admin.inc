<?php
// $Id$

/**
 * @file
 * Administrative page callbacks for menu module.
 */

/**
 * Menu callback which shows an overview page of all the custom menus and their descriptions.
 */
function menu_overview_page() {
  $result = db_query("SELECT * FROM {menu_custom} ORDER BY title");
  $content = array();
  while ($menu = db_fetch_array($result)) {
    $menu['href'] = 'admin/build/menu-customize/'. $menu['menu_name'];
    $menu['localized_options'] = array();
    $menu['description'] = filter_xss_admin($menu['description']);
    $content[] = $menu;
  }
  return theme('admin_block_content', $content);
}

/**
 * Form for editing an entire menu tree at once.
 *
 * Shows for one menu the menu items accessible to the current user and
 * relevant operations.
 */
function menu_overview_form(&$form_state, $menu) {
  global $menu_admin;
  $sql = "
    SELECT m.load_functions, m.to_arg_functions, m.access_callback, m.access_arguments, m.page_callback, m.page_arguments, m.title, m.title_callback, m.title_arguments, m.type, m.description, ml.*
    FROM {menu_links} ml LEFT JOIN {menu_router} m ON m.path = ml.router_path
    WHERE ml.menu_name = '%s'
    ORDER BY p1 ASC, p2 ASC, p3 ASC, p4 ASC, p5 ASC, p6 ASC, p7 ASC, p8 ASC, p9 ASC";
  $result = db_query($sql, $menu['menu_name']);
  $tree = menu_tree_data($result);
  $node_links = array();
  menu_tree_collect_node_links($tree, $node_links);
  // We indicate that a menu administrator is running the menu access check.
  $menu_admin = TRUE;
  menu_tree_check_access($tree, $node_links);
  $menu_admin = FALSE;

  $form = _menu_overview_tree_form($tree);
  $form['#menu'] =  $menu;
  if (element_children($form)) {
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save configuration'),
    );
  }
  else {
    $form['empty_menu'] = array('#value' => t('There are no menu items yet.'));
  }
  return $form;
}

/**
 * Recursive helper function for menu_overview_form().
 */
function _menu_overview_tree_form($tree) {
  static $form = array('#tree' => TRUE);
  foreach ($tree as $data) {
    $title = '';
    $item = $data['link'];
    // Don't show callbacks; these have $item['hidden'] < 0.
    if ($item && $item['hidden'] >= 0) {
      $mlid = 'mlid:'. $item['mlid'];
      $form[$mlid]['#item'] = $item;
      $form[$mlid]['#attributes'] = $item['hidden'] ? array('class' => 'menu-disabled') : array('class' => 'menu-enabled');
      $form[$mlid]['title']['#value'] = l($item['title'], $item['href'], $item['localized_options']) . ($item['hidden'] ? ' ('. t('disabled') .')' : '');
      $form[$mlid]['hidden'] = array(
        '#type' => 'checkbox',
        '#default_value' => !$item['hidden'],
      );
      $form[$mlid]['expanded'] = array(
        '#type' => 'checkbox',
        '#default_value' => $item['expanded'],
      );
      $form[$mlid]['weight'] = array(
        '#type' => 'weight',
        '#delta' => 50,
        '#default_value' => isset($form_state[$mlid]['weight']) ? $form_state[$mlid]['weight'] : $item['weight'],
      );
      $form[$mlid]['mlid'] = array(
        '#type' => 'hidden',
        '#value' => $item['mlid'],
      );
      $form[$mlid]['plid'] = array(
        '#type' => 'textfield',
        '#default_value' => isset($form_state[$mlid]['plid']) ? $form_state[$mlid]['plid'] : $item['plid'],
        '#size' => 6,
      );
      // Build a list of operations.
      $operations = array();
      $operations['edit'] = l(t('edit'), 'admin/build/menu/item/'. $item['mlid'] .'/edit');
      // Only items created by the menu module can be deleted.
      if ($item['module'] == 'menu' || $item['updated'] == 1) {
        $operations['delete'] = l(t('delete'), 'admin/build/menu/item/'. $item['mlid'] .'/delete');
      }
      // Set the reset column.
      elseif ($item['module'] == 'system' && $item['customized']) {
        $operations['reset'] = l(t('reset'), 'admin/build/menu/item/'. $item['mlid'] .'/reset');
      }

      $form[$mlid]['operations'] = array();
      foreach ($operations as $op => $value) {
        $form[$mlid]['operations'][$op] = array('#value' => $value);
      }
    }

    if ($data['below']) {
      _menu_overview_tree_form($data['below']);
    }
  }
  return $form;
}

/**
 * Submit handler for the menu overview form.
 *
 * This function takes great care in saving parent items first, then items
 * underneath them. Saving items in the incorrect order can break the menu tree.
 *
 * @see menu_overview_form()
 */
function menu_overview_form_submit($form, &$form_state) {
  // When dealing with saving menu items, the order in which these items are
  // saved is critical. If a changed child item is saved before its parent,
  // the child item could be saved with an invalid path past its immediate
  // parent. To prevent this, save items in the form in the same order they
  // are sent by $_POST, ensuring parents are saved first, then their children.
  // See http://drupal.org/node/181126#comment-632270
  $order = array_flip(array_keys($form['#post'])); // Get the $_POST order.
  $form = array_merge($order, $form); // Update our original form with the new order.

  $updated_items = array();
  $fields = array('expanded', 'weight', 'plid');
  foreach (element_children($form) as $mlid) {
    if (isset($form[$mlid]['#item'])) {
      $element = $form[$mlid];
      // Update any fields that have changed in this menu item.
      foreach ($fields as $field) {
        if ($element[$field]['#value'] != $element[$field]['#default_value']) {
          $element['#item'][$field] = $element[$field]['#value'];
          $updated_items[$mlid] = $element['#item'];
        }
      }
      // Hidden is a special case, the value needs to be reversed.
      if ($element['hidden']['#value'] != $element['hidden']['#default_value']) {
        $element['#item']['hidden'] = !$element['hidden']['#value'];
        $updated_items[$mlid] = $element['#item'];
      }
    }
  }

  // Save all our changed items to the database.
  foreach ($updated_items as $item) {
    $item['customized'] = 1;
    menu_link_save($item);
  }
}

/**
 * Theme the menu overview form into a table.
 *
 * @ingroup themeable
 */
function theme_menu_overview_form($form) {
  drupal_add_tabledrag('menu-overview', 'match', 'parent', 'menu-plid', 'menu-plid', 'menu-mlid', TRUE, MENU_MAX_DEPTH - 1);
  drupal_add_tabledrag('menu-overview', 'order', 'sibling', 'menu-weight');

  $header = array(
    t('Menu item'),
    array('data' => t('Enabled'), 'class' => 'checkbox'),
    array('data' => t('Expanded'), 'class' => 'checkbox'),
    t('Weight'),
    array('data' => t('Operations'), 'colspan' => '3'),
  );

  $rows = array();
  foreach (element_children($form) as $mlid) {
    if (isset($form[$mlid]['hidden'])) {
      $element = &$form[$mlid];
      // Build a list of operations.
      $operations = array();
      foreach (element_children($element['operations']) as $op) {
        $operations[] = drupal_render($element['operations'][$op]);
      }
      while (count($operations) < 2) {
        $operations[] = '';
      }

      // Add special classes to be used for tabledrag.js.
      $element['plid']['#attributes']['class'] = 'menu-plid';
      $element['mlid']['#attributes']['class'] = 'menu-mlid';
      $element['weight']['#attributes']['class'] = 'menu-weight';

      // Change the parent field to a hidden. This allows any value but hides the field.
      $element['plid']['#type'] = 'hidden';

      $row = array();
      $row[] = theme('indentation', $element['#item']['depth'] - 1) . drupal_render($element['title']);
      $row[] = array('data' => drupal_render($element['hidden']), 'class' => 'checkbox');
      $row[] = array('data' => drupal_render($element['expanded']), 'class' => 'checkbox');
      $row[] = drupal_render($element['weight']) . drupal_render($element['plid']) . drupal_render($element['mlid']);
      $row = array_merge($row, $operations);

      $row = array_merge(array('data' => $row), $element['#attributes']);
      $row['class'] = !empty($row['class']) ? $row['class'] .' draggable' : 'draggable';
      $rows[] = $row;
    }
  }
  $output = '';
  if ($rows) {
    $output .= theme('table', $header, $rows, array('id' => 'menu-overview'));
  }
  $output .= drupal_render($form);
  return $output;
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
    '#item' => $item,
  );
  if ($type == 'add' || empty($item)) {
    // This is an add form, initialize the menu link.
    $item = array('link_title' => '', 'mlid' => 0, 'plid' => 0, 'menu_name' => $menu['menu_name'], 'weight' => 0, 'link_path' => '', 'options' => array(), 'module' => 'menu', 'expanded' => 0, 'hidden' => 0, 'has_children' => 0);
  }
  foreach (array('link_path', 'mlid', 'module', 'has_children', 'options') as $key) {
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
  $form['menu']['enabled'] = array(
    '#type' => 'checkbox',
    '#title' => t('Enabled'),
    '#default_value' => !$item['hidden'],
    '#description' => t('Menu items that are not enabled will not be listed in any menu.'),
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
    '#description' => t('The maximum depth for an item and all its children is fixed at !maxdepth. Some menu items may not be available as parents if selecting them would exceed this limit.', array('!maxdepth' => MENU_MAX_DEPTH)),
    '#attributes' => array('class' => 'menu-title-select'),
  );
  $form['menu']['weight'] = array(
    '#type' => 'weight',
    '#title' => t('Weight'),
    '#delta' => 50,
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
  $normal_path = drupal_get_normal_path($item['link_path']);
  if ($item['link_path'] != $normal_path) {
    drupal_set_message(t('The menu system stores system paths only, but will use the URL alias for display. %link_path has been stored as %normal_path', array('%link_path' => $item['link_path'], '%normal_path' => $normal_path)));
    $item['link_path'] = $normal_path;
  }
  if (!menu_path_is_external($item['link_path'])) {
    $parsed_link = parse_url($item['link_path']);
    if (isset($parsed_link['query'])) {
      $item['options']['query'] = $parsed_link['query'];
    }
    else {
      // Use unset() rather than setting to empty string
      // to avoid redundant serialized data being stored.
      unset($item['options']['query']);
    }
    if (isset($parsed_link['fragment'])) {
      $item['options']['fragment'] = $parsed_link['fragment'];
    }
    else {
      unset($item['options']['fragment']);
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
  $item = &$form_state['values']['menu'];

  // The value of "hidden" is the opposite of the value
  // supplied by the "enabled" checkbox.
  $item['hidden'] = (int) !$item['enabled'];
  unset($item['enabled']);

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
      '#description' => t('The machine-readable name of this menu. This text will be used for constructing the URL of the <em>menu overview</em> page for this menu. This name must contain only lowercase letters, numbers, and hyphens, and must be unique.'),
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
  // Links defined via hook_menu may not be deleted. Updated items are an
  // exception, as they can be broken.
  if ($item['module'] == 'system' && !$item['updated']) {
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
  $form['menu_default_node_menu'] = array(
    '#type' => 'select',
    '#title' => t('Default menu for content'),
    '#default_value' => variable_get('menu_default_node_menu', 'primary-links'),
    '#options' => $menu_options,
    '#description' => t('Choose the menu to be the default in the menu options in the content authoring form.'),
  );

  $primary = variable_get('menu_primary_links_source', 'primary-links');
  $primary_options = array_merge($menu_options, array('' => t('No primary links')));
  $form['menu_primary_links_source'] = array(
    '#type' => 'select',
    '#title' => t('Source for the primary links'),
    '#default_value' => $primary,
    '#options' => $primary_options,
    '#tree' => FALSE,
    '#description' => t('Select what should be displayed as the primary links.'),
  );

  $secondary_options = array_merge($menu_options, array('' => t('No secondary links')));
  $form["menu_secondary_links_source"] = array(
    '#type' => 'select',
    '#title' => t('Source for the secondary links'),
    '#default_value' => variable_get('menu_secondary_links_source', 'secondary-links'),
    '#options' => $secondary_options,
    '#tree' => FALSE,
    '#description' => t('Select what should be displayed as the secondary links. You can choose the same menu for secondary links as for primary links (currently %primary). If you do this, the children of the active primary menu link will be displayed as secondary links.', array('%primary' => $primary_options[$primary])),
  );

  return system_settings_form($form);
}

