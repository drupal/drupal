<?php
// $Id: block.admin.inc,v 1.10 2007/11/14 05:59:38 dries Exp $

/**
 * @file
 * Admin page callbacks for the block module.
 */

/**
 * Menu callback for admin/build/block.
 */
function block_admin_display($theme = NULL) {
  global $custom_theme;

  // If non-default theme configuration has been selected, set the custom theme.
  $custom_theme = isset($theme) ? $theme : variable_get('theme_default', 'garland');

  // Fetch and sort blocks
  $blocks = _block_rehash();
  usort($blocks, '_block_compare');

  return drupal_get_form('block_admin_display_form', $blocks, $theme);
}

/**
 * Generate main block administration form.
 */
function block_admin_display_form(&$form_state, $blocks, $theme = NULL) {
  global $theme_key, $custom_theme;

  // Add CSS
  drupal_add_css(drupal_get_path('module', 'block') .'/block.css', 'module', 'all', FALSE);

  // If non-default theme configuration has been selected, set the custom theme.
  $custom_theme = isset($theme) ? $theme : variable_get('theme_default', 'garland');
  init_theme();

  $throttle = module_exists('throttle');
  $block_regions = array(BLOCK_REGION_NONE => '<'. t('none') .'>') + system_region_list($theme_key);

  // Build form tree
  $form = array(
    '#action' => arg(3) ? url('admin/build/block/list/'. $theme_key) : url('admin/build/block'),
    '#tree' => TRUE,
    '#cache' => TRUE,
    '#prefix' => '<div id="block-admin-display-form-wrapper">',
    '#suffix' => '</div>',
  );
  foreach ($blocks as $i => $block) {
    $key = $block['module'] .'_'. $block['delta'];
    $form[$key]['module'] = array(
      '#type' => 'value',
      '#value' => $block['module'],
    );
    $form[$key]['delta'] = array(
      '#type' => 'value',
      '#value' => $block['delta'],
    );
    $form[$key]['info'] = array(
      '#value' => check_plain($block['info'])
    );
    $form[$key]['theme'] = array(
      '#type' => 'hidden',
      '#value' => $theme_key
    );
    $form[$key]['weight'] = array(
      '#type' => 'weight',
      '#default_value' => $block['weight'],
    );
    $form[$key]['region'] = array(
      '#type' => 'select',
      '#default_value' => $block['status'] ? (isset($block['region']) ? $block['region'] : system_default_region($theme_key)) : BLOCK_REGION_NONE,
      '#options' => $block_regions,
    );

    if ($throttle) {
      $form[$key]['throttle'] = array('#type' => 'checkbox', '#default_value' => isset($block['throttle']) ? $block['throttle'] : FALSE);
    }
    $form[$key]['configure'] = array('#value' => l(t('configure'), 'admin/build/block/configure/'. $block['module'] .'/'. $block['delta']));
    if ($block['module'] == 'block') {
      $form[$key]['delete'] = array('#value' => l(t('delete'), 'admin/build/block/delete/'. $block['delta']));
    }
  }

  // Attach the AHAH events to the submit button. Set the AHAH selector to every
  // select element in the form. The AHAH event could be attached to every select
  // element individually, but using the selector is more efficient, especially
  // on a page where hundreds of AHAH enabled elements may be present.
  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save blocks'),
    '#ahah' => array(
      'path' => 'admin/build/block/list/js/'. $theme_key,
      'selector' => '#block-admin-display-form-wrapper select',
      'wrapper' => 'block-admin-display-form-wrapper',
      'event' => 'change',
      'effect' => 'fade',
    ),
  );

  return $form;
}

/**
 * Process main block administration form submission.
 */
function block_admin_display_form_submit($form, &$form_state) {
  foreach ($form_state['values'] as $block) {
    $block['status'] = $block['region'] != BLOCK_REGION_NONE;
    $block['region'] = $block['status'] ? $block['region'] : '';
    db_query("UPDATE {blocks} SET status = %d, weight = %d, region = '%s', throttle = %d WHERE module = '%s' AND delta = '%s' AND theme = '%s'", $block['status'], $block['weight'], $block['region'], isset($block['throttle']) ? $block['throttle'] : 0, $block['module'], $block['delta'], $block['theme']);
  }
  drupal_set_message(t('The block settings have been updated.'));
  cache_clear_all();
}

/**
 * Javascript callback for AHAH replacement. Re-generate the form with the
 * updated values and return necessary html.
 */
function block_admin_display_js($theme = NULL) {
  // Load the cached form.
  $form_cache = cache_get('form_'. $_POST['form_build_id'], 'cache_form');

  // Set the new weights and regions for each block.
  $blocks = array();
  foreach (element_children($form_cache->data) as $key) {
    $field = $form_cache->data[$key];
    if (isset($field['info'])) {
      $block = array(
        'module'   => $field['module']['#value'],
        'delta'    => $field['delta']['#value'],
        'info'     => html_entity_decode($field['info']['#value'], ENT_QUOTES),
        'region'   => $_POST[$key]['region'],
        'weight'   => $_POST[$key]['weight'],
        'status'   => $_POST[$key]['region'] == BLOCK_REGION_NONE ? 0 : 1,
      );

      $throttle = module_exists('throttle');
      if ($throttle) {
        $block['throttle'] = !empty($_POST[$key]['throttle']);
      }

      if ($block['weight'] != $form_cache->data[$key]['weight']['#default_value'] || $block['region'] != $form_cache->data[$key]['region']['#default_value']) {
        $changed_block = $block['module'] .'_'. $block['delta'];
      }

      $blocks[] = $block;
    }
  }

  // Resort the blocks with the new weights.
  usort($blocks, '_block_compare');

  // Create a form in the new order.
  $form_state = array('submitted' => FALSE);
  $form = block_admin_display_form($form_state, $blocks, $theme);

  // Maintain classes set on individual blocks.
  foreach (element_children($form_cache->data) as $key) {
    if (isset($form_cache->data[$key]['#attributes'])) {
      $form[$key]['#attributes'] = $form_cache->data[$key]['#attributes'];
    }
  }

  // Preserve the order of the new form while merging the previous data.
  $form_order = array_flip(array_keys($form)); // Save the form order.
  $form = array_merge($form_cache->data, $form);  // Merge the data.
  $form = array_merge($form_order, $form); // Put back into the correct order.

  // Add a permanent class to the changed block.
  $form[$changed_block]['#attributes']['class'] = 'block-modified';

  cache_set('form_'. $_POST['form_build_id'], $form, 'cache_form', $form_cache->expire);

  // Add a temporary class to mark the new AHAH content.
  $form[$changed_block]['#attributes']['class'] = empty($form[$changed_block]['#attributes']['class']) ? 'ahah-new-content' : $form[$changed_block]['#attributes']['class'] .' ahah-new-content';
  $form['js_modified'] = array(
    '#type' => 'value',
    '#value' => TRUE,
  );

  $form['#post'] = $_POST;
  $form['#theme'] = 'block_admin_display_form';

  // Add messages to our output.
  drupal_set_message(t('Your settings will not be saved until you click the <em>Save blocks</em> button.'), 'warning');

  // Render the form.
  drupal_alter('form', $form, array(), 'block_admin_display_form');
  $form = form_builder('block_admin_display_form', $form, $form_state);

  // Remove the wrapper from the form to prevent duplicate div IDs.
  unset($form['#prefix'], $form['#suffix']);

  $output = drupal_render($form);

  // Return the output in JSON format.
  drupal_json(array('status' => TRUE, 'data' => $output));
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
  // Sort by region.
  $place = strcmp($a['region'], $b['region']);
  if ($place) {
    return $place;
  }
  // Sort by weight.
  $weight = $a['weight'] - $b['weight'];
  if ($weight) {
    return $weight;
  }
  // Sort by title.
  return strcmp($a['info'], $b['info']);
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
      db_query("INSERT INTO {blocks} (visibility, pages, custom, title, module, theme, status, weight, delta, cache) VALUES(%d, '%s', %d, '%s', '%s', '%s', %d, %d, %d, %d)", $form_state['values']['visibility'], trim($form_state['values']['pages']), $form_state['values']['custom'], $form_state['values']['title'], $form_state['values']['module'], $theme->name, 0, 0, $delta, BLOCK_NO_CACHE);
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

/**
 * Process variables for block-admin-display.tpl.php.
 *
 * The $variables array contains the following arguments:
 * - $form
 *
 * @see block-admin-display.tpl.php
 * @see theme_block_admin_display()
 */
function template_preprocess_block_admin_display_form(&$variables) {
  global $theme_key;

  $variables['throttle'] = module_exists('throttle');
  $block_regions = system_region_list($theme_key);

  // Highlight regions on page to provide visual reference.
  foreach ($block_regions as $key => $value) {
    drupal_set_content($key, '<div class="block-region">'. $value .'</div>');
  }

  // Setup to track previous region in loop.
  $last_region = '';
  foreach (element_children($variables['form']) as $i) {
    $block = &$variables['form'][$i];

    // Only take form elements that are blocks.
    if (isset($block['info'])) {
      // Fetch region for current block.
      $region = $block['region']['#default_value'];

      // Track first block listing to insert region header inside block_admin_display.tpl.php.
      $is_region_first = FALSE;
      if ($last_region != $region) {
        $is_region_first = TRUE;
        // Set region title. Block regions already translated.
        if ($region != BLOCK_REGION_NONE) {
          $region_title = drupal_ucfirst($block_regions[$region]);
        }
        else {
          $region_title = t('Disabled');
        }
      }

      $variables['block_listing'][$i]->is_region_first = $is_region_first;
      $variables['block_listing'][$i]->row_class = isset($block['#attributes']['class']) ? $block['#attributes']['class'] : '';
      $variables['block_listing'][$i]->block_modified = isset($block['#attributes']['class']) && strpos($block['#attributes']['class'], 'block-modified') !== FALSE ? TRUE : FALSE;
      $variables['block_listing'][$i]->region_title = $region_title;
      $variables['block_listing'][$i]->block_title =  drupal_render($block['info']);
      $variables['block_listing'][$i]->region_select = drupal_render($block['region']) . drupal_render($block['theme']);
      $variables['block_listing'][$i]->weight_select = drupal_render($block['weight']);
      $variables['block_listing'][$i]->throttle_check = $variables['throttle'] ? drupal_render($block['throttle']) : '';
      $variables['block_listing'][$i]->configure_link = drupal_render($block['configure']);
      $variables['block_listing'][$i]->delete_link = !empty($block['delete']) ? drupal_render($block['delete']) : '';

      $last_region = $region;
    }
  }

  $variables['messages'] = isset($variables['form']['js_modified']) ? theme('status_messages') : '';
  $variables['form_submit'] = drupal_render($variables['form']);
}
