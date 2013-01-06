<?php

/**
 * @file
 * Admin page callbacks for the block module.
 */

/**
 * Page callback: Attaches CSS for the block region demo.
 *
 * @see block_menu()
 */
function block_admin_demo($theme = NULL) {
  drupal_add_css(drupal_get_path('module', 'block') . '/block.admin.css');
  return '';
}

/**
 * Page callback: Shows the block administration page.
 *
 * @param $theme
 *   The theme to display the administration page for. If not provided, defaults
 *   to the currently used theme.
 *
 * @see block_menu()
 */
function block_admin_display($theme = NULL) {
  global $theme_key;

  drupal_theme_initialize();

  if (!isset($theme)) {
    // If theme is not specifically set, rehash for the current theme.
    $theme = $theme_key;
  }

  // Fetch and sort blocks.
  $blocks = block_admin_display_prepare_blocks($theme);

  return drupal_get_form('block_admin_display_form', $blocks, $theme);
}

/**
 * Prepares a list of blocks for display on the blocks administration page.
 *
 * @param $theme
 *   The machine-readable name of the theme whose blocks should be returned.
 *
 * @return
 *   An array of blocks, as returned by _block_rehash(), sorted by region in
 *   preparation for display on the blocks administration page.
 *
 * @see block_admin_display_form()
 */
function block_admin_display_prepare_blocks($theme) {
  $blocks = _block_rehash($theme);
  $compare_theme = &drupal_static('_block_compare:theme');
  $compare_theme = $theme;
  usort($blocks, '_block_compare');
  return $blocks;
}

/**
 * Form constructor for the main block administration form.
 *
 * @param $blocks
 *   An array of blocks, as returned by block_admin_display_prepare_blocks().
 * @param $theme
 *   A string representing the name of the theme to edit blocks for.
 * @param $block_regions
 *   (optional) An array of regions in which the blocks will be allowed to be
 *   placed. Defaults to all visible regions for the theme whose blocks are
 *   being configured. In all cases, a dummy region for disabled blocks will
 *   also be displayed.
 *
 * @return
 *   An array representing the form definition.
 *
 * @ingroup forms
 * @see block_admin_display_form_submit()
 */
function block_admin_display_form($form, &$form_state, $blocks, $theme, $block_regions = NULL) {
  $path = drupal_get_path('module', 'block');
  $form['#attached']['css'][] = $path . '/block.admin.css';
  $form['#attached']['library'][] = array('system', 'drupal.tableheader');
  $form['#attached']['library'][] = array('block', 'drupal.block');

  // Get a list of block regions if one was not provided.
  if (!isset($block_regions)) {
    $block_regions = system_region_list($theme, REGIONS_VISIBLE);
  }
  // Add a last region for disabled blocks.
  $block_regions_with_disabled = $block_regions + array(BLOCK_REGION_NONE => BLOCK_REGION_NONE);

  foreach ($block_regions_with_disabled as $region => $title) {
    $form['#attached']['drupal_add_tabledrag'][] = array('blocks', 'match', 'sibling', 'block-region-select', 'block-region-' . $region, NULL, FALSE);
    $form['#attached']['drupal_add_tabledrag'][] = array('blocks', 'order', 'sibling', 'block-weight', 'block-weight-' . $region);
  }

  // Weights range from -delta to +delta, so delta should be at least half
  // of the amount of blocks present. This makes sure all blocks in the same
  // region get an unique weight.
  $weight_delta = round(count($blocks) / 2);

  // Build the form tree.
  $form['edited_theme'] = array(
    '#type' => 'value',
    '#value' => $theme,
  );
  $form['block_regions'] = array(
    '#type' => 'value',
    '#value' => $block_regions_with_disabled,
  );
  $form['blocks'] = array();
  $form['#tree'] = TRUE;

  foreach ($blocks as $key => $instance) {
    $block = $instance->getConfig();
    $form['blocks'][$key]['config_id'] = array(
      '#type' => 'value',
      '#value' => $block['config_id'],
    );
    $info = $instance->getDefinition();
    $form['blocks'][$key]['info'] = array(
      '#markup' => check_plain($info['subject']),
    );
    $form['blocks'][$key]['theme'] = array(
      '#type' => 'hidden',
      '#value' => $theme,
    );
    $form['blocks'][$key]['weight'] = array(
      '#type' => 'weight',
      '#default_value' => $block['weight'],
      '#delta' => $weight_delta,
      '#title_display' => 'invisible',
      '#title' => t('Weight for @block block', array('@block' => $info['subject'])),
    );
    $form['blocks'][$key]['region'] = array(
      '#type' => 'select',
      '#default_value' => $block['region'] != BLOCK_REGION_NONE ? $block['region'] : NULL,
      '#empty_value' => BLOCK_REGION_NONE,
      '#title_display' => 'invisible',
      '#title' => t('Region for @block block', array('@block' => $info['subject'])),
      '#options' => $block_regions,
    );
    $links['configure'] = array(
      'title' => t('configure'),
      'href' => 'admin/structure/block/manage/' . $block['config_id'] . '/' . $theme . '/configure',
    );
    $links['delete'] = array(
      'title' => t('delete'),
      'href' => 'admin/structure/block/manage/' . $block['config_id'] . '/' . $theme . '/delete',
    );
    $form['blocks'][$key]['operations'] = array(
      '#type' => 'operations',
      '#links' => $links,
    );
  }
  // Do not allow disabling the main system content block when it is present.
  if (isset($form['blocks']['system_main']['region'])) {
    $form['blocks']['system_main']['region']['#required'] = TRUE;
  }

  $form['actions'] = array(
    '#tree' => FALSE,
    '#type' => 'actions',
  );
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save blocks'),
    '#button_type' => 'primary',
  );

  return $form;
}

/**
 * Form submission handler for block_admin_display_form().
 *
 * @see block_admin_display_form()
 */
function block_admin_display_form_submit($form, &$form_state) {
  foreach ($form_state['values']['blocks'] as $block) {
    $config = config($block['config_id']);
    $config->set('weight', $block['weight']);
    $config->set('region', $block['region']);
    $config->save();
  }
  drupal_set_message(t('The block settings have been updated.'));
  cache_invalidate_tags(array('content' => TRUE));
}

/**
 * Sorts active blocks by region, then by weight; sorts inactive blocks by name.
 *
 * Callback for usort() in block_admin_display_prepare_blocks().
 */
function _block_compare($ainstance, $binstance) {
  global $theme_key;
  $a = $ainstance->getConfig();
  $b = $binstance->getConfig();

  // Theme should be set before calling this function, or the current theme
  // is being used.
  $theme = &drupal_static(__FUNCTION__ . ':theme');
  if (!isset($theme)) {
    $theme = $theme_key;
  }

  $regions = &drupal_static(__FUNCTION__ . ':regions');
  // We need the region list to correctly order by region.
  if (!isset($regions)) {
    $regions = array_flip(array_keys(system_region_list($theme)));
    $regions[BLOCK_REGION_NONE] = count($regions);
  }

  // Separate enabled from disabled.
  $status = $b['status'] - $a['status'];
  if ($status) {
    return $status;
  }
  // Sort by region (in the order defined by theme .info file).
  if ((!empty($a['region']) && !empty($b['region'])) && ($place = ($regions[$a['region']] - $regions[$b['region']]))) {
    return $place;
  }
  // Sort by weight, unless disabled.
  if ($a['region'] != BLOCK_REGION_NONE) {
    $weight = $a['weight'] - $b['weight'];
    if ($weight) {
      return $weight;
    }
  }
  // Sort by title.
  $ainfo = $ainstance->getDefinition();
  $binfo = $binstance->getDefinition();
  return strcmp($ainfo['subject'], $binfo['subject']);
}

/**
 * Form constructor for the block instance configuration form.
 *
 * @param string $plugin_id
 *   The plugin ID for the block instance.
 * @param string $theme
 *   (optional) The name of the theme for the block instance. If no theme is
 *   specified, the default theme will be used.
 *
 * @see block_menu()
 * @see custom_block_menu()
 * @see block_admin_configure_validate()
 * @see block_admin_configure_submit()
 *
 * @ingroup forms
 */
function block_admin_configure($form, &$form_state, $plugin_id, $theme = NULL) {
  $instance = block_load($plugin_id);
  $form['#instance'] = $instance;
  $config = $instance->getConfig();
  if (!isset($config['config_id']) && !$theme) {
    $theme = variable_get('theme_default', 'stark');
  }
  elseif (!$theme && isset($config['config_id'])) {
    list(, , , $theme) = explode('.', $config['config_id']);
  }
  $form['theme'] = array(
    '#type' => 'value',
    '#value' => $theme,
  );
  $form += $instance->form($form, $form_state);
  return $form;
}

/**
 * Form validation handler for block_admin_configure().
 *
 * @see block_admin_configure()
 * @see block_admin_configure_submit()
 */
function block_admin_configure_validate($form, &$form_state) {
  $form['#instance']->validate($form, $form_state);
}

/**
 * Form submission handler for block_admin_configure().
 *
 * @see block_admin_configure()
 * @see block_admin_configure_validate()
 */
function block_admin_configure_submit($form, &$form_state) {
  $form['#instance']->submit($form, $form_state);
  $config_values = $form['#instance']->getConfig();
  $machine_name = 'plugin.core.block.' . $form_state['values']['theme'] . '.' . $form_state['values']['machine_name'];
  $config = config($machine_name);
  $config->set('id', $form['#instance']->getPluginId());
  foreach ($config_values as $key => $value) {
    $config->set($key, $value);
  }
  $config->save();
  drupal_set_message(t('The block configuration has been saved.'));
  cache_invalidate_tags(array('content' => TRUE));
  $form_state['redirect'] = 'admin/structure/block/list/block_plugin_ui:' . $form_state['values']['theme'];
}

/**
 * Form constructor for the block instance deletion form.
 *
 * @param string $plugin_id
 *   The plugin ID for the block instance.
 * @param string $theme
 *   The name of the theme for the block instance.
 *
 * @see block_menu()
 * @see block_admin_block_delete_submit()
 */
function block_admin_block_delete($form, &$form_state, $plugin_id, $theme) {
  $block = block_load($plugin_id);
  $form['id'] = array('#type' => 'value', '#value' => $plugin_id);
  $form['theme'] = array('#type' => 'value', '#value' => $theme);
  $definition = $block->getDefinition();
  $config = $block->getConfig();
  $subject = empty($config['subject']) ? $definition['subject'] : $config['subject'];
  $form['subject'] = array('#type' => 'value', '#value' => $subject);

  return confirm_form($form, t('Are you sure you want to delete the block %name?', array('%name' => $subject)), 'admin/structure/block', '', t('Delete'), t('Cancel'));
}

/**
 * Form submission handler for block_admin_block_delete().
 *
 * @see block_admin_block_delete()
 */
function block_admin_block_delete_submit($form, &$form_state) {
  $config = config($form_state['values']['id']);
  $config->delete();
  drupal_set_message(t('The block %name has been removed.', array('%name' => $form_state['values']['subject'])));
  $form_state['redirect'] = 'admin/structure/block/list/block_plugin_ui:' . $form_state['values']['theme'];
}

/**
 * Processes variables for block-admin-display-form.tpl.php.
 *
 * The $variables array contains the following arguments:
 * - $form
 *
 * @see block-admin-display.tpl.php
 * @see theme_block_admin_display()
 */
function template_preprocess_block_admin_display_form(&$variables) {
  $variables['block_regions'] = $variables['form']['block_regions']['#value'];
  if (isset($variables['block_regions'][BLOCK_REGION_NONE])) {
    $variables['block_regions'][BLOCK_REGION_NONE] = t('Disabled');
  }

  foreach ($variables['block_regions'] as $key => $value) {
    // Initialize an empty array for the region.
    $variables['block_listing'][$key] = array();
  }

  // Initialize disabled blocks array.
  $variables['block_listing'][BLOCK_REGION_NONE] = array();

  // Add each block in the form to the appropriate place in the block listing.
  foreach (element_children($variables['form']['blocks']) as $i) {
    $block = &$variables['form']['blocks'][$i];

    // Fetch the region for the current block.
    $region = (isset($block['region']['#default_value']) ? $block['region']['#default_value'] : BLOCK_REGION_NONE);

    // Set special classes needed for table drag and drop.
    $block['region']['#attributes']['class'] = array('block-region-select', 'block-region-' . $region);
    $block['weight']['#attributes']['class'] = array('block-weight', 'block-weight-' . $region);

    $variables['block_listing'][$region][$i] = new stdClass();
    $variables['block_listing'][$region][$i]->row_class = !empty($block['#attributes']['class']) ? implode(' ', $block['#attributes']['class']) : '';
    $variables['block_listing'][$region][$i]->block_modified = !empty($block['#attributes']['class']) && in_array('block-modified', $block['#attributes']['class']);
    $variables['block_listing'][$region][$i]->block_title = drupal_render($block['info']);
    $variables['block_listing'][$region][$i]->region_select = drupal_render($block['region']) . drupal_render($block['theme']);
    $variables['block_listing'][$region][$i]->weight_select = drupal_render($block['weight']);
    $variables['block_listing'][$region][$i]->operations = drupal_render($block['operations']);
    $variables['block_listing'][$region][$i]->printed = FALSE;
  }

  $variables['form_submit'] = drupal_render_children($variables['form']);
}
