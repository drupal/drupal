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
  $form['#attached']['js'][] = 'core/misc/tableheader.js';
  $form['#attached']['js'][] = $path . '/block.js';

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

  foreach ($blocks as $i => $block) {
    $key = $block['module'] . '_' . $block['delta'];
    $form['blocks'][$key]['module'] = array(
      '#type' => 'value',
      '#value' => $block['module'],
    );
    $form['blocks'][$key]['delta'] = array(
      '#type' => 'value',
      '#value' => $block['delta'],
    );
    $form['blocks'][$key]['info'] = array(
      '#markup' => check_plain($block['info']),
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
      '#title' => t('Weight for @block block', array('@block' => $block['info'])),
    );
    $form['blocks'][$key]['region'] = array(
      '#type' => 'select',
      '#default_value' => $block['region'] != BLOCK_REGION_NONE ? $block['region'] : NULL,
      '#empty_value' => BLOCK_REGION_NONE,
      '#title_display' => 'invisible',
      '#title' => t('Region for @block block', array('@block' => $block['info'])),
      '#options' => $block_regions,
    );
    $form['blocks'][$key]['configure'] = array(
      '#type' => 'link',
      '#title' => t('configure'),
      '#href' => 'admin/structure/block/manage/' . $block['module'] . '/' . $block['delta'] . '/configure',
    );
    if ($block['module'] == 'block') {
      $form['blocks'][$key]['delete'] = array(
        '#type' => 'link',
        '#title' => t('delete'),
        '#href' => 'admin/structure/block/manage/' . $block['module'] . '/' . $block['delta'] . '/delete',
     );
    }
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
  );

  return $form;
}

/**
 * Form submission handler for block_admin_display_form().
 *
 * @see block_admin_display_form()
 */
function block_admin_display_form_submit($form, &$form_state) {
  $transaction = db_transaction();
  try {
    foreach ($form_state['values']['blocks'] as $block) {
      $block['status'] = (int) ($block['region'] != BLOCK_REGION_NONE);
      $block['region'] = $block['status'] ? $block['region'] : '';
      db_update('block')
        ->fields(array(
          'status' => $block['status'],
          'weight' => $block['weight'],
          'region' => $block['region'],
        ))
        ->condition('module', $block['module'])
        ->condition('delta', $block['delta'])
        ->condition('theme', $block['theme'])
        ->execute();
    }
  }
  catch (Exception $e) {
    $transaction->rollback();
    watchdog_exception('block', $e);
    throw $e;
  }
  drupal_set_message(t('The block settings have been updated.'));
  cache_invalidate(array('content' => TRUE));
}

/**
 * Sorts active blocks by region, then by weight; sorts inactive blocks by name.
 *
 * Callback for usort() in block_admin_display_prepare_blocks().
 */
function _block_compare($a, $b) {
  global $theme_key;

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
  return strcmp($a['info'], $b['info']);
}

/**
 * Form constructor for the block configuration form.
 *
 * Also used by block_add_block_form() for adding a new custom block.
 *
 * @param $module
 *   Name of the module that implements the block to be configured.
 * @param $delta
 *   Unique ID of the block within the context of $module.
 *
 * @see block_menu()
 * @see block_admin_configure_validate()
 * @see block_admin_configure_submit()
 * @ingroup forms
 */
function block_admin_configure($form, &$form_state, $module, $delta) {
  $block = block_load($module, $delta);
  $form['module'] = array(
    '#type' => 'value',
    '#value' => $block->module,
  );
  $form['delta'] = array(
    '#type' => 'value',
    '#value' => $block->delta,
  );

  // Get the block subject for the page title.
  $info = module_invoke($block->module, 'block_info');
  if (isset($info[$block->delta])) {
    drupal_set_title(t("'%name' block", array('%name' => $info[$block->delta]['info'])), PASS_THROUGH);
  }

  $form['settings']['title'] = array(
    '#type' => 'textfield',
    '#title' => t('Block title'),
    '#maxlength' => 64,
    '#description' => $block->module == 'block' ? t('The title of the block as shown to the user.') : t('Override the default title for the block. Use <em>!placeholder</em> to display no title, or leave blank to use the default block title.', array('!placeholder' => '&lt;none&gt;')),
    '#default_value' => isset($block->title) ? $block->title : '',
    '#weight' => -19,
  );

  // Module-specific block configuration.
  if ($settings = module_invoke($block->module, 'block_configure', $block->delta)) {
    foreach ($settings as $k => $v) {
      $form['settings'][$k] = $v;
    }
  }

  // Region settings.
  $form['regions'] = array(
    '#type' => 'fieldset',
    '#title' => t('Region settings'),
    '#collapsible' => FALSE,
    '#description' => t('Specify in which themes and regions this block is displayed.'),
    '#tree' => TRUE,
  );

  $theme_default = variable_get('theme_default', 'stark');
  $admin_theme = variable_get('admin_theme');
  foreach (list_themes() as $key => $theme) {
    // Only display enabled themes
    if ($theme->status) {
      $region = db_query("SELECT region FROM {block} WHERE module = :module AND delta = :delta AND theme = :theme", array(
        ':module' => $block->module,
        ':delta' => $block->delta,
        ':theme' => $key,
      ))->fetchField();

      // Use a meaningful title for the main site theme and administrative
      // theme.
      $theme_title = $theme->info['name'];
      if ($key == $theme_default) {
        $theme_title = t('!theme (default theme)', array('!theme' => $theme_title));
      }
      elseif ($admin_theme && $key == $admin_theme) {
        $theme_title = t('!theme (administration theme)', array('!theme' => $theme_title));
      }
      $form['regions'][$key] = array(
        '#type' => 'select',
        '#title' => $theme_title,
        '#default_value' => !empty($region) && $region != -1 ? $region : NULL,
        '#empty_value' => BLOCK_REGION_NONE,
        '#options' => system_region_list($key, REGIONS_VISIBLE),
        '#weight' => ($key == $theme_default ? 9 : 10),
      );
    }
  }

  // Visibility settings.
  $form['visibility_title'] = array(
    '#type' => 'item',
    '#title' => t('Visibility settings'),
  );
  $form['visibility'] = array(
    '#type' => 'vertical_tabs',
    '#attached' => array(
      'js' => array(drupal_get_path('module', 'block') . '/block.js'),
    ),
  );

  // Per-path visibility.
  $form['visibility']['path'] = array(
    '#type' => 'fieldset',
    '#title' => t('Pages'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#group' => 'visibility',
    '#weight' => 0,
  );

  $access = user_access('use PHP for settings');
  if (isset($block->visibility) && $block->visibility == BLOCK_VISIBILITY_PHP && !$access) {
    $form['visibility']['path']['visibility'] = array(
      '#type' => 'value',
      '#value' => BLOCK_VISIBILITY_PHP,
    );
    $form['visibility']['path']['pages'] = array(
      '#type' => 'value',
      '#value' => isset($block->pages) ? $block->pages : '',
    );
  }
  else {
    $options = array(
      BLOCK_VISIBILITY_NOTLISTED => t('All pages except those listed'),
      BLOCK_VISIBILITY_LISTED => t('Only the listed pages'),
    );
    $description = t("Specify pages by using their paths. Enter one path per line. The '*' character is a wildcard. Example paths are %user for the current user's page and %user-wildcard for every user page. %front is the front page.", array('%user' => 'user', '%user-wildcard' => 'user/*', '%front' => '<front>'));

    if (module_exists('php') && $access) {
      $options += array(BLOCK_VISIBILITY_PHP => t('Pages on which this PHP code returns <code>TRUE</code> (experts only)'));
      $title = t('Pages or PHP code');
      $description .= ' ' . t('If the PHP option is chosen, enter PHP code between %php. Note that executing incorrect PHP code can break your Drupal site.', array('%php' => '<?php ?>'));
    }
    else {
      $title = t('Pages');
    }
    $form['visibility']['path']['visibility'] = array(
      '#type' => 'radios',
      '#title' => t('Show block on specific pages'),
      '#options' => $options,
      '#default_value' => isset($block->visibility) ? $block->visibility : BLOCK_VISIBILITY_NOTLISTED,
    );
    $form['visibility']['path']['pages'] = array(
      '#type' => 'textarea',
      '#title' => '<span class="element-invisible">' . $title . '</span>',
      '#default_value' => isset($block->pages) ? $block->pages : '',
      '#description' => $description,
    );
  }

  // Configure the block visibility per language.
  if (module_exists('language') && language_multilingual()) {
    $configurable_language_types = language_types_get_configurable();
    $existing_language_settings = db_query("SELECT type, langcode FROM {block_language} WHERE module = :module AND delta = :delta", array(
      ':module' => $form['module']['#value'],
      ':delta' => $form['delta']['#value'],
    ))->fetchAll();
    $default_langcode_options = array();
    $default_language_type = $configurable_language_types[0];
    foreach ($existing_language_settings as $setting) {
      $default_langcode_options[] = $setting->langcode;
      // Overwrite default language type if we have it set. Although this
      // theoretically would allow per language type association, our UI
      // only allows language type association overall for a block, so we
      // only need a single value.
      $default_language_type = $setting->type;
    }

    // Fetch the enabled languages.
    $enabled_languages = language_list(TRUE);
    foreach ($enabled_languages as $language) {
      // @TODO $language->name is not wrapped with t(), it should be replaced
      // by CMI translation implementation.
      $langcodes_options[$language->langcode] = $language->name;
    }
    $form['visibility']['language'] = array(
      '#type' => 'fieldset',
      '#title' => t('Languages'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#group' => 'visibility',
      '#weight' => 5,
    );
    // If there are multiple configurable language types, let the user pick
    // which one should be applied to this visibility setting. This way users
    // can limit blocks by interface language or content language for exmaple.
    $language_types = language_types_info();
    $language_type_options = array();
    foreach ($configurable_language_types as $type_key) {
      $language_type_options[$type_key] = $language_types[$type_key]['name'];
    }
    $form['visibility']['language']['language_type'] = array(
      '#type' => 'radios',
      '#title' => t('Language type'),
      '#options' => $language_type_options,
      '#default_value' => $default_language_type,
      '#access' => count($language_type_options) > 1,
    );
    $form['visibility']['language']['langcodes'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Show this block only for specific languages'),
      '#default_value' => $default_langcode_options,
      '#options' => $langcodes_options,
      '#description' => t('Show this block only for the selected language(s). If you select no languages, the block will be visibile in all languages.'),
    );
  }

  // Per-role visibility.
  $default_role_options = db_query("SELECT rid FROM {block_role} WHERE module = :module AND delta = :delta", array(
    ':module' => $block->module,
    ':delta' => $block->delta,
  ))->fetchCol();
  $role_options = array_map('check_plain', user_roles());
  $form['visibility']['role'] = array(
    '#type' => 'fieldset',
    '#title' => t('Roles'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#group' => 'visibility',
    '#weight' => 10,
  );
  $form['visibility']['role']['roles'] = array(
    '#type' => 'checkboxes',
    '#title' => t('Show block for specific roles'),
    '#default_value' => $default_role_options,
    '#options' => $role_options,
    '#description' => t('Show this block only for the selected role(s). If you select no roles, the block will be visible to all users.'),
  );

  // Per-user visibility.
  $form['visibility']['user'] = array(
    '#type' => 'fieldset',
    '#title' => t('Users'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#group' => 'visibility',
    '#weight' => 20,
  );
  $form['visibility']['user']['custom'] = array(
    '#type' => 'radios',
    '#title' => t('Customizable per user'),
    '#options' => array(
      BLOCK_CUSTOM_FIXED => t('Not customizable'),
      BLOCK_CUSTOM_ENABLED => t('Customizable, visible by default'),
      BLOCK_CUSTOM_DISABLED => t('Customizable, hidden by default'),
    ),
    '#description' => t('Allow individual users to customize the visibility of this block in their account settings.'),
    '#default_value' => isset($block->custom) ? $block->custom : BLOCK_CUSTOM_FIXED,
  );

  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save block'),
  );

  return $form;
}

/**
 * Form validation handler for block_admin_configure().
 *
 * @see block_admin_configure()
 * @see block_admin_configure_submit()
 */
function block_admin_configure_validate($form, &$form_state) {
  if ($form_state['values']['module'] == 'block') {
    $custom_block_exists = (bool) db_query_range('SELECT 1 FROM {block_custom} WHERE bid <> :bid AND info = :info', 0, 1, array(
      ':bid' => $form_state['values']['delta'],
      ':info' => $form_state['values']['info'],
    ))->fetchField();
    if (empty($form_state['values']['info']) || $custom_block_exists) {
      form_set_error('info', t('Ensure that each block description is unique.'));
    }
  }
}

/**
 * Form submission handler for block_admin_configure().
 *
 * @see block_admin_configure()
 * @see block_admin_configure_validate()
 */
function block_admin_configure_submit($form, &$form_state) {
  if (!form_get_errors()) {
    $transaction = db_transaction();
    try {
      db_update('block')
        ->fields(array(
          'visibility' => (int) $form_state['values']['visibility'],
          'pages' => trim($form_state['values']['pages']),
          'custom' => (int) $form_state['values']['custom'],
          'title' => $form_state['values']['title'],
        ))
        ->condition('module', $form_state['values']['module'])
        ->condition('delta', $form_state['values']['delta'])
        ->execute();

      db_delete('block_role')
        ->condition('module', $form_state['values']['module'])
        ->condition('delta', $form_state['values']['delta'])
        ->execute();
      $query = db_insert('block_role')->fields(array('rid', 'module', 'delta'));
      foreach (array_filter($form_state['values']['roles']) as $rid) {
        $query->values(array(
          'rid' => $rid,
          'module' => $form_state['values']['module'],
          'delta' => $form_state['values']['delta'],
        ));
      }
      $query->execute();

      // Store regions per theme for this block.
      foreach ($form_state['values']['regions'] as $theme => $region) {
        db_merge('block')
          ->key(array('theme' => $theme, 'delta' => $form_state['values']['delta'], 'module' => $form_state['values']['module']))
          ->fields(array(
            'region' => ($region == BLOCK_REGION_NONE ? '' : $region),
            'pages' => trim($form_state['values']['pages']),
            'status' => (int) ($region != BLOCK_REGION_NONE),
          ))
          ->execute();
      }

      // Update the block visibility settings if we have settings to store
      // for the existing languages.
      if (module_exists('language') && isset($form_state['values']['langcodes'])) {
        db_delete('block_language')
          ->condition('module', $form_state['values']['module'])
          ->condition('delta', $form_state['values']['delta'])
          ->execute();
        $query = db_insert('block_language')->fields(array(
          'type', 'langcode', 'module', 'delta'
        ));
        foreach (array_filter($form_state['values']['langcodes']) as $langcode) {
          $query->values(array(
            'type' => $form_state['values']['language_type'],
            'langcode' => $langcode,
            'module' => $form_state['values']['module'],
            'delta' => $form_state['values']['delta'],
          ));
        }
        $query->execute();
      }

      module_invoke($form_state['values']['module'], 'block_save', $form_state['values']['delta'], $form_state['values']);
    }
    catch (Exception $e) {
      $transaction->rollback();
      watchdog_exception('block', $e);
      throw $e;
    }
    drupal_set_message(t('The block configuration has been saved.'));
    cache_invalidate(array('content' => TRUE));
    $form_state['redirect'] = 'admin/structure/block';
  }
}

/**
 * Form constructor for the add block form.
 *
 * @see block_menu()
 * @see block_add_block_form_validate()
 * @see block_add_block_form_submit()
 * @ingroup forms
 */
function block_add_block_form($form, &$form_state) {
  return block_admin_configure($form, $form_state, 'block', NULL);
}

/**
 * Form validation handler for block_add_block_form().
 *
 * @see block_add_block_form()
 * @see block_add_block_form_submit()
 */
function block_add_block_form_validate($form, &$form_state) {
  $custom_block_exists = (bool) db_query_range('SELECT 1 FROM {block_custom} WHERE info = :info', 0, 1, array(':info' => $form_state['values']['info']))->fetchField();

  if (empty($form_state['values']['info']) || $custom_block_exists) {
    form_set_error('info', t('Ensure that each block description is unique.'));
  }
}

/**
 * Form submission handler for block_add_block_form().
 *
 * Saves the new custom block.
 *
 * @see block_add_block_form()
 * @see block_add_block_form_validate()
 */
function block_add_block_form_submit($form, &$form_state) {
  $delta = db_insert('block_custom')
    ->fields(array(
      'body' => $form_state['values']['body']['value'],
      'info' => $form_state['values']['info'],
      'format' => $form_state['values']['body']['format'],
    ))
    ->execute();
  // Store block delta to allow other modules to work with new block.
  $form_state['values']['delta'] = $delta;

  $query = db_insert('block')->fields(array('visibility', 'pages', 'custom', 'title', 'module', 'theme', 'status', 'weight', 'delta', 'cache'));
  foreach (list_themes() as $key => $theme) {
    if ($theme->status) {
      $query->values(array(
        'visibility' => (int) $form_state['values']['visibility'],
        'pages' => trim($form_state['values']['pages']),
        'custom' => (int) $form_state['values']['custom'],
        'title' => $form_state['values']['title'],
        'module' => $form_state['values']['module'],
        'theme' => $theme->name,
        'status' => 0,
        'weight' => 0,
        'delta' => $delta,
        'cache' => DRUPAL_NO_CACHE,
      ));
    }
  }
  $query->execute();

  $query = db_insert('block_role')->fields(array('rid', 'module', 'delta'));
  foreach (array_filter($form_state['values']['roles']) as $rid) {
    $query->values(array(
      'rid' => $rid,
      'module' => $form_state['values']['module'],
      'delta' => $delta,
    ));
  }
  $query->execute();

  // Store regions per theme for this block.
  foreach ($form_state['values']['regions'] as $theme => $region) {
    db_merge('block')
      ->key(array('theme' => $theme, 'delta' => $delta, 'module' => $form_state['values']['module']))
      ->fields(array(
        'region' => ($region == BLOCK_REGION_NONE ? '' : $region),
        'pages' => trim($form_state['values']['pages']),
        'status' => (int) ($region != BLOCK_REGION_NONE),
      ))
      ->execute();
  }

  // Update the block visibility settings if we have settings to store
  // for the existing languages.
  if (module_exists('language') && isset($form_state['values']['langcodes'])) {
    $query = db_insert('block_language')->fields(array(
      'type', 'langcode', 'module', 'delta'
    ));
    foreach (array_filter($form_state['values']['langcodes']) as $langcode) {
      $query->values(array(
        'type' => $form_state['values']['language_type'],
        'langcode' => $langcode,
        'module' => $form_state['values']['module'],
        'delta' => $form_state['values']['delta'],
      ));
    }
    $query->execute();
  }

  drupal_set_message(t('The block has been created.'));
  cache_invalidate(array('content' => TRUE));
  $form_state['redirect'] = 'admin/structure/block';
}

/**
 * Form constructor for the custom block deletion form.
 *
 * @param $module
 *   The name of the module that implements the block to be deleted. This should
 *   always equal 'block' since it only allows custom blocks to be deleted.
 * @param $delta
 *   The unique ID of the block within the context of $module.
 *
 * @see block_menu()
 * @see block_custom_block_delete_submit()
 */
function block_custom_block_delete($form, &$form_state, $module, $delta) {
  $block = block_load($module, $delta);
  $custom_block = block_custom_block_get($block->delta);
  $form['info'] = array('#type' => 'hidden', '#value' => $custom_block['info'] ? $custom_block['info'] : $custom_block['title']);
  $form['bid'] = array('#type' => 'hidden', '#value' => $block->delta);

  return confirm_form($form, t('Are you sure you want to delete the block %name?', array('%name' => $custom_block['info'])), 'admin/structure/block', '', t('Delete'), t('Cancel'));
}

/**
 * Form submission handler for block_custom_block_delete().
 *
 * @see block_custom_block_delete()
 */
function block_custom_block_delete_submit($form, &$form_state) {
  db_delete('block_custom')
    ->condition('bid', $form_state['values']['bid'])
    ->execute();
  db_delete('block')
    ->condition('module', 'block')
    ->condition('delta', $form_state['values']['bid'])
    ->execute();
  db_delete('block_role')
    ->condition('module', 'block')
    ->condition('delta', $form_state['values']['bid'])
    ->execute();
  db_delete('block_language')
    ->condition('module', 'block')
    ->condition('delta', $form_state['values']['bid'])
    ->execute();

  drupal_set_message(t('The block %name has been removed.', array('%name' => $form_state['values']['info'])));
  cache_invalidate(array('content' => TRUE));
  $form_state['redirect'] = 'admin/structure/block';
  return;
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
    $variables['block_listing'][$region][$i]->configure_link = drupal_render($block['configure']);
    $variables['block_listing'][$region][$i]->delete_link = !empty($block['delete']) ? drupal_render($block['delete']) : '';
    $variables['block_listing'][$region][$i]->printed = FALSE;
  }

  $variables['form_submit'] = drupal_render_children($variables['form']);
}
