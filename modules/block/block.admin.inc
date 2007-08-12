<?php
// $Id: block.admin.inc,v 1.5 2007/08/12 15:55:35 dries Exp $

/**
 * @file
 * Admin page callbacks for the block module.
 */

/**
 * Generate main block administration form.
 */
function block_admin_display(&$form_state, $theme = NULL) {
  global $theme_key, $custom_theme;

  // Add CSS
  drupal_add_css(drupal_get_path('module', 'block') .'/block.css', 'module', 'all', FALSE);

  // If non-default theme configuration has been selected, set the custom theme.
  if ($theme) {
    $custom_theme = $theme;
  }
  else {
    $custom_theme = variable_get('theme_default', 'garland');
  }
  init_theme();

  // Fetch and sort blocks
  $blocks = _block_rehash();
  usort($blocks, '_block_compare');

  $throttle = module_exists('throttle');
  $block_regions = array(BLOCK_REGION_NONE => '<'. t('none') .'>') + system_region_list($theme_key);

  // Build form tree
  $form['#action'] = arg(3) ? url('admin/build/block/list/'. $theme_key) : url('admin/build/block');
  $form['#tree'] = TRUE;
  foreach ($blocks as $i => $block) {
    $form[$i]['module'] = array('#type' => 'value', '#value' => $block['module']);
    $form[$i]['delta'] = array('#type' => 'value', '#value' => $block['delta']);
    $form[$i]['info'] = array('#value' => check_plain($block['info']));
    $form[$i]['theme'] = array('#type' => 'hidden', '#value' => $theme_key);
    $form[$i]['weight'] = array('#type' => 'weight', '#default_value' => $block['weight']);
    $form[$i]['region'] = array('#type' => 'select',
      '#default_value' => $block['status'] ? (isset($block['region']) ? $block['region'] : system_default_region($theme_key)) : BLOCK_REGION_NONE,
      '#options' => $block_regions,
    );

    if ($throttle) {
      $form[$i]['throttle'] = array('#type' => 'checkbox', '#default_value' => isset($block['throttle']) ? $block['throttle'] : FALSE);
    }
    $form[$i]['configure'] = array('#value' => l(t('configure'), 'admin/build/block/configure/'. $block['module'] .'/'. $block['delta']));
    if ($block['module'] == 'block') {
      $form[$i]['delete'] = array('#value' => l(t('delete'), 'admin/build/block/delete/'. $block['delta']));
    }
  }
  $form['submit'] = array('#type' => 'submit', '#value' => t('Save blocks'));

  return $form;
}


/**
 * Process main block administration form submission.
 */
function block_admin_display_submit($form, &$form_state) {
  foreach ($form_state['values'] as $block) {
    $block['status'] = $block['region'] != BLOCK_REGION_NONE;
    $block['region'] = $block['status'] ? $block['region'] : '';
    db_query("UPDATE {blocks} SET status = %d, weight = %d, region = '%s', throttle = %d WHERE module = '%s' AND delta = '%s' AND theme = '%s'", $block['status'], $block['weight'], $block['region'], isset($block['throttle']) ? $block['throttle'] : 0, $block['module'], $block['delta'], $block['theme']);
  }
  drupal_set_message(t('The block settings have been updated.'));
  cache_clear_all();
}

/**
 * Helper function for sorting blocks on admin/build/block.
 *
 * Active blocks are sorted by region, then by weight.
 * Disabled blocks are sorted by name.
 */
function _block_compare($a, $b) {
  $status = $b['status'] - $a['status'];
  // Separate enabled from disabled.
  if ($status) {
    return $status;
  }
  // Enabled blocks
  if ($a['status']) {
    $place = strcmp($a['region'], $b['region']);
    return $place ? $place : ($a['weight'] - $b['weight']);
  }
  // Disabled blocks
  else {
    return strcmp($a['info'], $b['info']);
  }
}

/**
 * Menu callback; displays the block configuration form.
 */
function block_admin_configure(&$form_state, $module = NULL, $delta = 0) {

  $form['module'] = array('#type' => 'value', '#value' => $module);
  $form['delta'] = array('#type' => 'value', '#value' => $delta);

  $edit = db_fetch_array(db_query("SELECT pages, visibility, custom, title FROM {blocks} WHERE module = '%s' AND delta = '%s'", $module, $delta));

  $form['block_settings'] = array(
    '#type' => 'fieldset',
    '#title' => t('Block specific settings'),
    '#collapsible' => TRUE,
  );
  $form['block_settings']['title'] = array(
    '#type' => 'textfield',
    '#title' => t('Block title'),
    '#maxlength' => 64,
    '#description' =>  $module == 'block' ? t('The title of the block as shown to the user.') : t('Override the default title for the block. Use <em>&lt;none&gt;</em> to display no title, or leave blank to use the default block title.'),
    '#default_value' => $edit['title'],
    '#weight' => -18,
  );


  // Module-specific block configurations.
  if ($settings = module_invoke($module, 'block', 'configure', $delta)) {
    foreach ($settings as $k => $v) {
      $form['block_settings'][$k] = $v;
    }
  }

  // Get the block subject for the page title.
  $info = module_invoke($module, 'block', 'list');
  if (isset($info[$delta])) {
    drupal_set_title(t("'%name' block", array('%name' => $info[$delta]['info'])));
  }

  // Standard block configurations.
  $form['user_vis_settings'] = array(
    '#type' => 'fieldset',
    '#title' => t('User specific visibility settings'),
    '#collapsible' => TRUE,
  );
  $form['user_vis_settings']['custom'] = array(
    '#type' => 'radios',
    '#title' => t('Custom visibility settings'),
    '#options' => array(
      t('Users cannot control whether or not they see this block.'),
      t('Show this block by default, but let individual users hide it.'),
      t('Hide this block by default but let individual users show it.')
    ),
    '#description' =>  t('Allow individual users to customize the visibility of this block in their account settings.'),
    '#default_value' => $edit['custom'],
  );

  // Role-based visibility settings
  $default_role_options = array();
  $result = db_query("SELECT rid FROM {blocks_roles} WHERE module = '%s' AND delta = '%s'", $module, $delta);
  while ($role = db_fetch_object($result)) {
    $default_role_options[] = $role->rid;
  }
  $result = db_query('SELECT rid, name FROM {role} ORDER BY name');
  $role_options = array();
  while ($role = db_fetch_object($result)) {
    $role_options[$role->rid] = $role->name;
  }
  $form['role_vis_settings'] = array(
    '#type' => 'fieldset',
    '#title' => t('Role specific visibility settings'),
    '#collapsible' => TRUE,
  );
  $form['role_vis_settings']['roles'] = array(
    '#type' => 'checkboxes',
    '#title' => t('Show block for specific roles'),
    '#default_value' => $default_role_options,
    '#options' => $role_options,
    '#description' =>  t('Show this block only for the selected role(s). If you select no roles, the block will be visible to all users.'),
  );

  $form['page_vis_settings'] = array(
    '#type' => 'fieldset',
    '#title' => t('Page specific visibility settings'),
    '#collapsible' => TRUE,
  );
  $access = user_access('use PHP for block visibility');

  if ($edit['visibility'] == 2 && !$access) {
    $form['page_vis_settings'] = array();
    $form['page_vis_settings']['visibility'] = array('#type' => 'value', '#value' => 2);
    $form['page_vis_settings']['pages'] = array('#type' => 'value', '#value' => $edit['pages']);
  }
  else {
    $options = array(t('Show on every page except the listed pages.'), t('Show on only the listed pages.'));
    $description = t("Enter one page per line as Drupal paths. The '*' character is a wildcard. Example paths are %blog for the blog page and %blog-wildcard for every personal blog. %front is the front page.", array('%blog' => 'blog', '%blog-wildcard' => 'blog/*', '%front' => '<front>'));

    if ($access) {
      $options[] = t('Show if the following PHP code returns <code>TRUE</code> (PHP-mode, experts only).');
      $description .= ' '. t('If the PHP-mode is chosen, enter PHP code between %php. Note that executing incorrect PHP-code can break your Drupal site.', array('%php' => '<?php ?>'));
    }
    $form['page_vis_settings']['visibility'] = array(
      '#type' => 'radios',
      '#title' => t('Show block on specific pages'),
      '#options' => $options,
      '#default_value' => $edit['visibility'],
    );
    $form['page_vis_settings']['pages'] = array(
      '#type' => 'textarea',
      '#title' => t('Pages'),
      '#default_value' => $edit['pages'],
      '#description' => $description,
    );
  }

  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save block'),
  );

  return $form;
}

function block_admin_configure_validate($form, &$form_state) {
  if ($form_state['values']['module'] == 'block') {
    if (empty($form_state['values']['info']) || db_result(db_query("SELECT COUNT(*) FROM {boxes} WHERE bid != %d AND info = '%s'", $form_state['values']['delta'], $form_state['values']['info']))) {
      form_set_error('info', t('Please ensure that each block description is unique.'));
    }
  }
}

function block_admin_configure_submit($form, &$form_state) {
  if (!form_get_errors()) {
    db_query("UPDATE {blocks} SET visibility = %d, pages = '%s', custom = %d, title = '%s' WHERE module = '%s' AND delta = '%s'", $form_state['values']['visibility'], trim($form_state['values']['pages']), $form_state['values']['custom'], $form_state['values']['title'], $form_state['values']['module'], $form_state['values']['delta']);
    db_query("DELETE FROM {blocks_roles} WHERE module = '%s' AND delta = '%s'", $form_state['values']['module'], $form_state['values']['delta']);
    foreach (array_filter($form_state['values']['roles']) as $rid) {
      db_query("INSERT INTO {blocks_roles} (rid, module, delta) VALUES (%d, '%s', '%s')", $rid, $form_state['values']['module'], $form_state['values']['delta']);
    }
    module_invoke($form_state['values']['module'], 'block', 'save', $form_state['values']['delta'], $form_state['values']);
    drupal_set_message(t('The block configuration has been saved.'));
    cache_clear_all();
    $form_state['redirect'] = 'admin/build/block';
    return;
  }
}

/**
 * Menu callback: display the custom block addition form.
 */
function block_add_block_form(&$form_state) {
  return block_admin_configure($form_state, 'block', NULL);
}

function block_add_block_form_validate($form, &$form_state) {
  if (empty($form_state['values']['info']) || db_result(db_query("SELECT COUNT(*) FROM {boxes} WHERE info = '%s'", $form_state['values']['info']))) {
    form_set_error('info', t('Please ensure that each block description is unique.'));
  }
}

/**
 * Save the new custom block.
 */
function block_add_block_form_submit($form, &$form_state) {
  db_query("INSERT INTO {boxes} (body, info, format) VALUES  ('%s', '%s', %d)", $form_state['values']['body'], $form_state['values']['info'], $form_state['values']['format']);
  $delta = db_last_insert_id('boxes', 'bid');

  foreach (list_themes() as $key => $theme) {
    if ($theme->status) {
      db_query("INSERT INTO {blocks} (visibility, pages, custom, title, module, theme, status, weight, delta) VALUES(%d, '%s', %d, '%s', '%s', '%s', %d, %d, %d)", $form_state['values']['visibility'], trim($form_state['values']['pages']), $form_state['values']['custom'], $form_state['values']['title'], $form_state['values']['module'], $theme->name, 0, 0, $delta);
    }
  }

  foreach (array_filter($form_state['values']['roles']) as $rid) {
    db_query("INSERT INTO {blocks_roles} (rid, module, delta) VALUES (%d, '%s', '%s')", $rid, $form_state['values']['module'], $delta);
  }

  drupal_set_message(t('The block has been created.'));
  cache_clear_all();

  $form_state['redirect'] = 'admin/build/block';
  return;
}

/**
 * Menu callback; confirm deletion of custom blocks.
 */
function block_box_delete(&$form_state, $bid = 0) {
  $box = block_box_get($bid);
  $form['info'] = array('#type' => 'hidden', '#value' => $box['info'] ? $box['info'] : $box['title']);
  $form['bid'] = array('#type' => 'hidden', '#value' => $bid);

  return confirm_form($form, t('Are you sure you want to delete the block %name?', array('%name' => $box['info'])), 'admin/build/block', '', t('Delete'), t('Cancel'));
}

/**
 * Deletion of custom blocks.
 */
function block_box_delete_submit($form, &$form_state) {
  db_query('DELETE FROM {boxes} WHERE bid = %d', $form_state['values']['bid']);
  db_query("DELETE FROM {blocks} WHERE module = 'block' AND delta = %d", $form_state['values']['bid']);
  drupal_set_message(t('The block %name has been removed.', array('%name' => $form_state['values']['info'])));
  cache_clear_all();
  $form_state['redirect'] = 'admin/build/block';
  return;
}
