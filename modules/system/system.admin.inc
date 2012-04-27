<?php

/**
 * @file
 * Admin page callbacks for the system module.
 */

/**
 * Menu callback; Provide the administration overview page.
 */
function system_main_admin_page($arg = NULL) {
  // If we received an argument, they probably meant some other page.
  // Let's 404 them since the menu system cannot be told we do not
  // accept arguments.
  if (isset($arg) && substr($arg, 0, 3) != 'by-') {
    return drupal_not_found();
  }

  // Check for status report errors.
  if (system_status(TRUE) && user_access('administer site configuration')) {
    drupal_set_message(t('One or more problems were detected with your Drupal installation. Check the <a href="@status">status report</a> for more information.', array('@status' => url('admin/reports/status'))), 'error');
  }
  $blocks = array();
  if ($admin = db_fetch_array(db_query("SELECT menu_name, mlid FROM {menu_links} WHERE link_path = 'admin' AND module = 'system'"))) {
    $result = db_query("
      SELECT m.*, ml.*
      FROM {menu_links} ml
      INNER JOIN {menu_router} m ON ml.router_path = m.path
      WHERE ml.link_path != 'admin/help' AND menu_name = '%s' AND ml.plid = %d AND hidden = 0", $admin);
    while ($item = db_fetch_array($result)) {
      _menu_link_translate($item);
      if (!$item['access']) {
        continue;
      }
      // The link 'description' either derived from the hook_menu 'description'
      // or entered by the user via menu module is saved as the title attribute.
      if (!empty($item['localized_options']['attributes']['title'])) {
        $item['description'] = $item['localized_options']['attributes']['title'];
      }
      $block = $item;
      $block['content'] = '';
      if ($item['block_callback'] && function_exists($item['block_callback'])) {
        $function = $item['block_callback'];
        $block['content'] .= $function();
      }
      $block['content'] .= theme('admin_block_content', system_admin_menu_block($item));
      // Prepare for sorting as in function _menu_tree_check_access().
      // The weight is offset so it is always positive, with a uniform 5-digits.
      $blocks[(50000 + $item['weight']) .' '. $item['title'] .' '. $item['mlid']] = $block;
    }
  }
  if ($blocks) {
    ksort($blocks);
    return theme('admin_page', $blocks);
  }
  else {
    return t('You do not have any administrative items.');
  }
}


/**
 * Provide a single block from the administration menu as a page.
 *
 * This function is often a destination for these blocks.
 * For example, 'admin/content/types' needs to have a destination to be valid
 * in the Drupal menu system, but too much information there might be
 * hidden, so we supply the contents of the block.
 *
 * @return
 *   The output HTML.
 */
function system_admin_menu_block_page() {
  $item = menu_get_item();
  if ($content = system_admin_menu_block($item)) {
    $output = theme('admin_block_content', $content);
  }
  else {
    $output = t('You do not have any administrative items.');
  }
  return $output;
}

/**
 * Menu callback; Sets whether the admin menu is in compact mode or not.
 *
 * @param $mode
 *   Valid values are 'on' and 'off'.
 */
function system_admin_compact_page($mode = 'off') {
  global $user;
  user_save($user, array('admin_compact_mode' => ($mode == 'on')));
  drupal_goto('admin');
}

/**
 * Menu callback; prints a listing of admin tasks for each installed module.
 */
function system_admin_by_module() {

  $modules = module_rebuild_cache();
  $menu_items = array();
  $help_arg = module_exists('help') ? drupal_help_arg() : FALSE;

  foreach ($modules as $file) {
    $module = $file->name;
    if ($module == 'help') {
      continue;
    }

    $admin_tasks = system_get_module_admin_tasks($module);

    // Only display a section if there are any available tasks.
    if (count($admin_tasks)) {

      // Check for help links.
      if ($help_arg && module_invoke($module, 'help', "admin/help#$module", $help_arg)) {
        $admin_tasks[100] = l(t('Get help'), "admin/help/$module");
      }

      // Sort.
      ksort($admin_tasks);

      $menu_items[$file->info['name']] = array($file->info['description'], $admin_tasks);
    }
  }
  return theme('system_admin_by_module', $menu_items);
}

/**
 * Menu callback: Displays the configuration overview page.
 */
function system_settings_overview() {
  // Check database setup if necessary
  if (function_exists('db_check_setup') && empty($_POST)) {
    db_check_setup();
  }

  $item = menu_get_item('admin/settings');
  $content = system_admin_menu_block($item);

  $output = theme('admin_block_content', $content);

  return $output;
}

/**
 * Form builder; This function allows selection of the theme to show in administration sections.
 *
 * @ingroup forms
 * @see system_settings_form()
 */
function system_admin_theme_settings() {
  $themes = system_theme_data();

  uasort($themes, 'system_sort_modules_by_info_name');

  $options[0] = '<'. t('System default') .'>';
  foreach ($themes as $theme) {
    $options[$theme->name] = $theme->info['name'];
  }

  $form['admin_theme'] = array(
    '#type' => 'select',
    '#options' => $options,
    '#title' => t('Administration theme'),
    '#description' => t('Choose which theme the administration pages should display in. If you choose "System default" the administration pages will use the same theme as the rest of the site.'),
    '#default_value' => variable_get('admin_theme', '0'),
  );

  $form['node_admin_theme'] = array(
    '#type' => 'checkbox',
    '#title' => t('Use administration theme for content editing'),
    '#description' => t('Use the administration theme when editing existing posts or creating new ones.'),
    '#default_value' => variable_get('node_admin_theme', '0'),
  );

  $form['#submit'][] = 'system_admin_theme_submit';
  return system_settings_form($form);
}

/**
 * Menu callback; displays a listing of all themes.
 *
 * @ingroup forms
 * @see system_themes_form_submit()
 */
function system_themes_form() {

  $themes = system_theme_data();

  uasort($themes, 'system_sort_modules_by_info_name');

  $status = array();
  $incompatible_core = array();
  $incompatible_php = array();

  foreach ($themes as $theme) {
    $screenshot = NULL;
    // Create a list which includes the current theme and all its base themes.
    if (isset($themes[$theme->name]->base_themes)) {
      $theme_keys = array_keys($themes[$theme->name]->base_themes);
      $theme_keys[] = $theme->name;
    }
    else {
      $theme_keys = array($theme->name);
    }
    // Look for a screenshot in the current theme or in its closest ancestor.
    foreach (array_reverse($theme_keys) as $theme_key) {
      if (isset($themes[$theme_key]) && file_exists($themes[$theme_key]->info['screenshot'])) {
        $screenshot = $themes[$theme_key]->info['screenshot'];
        break;
      }
    }
    $screenshot = $screenshot ? theme('image', $screenshot, t('Screenshot for %theme theme', array('%theme' => $theme->info['name'])), '', array('class' => 'screenshot'), FALSE) : t('no screenshot');

    $form[$theme->name]['screenshot'] = array('#value' => $screenshot);
    $form[$theme->name]['info'] = array(
      '#type' => 'value',
      '#value' => $theme->info,
    );
    $options[$theme->name] = '';

    if (!empty($theme->status) || $theme->name == variable_get('admin_theme', '0')) {
      $form[$theme->name]['operations'] = array('#value' => l(t('configure'), 'admin/build/themes/settings/'. $theme->name) );
    }
    else {
      // Dummy element for drupal_render. Cleaner than adding a check in the theme function.
      $form[$theme->name]['operations'] = array();
    }
    if (!empty($theme->status)) {
      $status[] = $theme->name;
    }
    else {
      // Ensure this theme is compatible with this version of core.
      if (!isset($theme->info['core']) || $theme->info['core'] != DRUPAL_CORE_COMPATIBILITY) {
        $incompatible_core[] = $theme->name;
      }
      if (version_compare(phpversion(), $theme->info['php']) < 0) {
        $incompatible_php[$theme->name] = $theme->info['php'];
      }
    }
  }

  $form['status'] = array(
    '#type' => 'checkboxes',
    '#options' => $options,
    '#default_value' => $status,
    '#incompatible_themes_core' => drupal_map_assoc($incompatible_core),
    '#incompatible_themes_php' => $incompatible_php,
  );
  $form['theme_default'] = array(
    '#type' => 'radios',
    '#options' => $options,
    '#default_value' => variable_get('theme_default', 'garland'),
  );
  $form['buttons']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save configuration'),
  );
  $form['buttons']['reset'] = array(
    '#type' => 'submit',
    '#value' => t('Reset to defaults'),
  );
  return $form;
}

/**
 * Process system_themes_form form submissions.
 */
function system_themes_form_submit($form, &$form_state) {
  drupal_clear_css_cache();

  // Store list of previously enabled themes and disable all themes
  $old_theme_list = $new_theme_list = array();
  foreach (list_themes() as $theme) {
    if ($theme->status) {
      $old_theme_list[] = $theme->name;
    }
  }
  db_query("UPDATE {system} SET status = 0 WHERE type = 'theme'");

  if ($form_state['values']['op'] == t('Save configuration')) {
    if (is_array($form_state['values']['status'])) {
      foreach ($form_state['values']['status'] as $key => $choice) {
        // Always enable the default theme, despite its status checkbox being checked:
        if ($choice || $form_state['values']['theme_default'] == $key) {
          system_initialize_theme_blocks($key);
          $new_theme_list[] = $key;
          db_query("UPDATE {system} SET status = 1 WHERE type = 'theme' and name = '%s'", $key);
        }
      }
    }
    if (($admin_theme = variable_get('admin_theme', '0')) != '0' && $admin_theme != $form_state['values']['theme_default']) {
      drupal_set_message(t('Please note that the <a href="!admin_theme_page">administration theme</a> is still set to the %admin_theme theme; consequently, the theme on this page remains unchanged. All non-administrative sections of the site, however, will show the selected %selected_theme theme by default.', array(
        '!admin_theme_page' => url('admin/settings/admin'),
        '%admin_theme' => $admin_theme,
        '%selected_theme' => $form_state['values']['theme_default'],
      )));
    }
    variable_set('theme_default', $form_state['values']['theme_default']);
  }
  else {
    // Revert to defaults: only Garland is enabled.
    variable_del('theme_default');
    db_query("UPDATE {system} SET status = 1 WHERE type = 'theme' AND name = 'garland'");
    $new_theme_list = array('garland');
  }

  list_themes(TRUE);
  menu_rebuild();
  drupal_rebuild_theme_registry();
  drupal_set_message(t('The configuration options have been saved.'));
  $form_state['redirect'] = 'admin/build/themes';

  // Notify locale module about new themes being enabled, so translations can
  // be imported. This might start a batch, and only return to the redirect
  // path after that.
  module_invoke('locale', 'system_update', array_diff($new_theme_list, $old_theme_list));

  return;
}

/**
 * Form builder; display theme configuration for entire site and individual themes.
 *
 * @param $key
 *   A theme name.
 * @return
 *   The form structure.
 * @ingroup forms
 * @see system_theme_settings_submit()
 */
function system_theme_settings(&$form_state, $key = '') {
  $directory_path = file_directory_path();
  file_check_directory($directory_path, FILE_CREATE_DIRECTORY, 'file_directory_path');

  // Default settings are defined in theme_get_settings() in includes/theme.inc
  if ($key) {
    $settings = theme_get_settings($key);
    $var = str_replace('/', '_', 'theme_'. $key .'_settings');
    $themes = system_theme_data();
    $features = $themes[$key]->info['features'];
  }
  else {
    $settings = theme_get_settings('');
    $var = 'theme_settings';
  }

  $form['var'] = array('#type' => 'hidden', '#value' => $var);

  // Check for a new uploaded logo, and use that instead.
  if ($file = file_save_upload('logo_upload', array('file_validate_is_image' => array()))) {
    $parts = pathinfo($file->filename);
    $filename = ($key) ? str_replace('/', '_', $key) .'_logo.'. $parts['extension'] : 'logo.'. $parts['extension'];

    // The image was saved using file_save_upload() and was added to the
    // files table as a temporary file. We'll make a copy and let the garbage
    // collector delete the original upload.
    if (file_copy($file, $filename, FILE_EXISTS_REPLACE)) {
      $_POST['default_logo'] = 0;
      $_POST['logo_path'] = $file->filepath;
      $_POST['toggle_logo'] = 1;
    }
  }

  // Check for a new uploaded favicon, and use that instead.
  if ($file = file_save_upload('favicon_upload')) {
    $parts = pathinfo($file->filename);
    $filename = ($key) ? str_replace('/', '_', $key) .'_favicon.'. $parts['extension'] : 'favicon.'. $parts['extension'];

    // The image was saved using file_save_upload() and was added to the
    // files table as a temporary file. We'll make a copy and let the garbage
    // collector delete the original upload.
    if (file_copy($file, $filename)) {
      $_POST['default_favicon'] = 0;
      $_POST['favicon_path'] = $file->filepath;
      $_POST['toggle_favicon'] = 1;
    }
  }

  // Toggle settings
  $toggles = array(
    'logo'                 => t('Logo'),
    'name'                 => t('Site name'),
    'slogan'               => t('Site slogan'),
    'mission'              => t('Mission statement'),
    'node_user_picture'    => t('User pictures in posts'),
    'comment_user_picture' => t('User pictures in comments'),
    'search'               => t('Search box'),
    'favicon'              => t('Shortcut icon'),
    'primary_links'        => t('Primary links'),
    'secondary_links'      => t('Secondary links'),
  );

  // Some features are not always available
  $disabled = array();
  if (!variable_get('user_pictures', 0)) {
    $disabled['toggle_node_user_picture'] = TRUE;
    $disabled['toggle_comment_user_picture'] = TRUE;
  }
  if (!module_exists('search')) {
    $disabled['toggle_search'] = TRUE;
  }

  $form['theme_settings'] = array(
    '#type' => 'fieldset',
    '#title' => t('Toggle display'),
    '#description' => t('Enable or disable the display of certain page elements.'),
  );
  foreach ($toggles as $name => $title) {
    if ((!$key) || in_array($name, $features)) {
      $form['theme_settings']['toggle_'. $name] = array('#type' => 'checkbox', '#title' => $title, '#default_value' => $settings['toggle_'. $name]);
      // Disable checkboxes for features not supported in the current configuration.
      if (isset($disabled['toggle_'. $name])) {
        $form['theme_settings']['toggle_'. $name]['#disabled'] = TRUE;
      }
    }
  }

  // System wide only settings.
  if (!$key) {
    // Create neat 2-column layout for the toggles
    $form['theme_settings'] += array(
      '#prefix' => '<div class="theme-settings-left">',
      '#suffix' => '</div>',
    );

    // Toggle node display.
    $node_types = node_get_types('names');
    if ($node_types) {
      $form['node_info'] = array(
        '#type' => 'fieldset',
        '#title' => t('Display post information on'),
        '#description' => t('Enable or disable the <em>submitted by Username on date</em> text when displaying posts of the following type.'),
        '#prefix' => '<div class="theme-settings-right">',
        '#suffix' => '</div>',
      );
      foreach ($node_types as $type => $name) {
        $form['node_info']["toggle_node_info_$type"] = array('#type' => 'checkbox', '#title' => check_plain($name), '#default_value' => $settings["toggle_node_info_$type"]);
      }
    }
  }
  elseif (!element_children($form['theme_settings'])) {
    // If there is no element in the theme settings fieldset then do not show
    // it -- but keep it in the form if another module wants to alter.
    $form['theme_settings']['#access'] = FALSE;
  }

  // Logo settings
  if ((!$key) || in_array('logo', $features)) {
    $form['logo'] = array(
      '#type' => 'fieldset',
      '#title' => t('Logo image settings'),
      '#description' => t('If toggled on, the following logo will be displayed.'),
      '#attributes' => array('class' => 'theme-settings-bottom'),
    );
    $form['logo']["default_logo"] = array(
      '#type' => 'checkbox',
      '#title' => t('Use the default logo'),
      '#default_value' => $settings['default_logo'],
      '#tree' => FALSE,
      '#description' => t('Check here if you want the theme to use the logo supplied with it.')
    );
    $form['logo']['logo_path'] = array(
      '#type' => 'textfield',
      '#title' => t('Path to custom logo'),
      '#default_value' => $settings['logo_path'],
      '#description' => t('The path to the file you would like to use as your logo file instead of the default logo.'));

    $form['logo']['logo_upload'] = array(
      '#type' => 'file',
      '#title' => t('Upload logo image'),
      '#maxlength' => 40,
      '#description' => t("If you don't have direct file access to the server, use this field to upload your logo.")
    );
  }

  if ((!$key) || in_array('favicon', $features)) {
    $form['favicon'] = array(
      '#type' => 'fieldset',
      '#title' => t('Shortcut icon settings'),
      '#description' => t("Your shortcut icon, or 'favicon', is displayed in the address bar and bookmarks of most browsers.")
    );
    $form['favicon']['default_favicon'] = array(
      '#type' => 'checkbox',
      '#title' => t('Use the default shortcut icon.'),
      '#default_value' => $settings['default_favicon'],
      '#description' => t('Check here if you want the theme to use the default shortcut icon.')
    );
    $form['favicon']['favicon_path'] = array(
      '#type' => 'textfield',
      '#title' => t('Path to custom icon'),
      '#default_value' => $settings['favicon_path'],
      '#description' => t('The path to the image file you would like to use as your custom shortcut icon.')
    );

    $form['favicon']['favicon_upload'] = array(
      '#type' => 'file',
      '#title' => t('Upload icon image'),
      '#description' => t("If you don't have direct file access to the server, use this field to upload your shortcut icon.")
    );
  }

  if ($key) {
    // Call engine-specific settings.
    $function = $themes[$key]->prefix .'_engine_settings';
    if (function_exists($function)) {
      $group = $function($settings);
      if (!empty($group)) {
        $form['engine_specific'] = array('#type' => 'fieldset', '#title' => t('Theme-engine-specific settings'), '#description' => t('These settings only exist for all the templates and styles based on the %engine theme engine.', array('%engine' => $themes[$key]->prefix)));
        $form['engine_specific'] = array_merge($form['engine_specific'], $group);
      }
    }

    // Create a list which includes the current theme and all its base themes.
    if (isset($themes[$key]->base_themes)) {
      $theme_keys = array_keys($themes[$key]->base_themes);
      $theme_keys[] = $key;
    }
    else {
      $theme_keys = array($key);
    }

    // Process the theme and all its base themes.
    foreach ($theme_keys as $theme) {
      // Include the theme-settings.php file.
      $filename = './'. str_replace("/$theme.info", '', $themes[$theme]->filename) .'/theme-settings.php';
      if (file_exists($filename)) {
        require_once $filename;
      }

      $function = $theme .'_settings';
      if (!function_exists($function)) {
        $function = $themes[$theme]->prefix .'_settings';
      }
      if (function_exists($function)) {
        $group = $function($settings);
        if (!empty($group)) {
          $form['theme_specific']['#type'] = 'fieldset';
          $form['theme_specific']['#title'] = t('Theme-specific settings');
          $form['theme_specific']['#description'] = t('These settings only exist for the %theme theme and all the styles based on it.', array('%theme' => $themes[$theme]->info['name']));
          $form['theme_specific'] = array_merge($form['theme_specific'], $group);
        }
      }
    }
  }
  $form['#attributes'] = array('enctype' => 'multipart/form-data');

  $form = system_settings_form($form);
  // We don't want to call system_settings_form_submit(), so change #submit.
  $form['#submit'] = array('system_theme_settings_submit');
  return $form;
}

/**
 * Process system_theme_settings form submissions.
 */
function system_theme_settings_submit($form, &$form_state) {
  $values = $form_state['values'];
  $key = $values['var'];

  if ($values['op'] == t('Reset to defaults')) {
    variable_del($key);
    drupal_set_message(t('The configuration options have been reset to their default values.'));
  }
  else {
    // Exclude unnecessary elements before saving.
    unset($values['var'], $values['submit'], $values['reset'], $values['form_id'], $values['op'], $values['form_build_id'], $values['form_token']);
    variable_set($key, $values);
    drupal_set_message(t('The configuration options have been saved.'));
  }

  cache_clear_all();
}

/**
 * Recursively check compatibility.
 *
 * @param $incompatible
 *   An associative array which at the end of the check contains all incompatible files as the keys, their values being TRUE.
 * @param $files
 *   The set of files that will be tested.
 * @param $file
 *   The file at which the check starts.
 * @return
 *   Returns TRUE if an incompatible file is found, NULL (no return value) otherwise.
 */
function _system_is_incompatible(&$incompatible, $files, $file) {
  static $seen;
  // We need to protect ourselves in case of a circular dependency.
  if (isset($seen[$file->name])) {
    return isset($incompatible[$file->name]);
  }
  $seen[$file->name] = TRUE;
  if (isset($incompatible[$file->name])) {
    return TRUE;
  }
  // The 'dependencies' key in .info files was a string in Drupal 5, but changed
  // to an array in Drupal 6. If it is not an array, the module is not
  // compatible and we can skip the check below which requires an array.
  if (!is_array($file->info['dependencies'])) {
    $file->info['dependencies'] = array();
    $incompatible[$file->name] = TRUE;
    return TRUE;
  }
  // Recursively traverse the dependencies, looking for incompatible modules
  foreach ($file->info['dependencies'] as $dependency) {
    if (isset($files[$dependency]) && _system_is_incompatible($incompatible, $files, $files[$dependency])) {
      $incompatible[$file->name] = TRUE;
      return TRUE;
    }
  }
}

/**
 * Menu callback; provides module enable/disable interface.
 *
 * Modules can be enabled or disabled and set for throttling if the throttle module is enabled.
 * The list of modules gets populated by module.info files, which contain each module's name,
 * description and dependencies.
 * @see drupal_parse_info_file for information on module.info descriptors.
 *
 * Dependency checking is performed to ensure that a module cannot be enabled if the module has
 * disabled dependencies and also to ensure that the module cannot be disabled if the module has
 * enabled dependents.
 *
 * @param $form_state
 *   An associative array containing the current state of the form.
 * @ingroup forms
 * @see theme_system_modules()
 * @see system_modules_submit()
 * @return
 *   The form array.
 */
function system_modules($form_state = array()) {
  // Get current list of modules.
  $files = module_rebuild_cache();

  // Remove hidden modules from display list.
  $visible_files = $files;
  foreach ($visible_files as $filename => $file) {
    if (!empty($file->info['hidden'])) {
      unset($visible_files[$filename]);
    }
  }

  uasort($visible_files, 'system_sort_modules_by_info_name');

  if (!empty($form_state['storage'])) {
    return system_modules_confirm_form($visible_files, $form_state['storage']);
  }
  $dependencies = array();

  // Store module list for validation callback.
  $form['validation_modules'] = array('#type' => 'value', '#value' => $visible_files);

  // Create storage for disabled modules as browser will disable checkboxes.
  $form['disabled_modules'] = array('#type' => 'value', '#value' => array());

  // Traverse the files, checking for compatibility
  $incompatible_core = array();
  $incompatible_php = array();
  foreach ($visible_files as $filename => $file) {
    // Ensure this module is compatible with this version of core.
    if (!isset($file->info['core']) || $file->info['core'] != DRUPAL_CORE_COMPATIBILITY) {
      $incompatible_core[$file->name] = $file->name;
    }
    // Ensure this module is compatible with the currently installed version of PHP.
    if (version_compare(phpversion(), $file->info['php']) < 0) {
      $incompatible_php[$file->name] = $file->info['php'];
    }
  }

  // Array for disabling checkboxes in callback system_module_disable.
  $disabled = array();
  $throttle = array();
  // Traverse the files retrieved and build the form.
  foreach ($visible_files as $filename => $file) {
    $form['name'][$filename] = array('#value' => $file->info['name']);
    $form['version'][$filename] = array('#value' => $file->info['version']);
    $form['description'][$filename] = array('#value' => t($file->info['description']));
    $options[$filename] = '';
    // Ensure this module is compatible with this version of core and php.
    if (_system_is_incompatible($incompatible_core, $files, $file) || _system_is_incompatible($incompatible_php, $files, $file)) {
      $disabled[] = $file->name;
      // Nothing else in this loop matters, so move to the next module.
      continue;
    }
    if ($file->status) {
      $status[] = $file->name;
    }
    if ($file->throttle) {
      $throttle[] = $file->name;
    }

    $dependencies = array();
    // Check for missing dependencies.
    if (is_array($file->info['dependencies'])) {
      foreach ($file->info['dependencies'] as $dependency) {
        if (!isset($files[$dependency])) {
          $dependencies[] = t('@module (<span class="admin-missing">missing</span>)', array('@module' => drupal_ucfirst($dependency)));
          $disabled[] = $filename;
          $form['disabled_modules']['#value'][$filename] = FALSE;
        }
        // Only display visible modules.
        elseif (isset($visible_files[$dependency])) {
          if ($files[$dependency]->status) {
            $dependencies[] = t('@module (<span class="admin-enabled">enabled</span>)', array('@module' => $files[$dependency]->info['name']));
          }
          else {
            $dependencies[] = t('@module (<span class="admin-disabled">disabled</span>)', array('@module' => $files[$dependency]->info['name']));
          }
        }
      }

      // Add text for dependencies.
      if (!empty($dependencies)) {
        $form['description'][$filename]['dependencies'] = array(
          '#value' => t('Depends on: !dependencies', array('!dependencies' => implode(', ', $dependencies))),
          '#prefix' => '<div class="admin-dependencies">',
          '#suffix' => '</div>',
        );
      }
    }

    // Mark dependents disabled so user can not remove modules being depended on.
    $dependents = array();
    foreach ($file->info['dependents'] as $dependent) {
      // Hidden modules are unset already.
      if (isset($visible_files[$dependent])) {
        if ($files[$dependent]->status == 1) {
          $dependents[] = t('@module (<span class="admin-enabled">enabled</span>)', array('@module' => $files[$dependent]->info['name']));
          $disabled[] = $filename;
          $form['disabled_modules']['#value'][$filename] = TRUE;
        }
        else {
          $dependents[] = t('@module (<span class="admin-disabled">disabled</span>)', array('@module' => $files[$dependent]->info['name']));
        }
      }
    }

    // Add text for enabled dependents.
    if (!empty($dependents)) {
      $form['description'][$filename]['required'] = array(
        '#value' => t('Required by: !required', array('!required' => implode(', ', $dependents))),
        '#prefix' => '<div class="admin-required">',
        '#suffix' => '</div>',
      );
    }
  }

  $modules_required = drupal_required_modules();
  // Merge in required modules.
  foreach ($modules_required as $required) {
    $disabled[] = $required;
    $form['disabled_modules']['#value'][$required] = TRUE;
  }

  // Handle status checkboxes, including overriding
  // the generated checkboxes for required modules.
  $form['status'] = array(
    '#type' => 'checkboxes',
    '#default_value' => $status,
    '#options' => $options,
    '#process' => array(
      'expand_checkboxes',
      'system_modules_disable',
    ),
    '#disabled_modules' => $disabled,
    '#incompatible_modules_core' => $incompatible_core,
    '#incompatible_modules_php' => $incompatible_php,
  );

  // Handle throttle checkboxes, including overriding the
  // generated checkboxes for required modules.
  if (module_exists('throttle')) {
    $form['throttle'] = array(
      '#type' => 'checkboxes',
      '#default_value' => $throttle,
      '#options' => $options,
      '#process' => array(
        'expand_checkboxes',
        'system_modules_disable',
      ),
      '#disabled_modules' => array_merge($modules_required, array('throttle')),
    );
  }

  $form['buttons']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save configuration'),
  );
  $form['#action'] = url('admin/build/modules/list/confirm');

  return $form;
}

/**
 * Array sorting callback; sorts modules or themes by their name.
 */
function system_sort_modules_by_info_name($a, $b) {
  return strcasecmp($a->info['name'], $b->info['name']);
}

/**
 * Form process callback function to disable check boxes.
 *
 * @param $form
 *   The form structure.
 * @param $edit
 *   Not used.
 * @ingroup forms
 * @return
 *   The form structure.
 */
function system_modules_disable($form, $edit) {
  foreach ($form['#disabled_modules'] as $key) {
    $form[$key]['#attributes']['disabled'] = 'disabled';
  }
  return $form;
}

/**
 * Display confirmation form for dependencies.
 *
 * @param $modules
 *   Array of module file objects as returned from module_rebuild_cache().
 * @param $storage
 *   The contents of $form_state['storage']; an array with two
 *   elements: the list of dependencies and the list of status
 *   form field values from the previous screen.
 * @ingroup forms
 */
function system_modules_confirm_form($modules, $storage) {
  $form = array();
  $items = array();

  list($dependencies, $status) = $storage;
  $form['validation_modules'] = array('#type' => 'value', '#value' => $modules);
  $form['status']['#tree'] = TRUE;
  // Remember list of modules selected on the module listing page already.
  foreach ($status as $key => $choice) {
    $form['status'][$key] = array('#type' => 'value', '#value' => $choice);
  }
  foreach ($dependencies as $name => $missing_dependencies) {
    $form['status'][$name] = array('#type' => 'hidden', '#value' => 1);
    foreach ($missing_dependencies as $k => $dependency) {
      $form['status'][$dependency] = array('#type' => 'hidden', '#value' => 1);
      $info = $modules[$dependency]->info;
      $missing_dependencies[$k] = $info['name'] ? $info['name'] : drupal_ucfirst($dependency);
    }
    $t_argument = array(
      '@module' => $modules[$name]->info['name'],
      '@dependencies' => implode(', ', $missing_dependencies),
    );
    $items[] = format_plural(count($missing_dependencies), 'You must enable the @dependencies module to install @module.', 'You must enable the @dependencies modules to install @module.', $t_argument);
  }
  $form['text'] = array('#value' => theme('item_list', $items));

  if ($form) {
    // Set some default form values
    $form = confirm_form(
      $form,
      t('Some required modules must be enabled'),
      'admin/build/modules',
      t('Would you like to continue with enabling the above?'),
      t('Continue'),
      t('Cancel'));
    return $form;
  }
}

/**
 * Submit callback; handles modules form submission.
 */
function system_modules_submit($form, &$form_state) {
  include_once './includes/install.inc';
  $new_modules = array();

  // If we are coming from the confirm form...
  if (!isset($form_state['storage'])) {
    // Merge in disabled active modules since they should be enabled.
    // They don't appear because disabled checkboxes are not submitted
    // by browsers.
    $form_state['values']['status'] = array_merge($form_state['values']['status'], $form_state['values']['disabled_modules']);

    // Check values for dependency that we can't install.
    if ($dependencies = system_module_build_dependencies($form_state['values']['validation_modules'], $form_state['values'])) {
      // These are the modules that depend on existing modules.
      foreach (array_keys($dependencies) as $name) {
        $form_state['values']['status'][$name] = 0;
      }
    }
  }
  else {
    $dependencies = NULL;
  }

  // Update throttle settings, if present
  if (isset($form_state['values']['throttle'])) {
    foreach ($form_state['values']['throttle'] as $key => $choice) {
      db_query("UPDATE {system} SET throttle = %d WHERE type = 'module' and name = '%s'", $choice ? 1 : 0, $key);
    }
  }

  // If there where unmet dependencies and they haven't confirmed don't process
  // the submission yet. Store the form submission data needed later.
  if ($dependencies) {
    if (!isset($form_state['values']['confirm'])) {
      $form_state['storage'] = array($dependencies, $form_state['values']['status']);
      return;
    }
    else {
      $form_state['values']['status'] = array_merge($form_state['values']['status'], $form_storage[1]);
    }
  }
  // If we have no dependencies, or the dependencies are confirmed
  // to be installed, we don't need the temporary storage anymore.
  unset($form_state['storage']);

  $enable_modules = array();
  $disable_modules = array();
  foreach ($form_state['values']['status'] as $key => $choice) {
    if ($choice) {
      if (drupal_get_installed_schema_version($key) == SCHEMA_UNINSTALLED) {
        $new_modules[] = $key;
      }
      else {
        $enable_modules[] = $key;
      }
    }
    else {
      $disable_modules[] = $key;
    }
  }

  $old_module_list = module_list();

  if (!empty($enable_modules)) {
    module_enable($enable_modules);
  }
  if (!empty($disable_modules)) {
    module_disable($disable_modules);
  }

  // Install new modules.
  foreach ($new_modules as $key => $module) {
    if (!drupal_check_module($module)) {
      unset($new_modules[$key]);
    }
  }
  drupal_install_modules($new_modules);

  $current_module_list = module_list(TRUE, FALSE);
  if ($old_module_list != $current_module_list) {
    drupal_set_message(t('The configuration options have been saved.'));
  }

  drupal_rebuild_theme_registry();
  node_types_rebuild();
  menu_rebuild();
  cache_clear_all('schema', 'cache');
  drupal_clear_css_cache();
  drupal_clear_js_cache();

  $form_state['redirect'] = 'admin/build/modules';

  // Notify locale module about module changes, so translations can be
  // imported. This might start a batch, and only return to the redirect
  // path after that.
  module_invoke('locale', 'system_update', $new_modules);

  // Synchronize to catch any actions that were added or removed.
  actions_synchronize();

  return;
}


/**
 * Generate a list of dependencies for modules that are going to be switched on.
 *
 * @param $modules
 *   The list of modules to check.
 * @param $form_values
 *   Submitted form values used to determine what modules have been enabled.
 * @return
 *   An array of dependencies.
 */
function system_module_build_dependencies($modules, $form_values) {
  static $dependencies;

  if (!isset($dependencies) && isset($form_values)) {
    $dependencies = array();
    foreach ($modules as $name => $module) {
      // If the module is disabled, will be switched on and it has dependencies.
      if (!$module->status && $form_values['status'][$name] && isset($module->info['dependencies'])) {
        foreach ($module->info['dependencies'] as $dependency) {
          if (!$form_values['status'][$dependency] && isset($modules[$dependency])) {
            if (!isset($dependencies[$name])) {
              $dependencies[$name] = array();
            }
            $dependencies[$name][] = $dependency;
          }
        }
      }
    }
  }
  return $dependencies;
}

/**
 * Uninstall functions
 */

/**
 * Builds a form of currently disabled modules.
 *
 * @ingroup forms
 * @see system_modules_uninstall_validate()
 * @see system_modules_uninstall_submit()
 * @param $form_state['values']
 *   Submitted form values.
 * @return
 *   A form array representing the currently disabled modules.
 */
function system_modules_uninstall($form_state = NULL) {
  // Make sure the install API is available.
  include_once './includes/install.inc';

  // Display the confirm form if any modules have been submitted.
  if (isset($form_state) && $confirm_form = system_modules_uninstall_confirm_form($form_state['storage'])) {
    return $confirm_form;
  }

  $form = array();

  // Pull all disabled modules from the system table.
  $disabled_modules = db_query("SELECT name, filename, info FROM {system} WHERE type = 'module' AND status = 0 AND schema_version > %d ORDER BY name", SCHEMA_UNINSTALLED);
  while ($module = db_fetch_object($disabled_modules)) {

    // Grab the module info
    $info = unserialize($module->info);

    // Load the .install file, and check for an uninstall hook.
    // If the hook exists, the module can be uninstalled.
    module_load_install($module->name);
    if (module_hook($module->name, 'uninstall')) {
      $form['modules'][$module->name]['name'] = array('#value' => $info['name'] ? $info['name'] : $module->name);
      $form['modules'][$module->name]['description'] = array('#value' => t($info['description']));
      $options[$module->name] = '';
    }
  }

  // Only build the rest of the form if there are any modules available to uninstall.
  if (!empty($options)) {
    $form['uninstall'] = array(
      '#type' => 'checkboxes',
      '#options' => $options,
    );
    $form['buttons']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Uninstall'),
    );
    $form['#action'] = url('admin/build/modules/uninstall/confirm');
  }
  else {
    $form['modules'] = array();
  }

  return $form;
}

/**
 * Confirm uninstall of selected modules.
 *
 * @ingroup forms
 * @param $storage
 *   An associative array of modules selected to be uninstalled.
 * @return
 *   A form array representing modules to confirm.
 */
function system_modules_uninstall_confirm_form($storage) {
  // Nothing to build.
  if (!isset($storage)) {
    return;
  }

  // Construct the hidden form elements and list items.
  foreach (array_filter($storage['uninstall']) as $module => $value) {
    $info = drupal_parse_info_file(dirname(drupal_get_filename('module', $module)) .'/'. $module .'.info');
    $uninstall[] = $info['name'];
    $form['uninstall'][$module] = array('#type' => 'hidden',
      '#value' => 1,
    );
  }

  // Display a confirm form if modules have been selected.
  if (isset($uninstall)) {
    $form['#confirmed'] = TRUE;
    $form['uninstall']['#tree'] = TRUE;
    $form['modules'] = array('#value' => '<p>'. t('The following modules will be completely uninstalled from your site, and <em>all data from these modules will be lost</em>!') .'</p>'. theme('item_list', $uninstall));
    $form = confirm_form(
      $form,
      t('Confirm uninstall'),
      'admin/build/modules/uninstall',
      t('Would you like to continue with uninstalling the above?'),
      t('Uninstall'),
      t('Cancel'));
    return $form;
  }
}

/**
 * Validates the submitted uninstall form.
 */
function system_modules_uninstall_validate($form, &$form_state) {
  // Form submitted, but no modules selected.
  if (!count(array_filter($form_state['values']['uninstall']))) {
    drupal_set_message(t('No modules selected.'), 'error');
    drupal_goto('admin/build/modules/uninstall');
  }
}

/**
 * Processes the submitted uninstall form.
 */
function system_modules_uninstall_submit($form, &$form_state) {
  // Make sure the install API is available.
  include_once './includes/install.inc';

  if (!empty($form['#confirmed'])) {
    // Call the uninstall routine for each selected module.
    foreach (array_filter($form_state['values']['uninstall']) as $module => $value) {
      drupal_uninstall_module($module);
    }
    drupal_set_message(t('The selected modules have been uninstalled.'));

    unset($form_state['storage']);
    $form_state['redirect'] = 'admin/build/modules/uninstall';
  }
  else {
    $form_state['storage'] = $form_state['values'];
  }
}

/**
 * Form builder; The general site information form.
 *
 * @ingroup forms
 * @see system_settings_form()
 */
function system_site_information_settings() {
  $form['site_name'] = array(
    '#type' => 'textfield',
    '#title' => t('Name'),
    '#default_value' => variable_get('site_name', 'Drupal'),
    '#description' => t('The name of this website.'),
    '#required' => TRUE
  );
  $form['site_mail'] = array(
    '#type' => 'textfield',
    '#title' => t('E-mail address'),
    '#default_value' => variable_get('site_mail', ini_get('sendmail_from')),
    '#description' => t("The <em>From</em> address in automated e-mails sent during registration and new password requests, and other notifications. (Use an address ending in your site's domain to help prevent this e-mail being flagged as spam.)"),
    '#required' => TRUE,
  );
  $form['site_slogan'] = array(
    '#type' => 'textfield',
    '#title' => t('Slogan'),
    '#default_value' => variable_get('site_slogan', ''),
    '#description' => t("Your site's motto, tag line, or catchphrase (often displayed alongside the title of the site).")
  );
  $form['site_mission'] = array(
    '#type' => 'textarea',
    '#title' => t('Mission'),
    '#default_value' => variable_get('site_mission', ''),
    '#description' => t("Your site's mission or focus statement (often prominently displayed on the front page).")
  );
  $form['site_footer'] = array(
    '#type' => 'textarea',
    '#title' => t('Footer message'),
    '#default_value' => variable_get('site_footer', ''),
    '#description' => t('This text will be displayed at the bottom of each page. Useful for adding a copyright notice to your pages.')
  );
  $form['anonymous'] = array(
    '#type' => 'textfield',
    '#title' => t('Anonymous user'),
    '#default_value' => variable_get('anonymous', t('Anonymous')),
    '#description' => t('The name used to indicate anonymous users.'),
    '#required' => TRUE,
  );
  $form['site_frontpage'] = array(
    '#type' => 'textfield',
    '#title' => t('Default front page'),
    '#default_value' => variable_get('site_frontpage', 'node'),
    '#size' => 40,
    '#description' => t('The home page displays content from this relative URL. If unsure, specify "node".'),
    '#field_prefix' => url(NULL, array('absolute' => TRUE)) . (variable_get('clean_url', 0) ? '' : '?q='),
    '#required' => TRUE,
  );
  $form['#validate'][] = 'system_site_information_settings_validate';

  return system_settings_form($form);
}

/**
 * Validate the submitted site-information form.
 */
function system_site_information_settings_validate($form, &$form_state) {
  // Validate the e-mail address.
  if ($error = user_validate_mail($form_state['values']['site_mail'])) {
    form_set_error('site_mail', $error);
  }
  // Validate front page path.
  $item = array('link_path' => $form_state['values']['site_frontpage']);
  $normal_path = drupal_get_normal_path($item['link_path']);
  if ($item['link_path'] != $normal_path) {
    drupal_set_message(t('The menu system stores system paths only, but will use the URL alias for display. %link_path has been stored as %normal_path', array('%link_path' => $item['link_path'], '%normal_path' => $normal_path)));
    $item['link_path'] = $normal_path;
  }
  if (!empty($item) && !menu_valid_path($item)) {
    form_set_error('site_frontpage', t("The path '@path' is either invalid or you do not have access to it.", array('@path' => $item['link_path'])));
  }
}

/**
 * Form builder; Configure error reporting settings.
 *
 * @ingroup forms
 * @see system_settings_form()
 */
function system_error_reporting_settings() {

  $form['site_403'] = array(
    '#type' => 'textfield',
    '#title' => t('Default 403 (access denied) page'),
    '#default_value' => variable_get('site_403', ''),
    '#size' => 40,
    '#description' => t('This page is displayed when the requested document is denied to the current user. If unsure, specify nothing.'),
    '#field_prefix' => url(NULL, array('absolute' => TRUE)) . (variable_get('clean_url', 0) ? '' : '?q=')
  );

  $form['site_404'] = array(
    '#type' => 'textfield',
    '#title' => t('Default 404 (not found) page'),
    '#default_value' => variable_get('site_404', ''),
    '#size' => 40,
    '#description' => t('This page is displayed when no other content matches the requested document. If unsure, specify nothing.'),
    '#field_prefix' => url(NULL, array('absolute' => TRUE)) . (variable_get('clean_url', 0) ? '' : '?q=')
  );

  $form['error_level'] = array(
    '#type' => 'select', '#title' => t('Error reporting'), '#default_value' => variable_get('error_level', 1),
    '#options' => array(t('Write errors to the log'), t('Write errors to the log and to the screen')),
    '#description' => t('Specify where Drupal, PHP and SQL errors are logged. While it is recommended that a site running in a production environment write errors to the log only, in a development or testing environment it may be helpful to write errors both to the log and to the screen.')
  );

  return system_settings_form($form);
}

/**
 * Menu callback; Menu page for the various logging options.
 */
function system_logging_overview() {
  $item = menu_get_item('admin/settings/logging');
  $content = system_admin_menu_block($item);

  $output = theme('admin_block_content', $content);

  return $output;
}

/**
 * Form builder; Configure site performance settings.
 *
 * @ingroup forms
 * @see system_settings_form()
 */
function system_performance_settings() {

  $description = '<p>'. t("The normal cache mode is suitable for most sites and does not cause any side effects. The aggressive cache mode causes Drupal to skip the loading (boot) and unloading (exit) of enabled modules when serving a cached page. This results in an additional performance boost but can cause unwanted side effects.") .'</p>';

  $problem_modules = array_unique(array_merge(module_implements('boot'), module_implements('exit')));
  sort($problem_modules);

  if (count($problem_modules) > 0) {
    $description .= '<p>'. t('<strong class="error">The following enabled modules are incompatible with aggressive mode caching and will not function properly: %modules</strong>', array('%modules' => implode(', ', $problem_modules))) .'.</p>';
  }
  else {
    $description .= '<p>'. t('<strong class="ok">Currently, all enabled modules are compatible with the aggressive caching policy.</strong> Please note, if you use aggressive caching and enable new modules, you will need to check this page again to ensure compatibility.') .'</p>';
  }
  $form['page_cache'] = array(
    '#type' => 'fieldset',
    '#title' => t('Page cache'),
    '#description' => t('Enabling the page cache will offer a significant performance boost. Drupal can store and send compressed cached pages requested by <em>anonymous</em> users. By caching a web page, Drupal does not have to construct the page each time it is viewed.'),
  );

  $form['page_cache']['cache'] = array(
    '#type' => 'radios',
    '#title' => t('Caching mode'),
    '#default_value' => variable_get('cache', CACHE_DISABLED),
    '#options' => array(CACHE_DISABLED => t('Disabled'), CACHE_NORMAL => t('Normal (recommended for production sites, no side effects)'), CACHE_AGGRESSIVE => t('Aggressive (experts only, possible side effects)')),
    '#description' => $description
  );

  $period = drupal_map_assoc(array(0, 60, 180, 300, 600, 900, 1800, 2700, 3600, 10800, 21600, 32400, 43200, 86400), 'format_interval');
  $period[0] = '<'. t('none') .'>';
  $form['page_cache']['cache_lifetime'] = array(
    '#type' => 'select',
    '#title' => t('Minimum cache lifetime'),
    '#default_value' => variable_get('cache_lifetime', 0),
    '#options' => $period,
    '#description' => t('On high-traffic sites, it may be necessary to enforce a minimum cache lifetime. The minimum cache lifetime is the minimum amount of time that will elapse before the cache is emptied and recreated, and is applied to both page and block caches. A larger minimum cache lifetime offers better performance, but users will not see new content for a longer period of time.')
  );
  $form['page_cache']['page_compression'] = array(
    '#type' => 'radios',
    '#title' => t('Page compression'),
    '#default_value' => variable_get('page_compression', TRUE),
    '#options' => array(t('Disabled'), t('Enabled')),
    '#description' => t("By default, Drupal compresses the pages it caches in order to save bandwidth and improve download times. This option should be disabled when using a webserver that performs compression."),
  );

  $form['block_cache'] = array(
    '#type' => 'fieldset',
    '#title' => t('Block cache'),
    '#description' => t('Enabling the block cache can offer a performance increase for all users by preventing blocks from being reconstructed on each page load. If the page cache is also enabled, performance increases from enabling the block cache will mainly benefit authenticated users.'),
  );

  $form['block_cache']['block_cache'] = array(
    '#type' => 'radios',
    '#title' => t('Block cache'),
    '#default_value' => variable_get('block_cache', CACHE_DISABLED),
    '#options' => array(CACHE_DISABLED => t('Disabled'), CACHE_NORMAL => t('Enabled (recommended)')),
    '#disabled' => count(module_implements('node_grants')),
    '#description' => t('Note that block caching is inactive when modules defining content access restrictions are enabled.'),
  );

  $form['bandwidth_optimizations'] = array(
    '#type' => 'fieldset',
    '#title' => t('Bandwidth optimizations'),
    '#description' => t('<p>Drupal can automatically optimize external resources like CSS and JavaScript, which can reduce both the size and number of requests made to your website. CSS files can be aggregated and compressed into a single file, while JavaScript files are aggregated (but not compressed). These optional optimizations may reduce server load, bandwidth requirements, and page loading times.</p><p>These options are disabled if you have not set up your files directory, or if your download method is set to private.</p>')
  );

  $directory = file_directory_path();
  $is_writable = is_dir($directory) && is_writable($directory) && (variable_get('file_downloads', FILE_DOWNLOADS_PUBLIC) == FILE_DOWNLOADS_PUBLIC);
  $form['bandwidth_optimizations']['preprocess_css'] = array(
    '#type' => 'radios',
    '#title' => t('Optimize CSS files'),
    '#default_value' => intval(variable_get('preprocess_css', 0) && $is_writable),
    '#disabled' => !$is_writable,
    '#options' => array(t('Disabled'), t('Enabled')),
    '#description' => t('This option can interfere with theme development and should only be enabled in a production environment.'),
  );
  $form['bandwidth_optimizations']['preprocess_js'] = array(
    '#type' => 'radios',
    '#title' => t('Optimize JavaScript files'),
    '#default_value' => intval(variable_get('preprocess_js', 0) && $is_writable),
    '#disabled' => !$is_writable,
    '#options' => array(t('Disabled'), t('Enabled')),
    '#description' => t('This option can interfere with module development and should only be enabled in a production environment.'),
  );

  $form['clear_cache'] = array(
    '#type' => 'fieldset',
    '#title' => t('Clear cached data'),
    '#description' => t('Caching data improves performance, but may cause problems while troubleshooting new modules, themes, or translations, if outdated information has been cached. To refresh all cached data on your site, click the button below. <em>Warning: high-traffic sites will experience performance slowdowns while cached data is rebuilt.</em>'),
  );

  $form['clear_cache']['clear'] = array(
    '#type' => 'submit',
    '#value' => t('Clear cached data'),
    '#submit' => array('system_clear_cache_submit'),
  );

  $form['#submit'][] = 'drupal_clear_css_cache';
  $form['#submit'][] = 'drupal_clear_js_cache';

  return system_settings_form($form);
}

/**
 * Submit callback; clear system caches.
 *
 * @ingroup forms
 */
function system_clear_cache_submit($form, &$form_state) {
  drupal_flush_all_caches();
  drupal_set_message(t('Caches cleared.'));
}

/**
 * Form builder; Configure the site file handling.
 *
 * @ingroup forms
 * @see system_settings_form()
 */
function system_file_system_settings() {

  $form['file_directory_path'] = array(
    '#type' => 'textfield',
    '#title' => t('File system path'),
    '#default_value' => file_directory_path(),
    '#maxlength' => 255,
    '#description' => t('A file system path where the files will be stored. This directory must exist and be writable by Drupal. If the download method is set to public, this directory must be relative to the Drupal installation directory and be accessible over the web. If the download method is set to private, this directory should not be accessible over the web. Changing this location will modify all download paths and may cause unexpected problems on an existing site.'),
    '#after_build' => array('system_check_directory'),
  );

  $form['file_directory_temp'] = array(
    '#type' => 'textfield',
    '#title' => t('Temporary directory'),
    '#default_value' => file_directory_temp(),
    '#maxlength' => 255,
    '#description' => t('A file system path where uploaded files will be stored during previews.'),
    '#after_build' => array('system_check_directory'),
  );

  $form['file_downloads'] = array(
    '#type' => 'radios',
    '#title' => t('Download method'),
    '#default_value' => variable_get('file_downloads', FILE_DOWNLOADS_PUBLIC),
    '#options' => array(FILE_DOWNLOADS_PUBLIC => t('Public - files are available using HTTP directly.'), FILE_DOWNLOADS_PRIVATE => t('Private - files are transferred by Drupal.')),
    '#description' => t('Choose the <em>Public download</em> method unless you wish to enforce fine-grained access controls over file downloads. Changing the download method will modify all download paths and may cause unexpected problems on an existing site.')
  );

  return system_settings_form($form);
}

/**
 * Form builder; Configure site image toolkit usage.
 *
 * @ingroup forms
 * @see system_settings_form()
 */
function system_image_toolkit_settings() {
  $toolkits_available = image_get_available_toolkits();
  if (count($toolkits_available) > 1) {
    $form['image_toolkit'] = array(
      '#type' => 'radios',
      '#title' => t('Select an image processing toolkit'),
      '#default_value' => variable_get('image_toolkit', image_get_toolkit()),
      '#options' => $toolkits_available
    );
  }
  elseif (count($toolkits_available) == 1) {
    variable_set('image_toolkit', key($toolkits_available));
  }

  $form['image_toolkit_settings'] = image_toolkit_invoke('settings');

  return system_settings_form($form);
}

/**
 * Form builder; Configure how the site handles RSS feeds.
 *
 * @ingroup forms
 * @see system_settings_form()
 */
function system_rss_feeds_settings() {

  $form['feed_default_items'] = array(
    '#type' => 'select',
    '#title' => t('Number of items in each feed'),
    '#default_value' => variable_get('feed_default_items', 10),
    '#options' => drupal_map_assoc(array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 15, 20, 25, 30)),
    '#description' => t('Default number of items to include in each feed.')
  );
  $form['feed_item_length'] = array(
    '#type' => 'select',
    '#title' => t('Feed content'),
    '#default_value' => variable_get('feed_item_length', 'teaser'),
    '#options' => array('title' => t('Titles only'), 'teaser' => t('Titles plus teaser'), 'fulltext' => t('Full text')),
    '#description' => t('Global setting for the default display of content items in each feed.')
  );

  return system_settings_form($form);
}

/**
 * Form builder; Configure the site date and time settings.
 *
 * @ingroup forms
 * @see system_settings_form()
 * @see system_date_time_settings_submit()
 */
function system_date_time_settings() {
  drupal_add_js(drupal_get_path('module', 'system') .'/system.js', 'module');
  drupal_add_js(array('dateTime' => array('lookup' => url('admin/settings/date-time/lookup'))), 'setting');

  // Date settings:
  $zones = _system_zonelist();

  // Date settings: possible date formats
  $date_short = array('Y-m-d H:i', 'm/d/Y - H:i', 'd/m/Y - H:i', 'Y/m/d - H:i',
           'd.m.Y - H:i', 'm/d/Y - g:ia', 'd/m/Y - g:ia', 'Y/m/d - g:ia',
           'M j Y - H:i', 'j M Y - H:i', 'Y M j - H:i',
           'M j Y - g:ia', 'j M Y - g:ia', 'Y M j - g:ia');
  $date_medium = array('D, Y-m-d H:i', 'D, m/d/Y - H:i', 'D, d/m/Y - H:i',
          'D, Y/m/d - H:i', 'F j, Y - H:i', 'j F, Y - H:i', 'Y, F j - H:i',
          'D, m/d/Y - g:ia', 'D, d/m/Y - g:ia', 'D, Y/m/d - g:ia',
          'F j, Y - g:ia', 'j F Y - g:ia', 'Y, F j - g:ia', 'j. F Y - G:i');
  $date_long = array('l, F j, Y - H:i', 'l, j F, Y - H:i', 'l, Y, F j - H:i',
        'l, F j, Y - g:ia', 'l, j F Y - g:ia', 'l, Y, F j - g:ia', 'l, j. F Y - G:i');

  // Date settings: construct choices for user
  foreach ($date_short as $f) {
    $date_short_choices[$f] = format_date(time(), 'custom', $f);
  }
  foreach ($date_medium as $f) {
    $date_medium_choices[$f] = format_date(time(), 'custom', $f);
  }
  foreach ($date_long as $f) {
    $date_long_choices[$f] = format_date(time(), 'custom', $f);
  }

  $date_long_choices['custom'] = $date_medium_choices['custom'] = $date_short_choices['custom'] = t('Custom format');

  $form['locale'] = array(
    '#type' => 'fieldset',
    '#title' => t('Locale settings'),
  );

  $form['locale']['date_default_timezone'] = array(
    '#type' => 'select',
    '#title' => t('Default time zone'),
    '#default_value' => variable_get('date_default_timezone', 0),
    '#options' => $zones,
    '#description' => t('Select the default site time zone.')
  );

  $form['locale']['configurable_timezones'] = array(
    '#type' => 'radios',
    '#title' => t('User-configurable time zones'),
    '#default_value' => variable_get('configurable_timezones', 1),
    '#options' => array(t('Disabled'), t('Enabled')),
    '#description' => t('When enabled, users can set their own time zone and dates will be displayed accordingly.')
  );

  $form['locale']['date_first_day'] = array(
    '#type' => 'select',
    '#title' => t('First day of week'),
    '#default_value' => variable_get('date_first_day', 0),
    '#options' => array(0 => t('Sunday'), 1 => t('Monday'), 2 => t('Tuesday'), 3 => t('Wednesday'), 4 => t('Thursday'), 5 => t('Friday'), 6 => t('Saturday')),
    '#description' => t('The first day of the week for calendar views.')
  );

  $form['date_formats'] = array(
    '#type' => 'fieldset',
    '#title' => t('Formatting'),
  );

  $date_format_short = variable_get('date_format_short', $date_short[1]);
  $form['date_formats']['date_format_short'] = array(
    '#prefix' => '<div class="date-container"><div class="select-container">',
    '#suffix' => '</div>',
    '#type' => 'select',
    '#title' => t('Short date format'),
    '#attributes' => array('class' => 'date-format'),
    '#default_value' => (isset($date_short_choices[$date_format_short]) ? $date_format_short : 'custom'),
    '#options' => $date_short_choices,
    '#description' => t('The short format of date display.'),
  );

  $default_short_custom = variable_get('date_format_short_custom', (isset($date_short_choices[$date_format_short]) ? $date_format_short : ''));
  $form['date_formats']['date_format_short_custom'] = array(
    '#prefix' => '<div class="custom-container">',
    '#suffix' => '</div></div>',
    '#type' => 'textfield',
    '#title' => t('Custom short date format'),
    '#attributes' => array('class' => 'custom-format'),
    '#default_value' => $default_short_custom,
    '#description' => t('A user-defined short date format. See the <a href="@url">PHP manual</a> for available options. This format is currently set to display as <span>%date</span>.', array('@url' => 'http://php.net/manual/function.date.php', '%date' => format_date(time(), 'custom', $default_short_custom))),
  );

  $date_format_medium = variable_get('date_format_medium', $date_medium[1]);
  $form['date_formats']['date_format_medium'] = array(
    '#prefix' => '<div class="date-container"><div class="select-container">',
    '#suffix' => '</div>',
    '#type' => 'select',
    '#title' => t('Medium date format'),
    '#attributes' => array('class' => 'date-format'),
    '#default_value' => (isset($date_medium_choices[$date_format_medium]) ? $date_format_medium : 'custom'),
    '#options' => $date_medium_choices,
    '#description' => t('The medium sized date display.'),
  );

  $default_medium_custom = variable_get('date_format_medium_custom', (isset($date_medium_choices[$date_format_medium]) ? $date_format_medium : ''));
  $form['date_formats']['date_format_medium_custom'] = array(
    '#prefix' => '<div class="custom-container">',
    '#suffix' => '</div></div>',
    '#type' => 'textfield',
    '#title' => t('Custom medium date format'),
    '#attributes' => array('class' => 'custom-format'),
    '#default_value' => $default_medium_custom,
    '#description' => t('A user-defined medium date format. See the <a href="@url">PHP manual</a> for available options. This format is currently set to display as <span>%date</span>.', array('@url' => 'http://php.net/manual/function.date.php', '%date' => format_date(time(), 'custom', $default_medium_custom))),
  );

  $date_format_long = variable_get('date_format_long', $date_long[0]);
  $form['date_formats']['date_format_long'] = array(
    '#prefix' => '<div class="date-container"><div class="select-container">',
    '#suffix' => '</div>',
    '#type' => 'select',
    '#title' => t('Long date format'),
    '#attributes' => array('class' => 'date-format'),
    '#default_value' => (isset($date_long_choices[$date_format_long]) ? $date_format_long : 'custom'),
    '#options' => $date_long_choices,
    '#description' => t('Longer date format used for detailed display.')
  );

  $default_long_custom = variable_get('date_format_long_custom', (isset($date_long_choices[$date_format_long]) ? $date_format_long : ''));
  $form['date_formats']['date_format_long_custom'] = array(
    '#prefix' => '<div class="custom-container">',
    '#suffix' => '</div></div>',
    '#type' => 'textfield',
    '#title' => t('Custom long date format'),
    '#attributes' => array('class' => 'custom-format'),
    '#default_value' => $default_long_custom,
    '#description' => t('A user-defined long date format. See the <a href="@url">PHP manual</a> for available options. This format is currently set to display as <span>%date</span>.', array('@url' => 'http://php.net/manual/function.date.php', '%date' => format_date(time(), 'custom', $default_long_custom))),
  );

  $form = system_settings_form($form);
  // We will call system_settings_form_submit() manually, so remove it for now.
  unset($form['#submit']);
  return $form;
}

/**
 * Process system_date_time_settings form submissions.
 */
function system_date_time_settings_submit($form, &$form_state) {
  if ($form_state['values']['date_format_short'] == 'custom') {
    $form_state['values']['date_format_short'] = $form_state['values']['date_format_short_custom'];
  }
  if ($form_state['values']['date_format_medium'] == 'custom') {
    $form_state['values']['date_format_medium'] = $form_state['values']['date_format_medium_custom'];
  }
  if ($form_state['values']['date_format_long'] == 'custom') {
    $form_state['values']['date_format_long'] = $form_state['values']['date_format_long_custom'];
  }
  return system_settings_form_submit($form, $form_state);
}

/**
 * Return the date for a given format string via Ajax.
 */
function system_date_time_lookup() {
  $result = format_date(time(), 'custom', $_GET['format']);
  drupal_json($result);
}

/**
 * Form builder; Configure the site's maintenance status.
 *
 * @ingroup forms
 * @see system_settings_form()
 */
function system_site_maintenance_settings() {

  $form['site_offline'] = array(
    '#type' => 'radios',
    '#title' => t('Site status'),
    '#default_value' => variable_get('site_offline', 0),
    '#options' => array(t('Online'), t('Off-line')),
    '#description' => t('When set to "Online", all visitors will be able to browse your site normally. When set to "Off-line", only users with the "administer site configuration" permission will be able to access your site to perform maintenance; all other visitors will see the site off-line message configured below. Authorized users can log in during "Off-line" mode directly via the <a href="@user-login">user login</a> page.', array('@user-login' => url('user'))),
  );

  $form['site_offline_message'] = array(
    '#type' => 'textarea',
    '#title' => t('Site off-line message'),
    '#default_value' => variable_get('site_offline_message', t('@site is currently under maintenance. We should be back shortly. Thank you for your patience.', array('@site' => variable_get('site_name', 'Drupal')))),
    '#description' => t('Message to show visitors when the site is in off-line mode.')
  );

  return system_settings_form($form);
}

/**
 * Form builder; Configure Clean URL settings.
 *
 * @ingroup forms
 * @see system_settings_form()
 */
function system_clean_url_settings() {
  $form['clean_url'] = array(
    '#type' => 'radios',
    '#title' => t('Clean URLs'),
    '#default_value' => variable_get('clean_url', 0),
    '#options' => array(t('Disabled'), t('Enabled')),
    '#description' => t('This option makes Drupal emit "clean" URLs (i.e. without <code>?q=</code> in the URL).'),
  );

  if (!variable_get('clean_url', 0)) {
    if (strpos(request_uri(), '?q=') !== FALSE) {
      drupal_add_js(drupal_get_path('module', 'system') .'/system.js', 'module');

      $form['clean_url']['#description'] .= ' <span>'. t('Before enabling clean URLs, you must perform a test to determine if your server is properly configured. If you are able to see this page again after clicking the "Run the clean URL test" link, the test has succeeded and the radio buttons above will be available. If instead you are directed to a "Page not found" error, you will need to change the configuration of your server. The <a href="@handbook">handbook page on Clean URLs</a> has additional troubleshooting information.', array('@handbook' => 'http://drupal.org/node/15365')) .'</span>';

      $form['clean_url']['#disabled'] = TRUE;
      $form['clean_url']['#prefix'] = '<div id="clean-url">';
      $form['clean_url']['#suffix'] = '<p>'. t('<a href="@clean_url">Run the clean url test</a>.', array('@clean_url' => base_path() .'admin/settings/clean-urls')) .'</p></div>';
    }
    else {
      $form['clean_url']['#description'] .= ' <div class="ok">'. t('Your server has been successfully tested to support this feature.') .'</div>';
    }
  }

  return system_settings_form($form);
}

/**
 * Menu callback: displays the site status report. Can also be used as a pure check.
 *
 * @param $check
 *   If true, only returns a boolean whether there are system status errors.
 */
function system_status($check = FALSE) {
  // Load .install files
  include_once './includes/install.inc';
  drupal_load_updates();

  // Check run-time requirements and status information.
  $requirements = module_invoke_all('requirements', 'runtime');
  usort($requirements, '_system_sort_requirements');

  if ($check) {
    return drupal_requirements_severity($requirements) == REQUIREMENT_ERROR;
  }
  // MySQL import might have set the uid of the anonymous user to autoincrement
  // value. Let's try fixing it. See http://drupal.org/node/204411
  db_query("UPDATE {users} SET uid = uid - uid WHERE name = '' AND pass = '' AND status = 0");

  return theme('status_report', $requirements);
}

/**
 * Menu callback: run cron manually.
 */
function system_run_cron() {
  // Run cron manually
  if (drupal_cron_run()) {
    drupal_set_message(t('Cron ran successfully.'));
  }
  else {
    drupal_set_message(t('Cron run failed.'), 'error');
  }

  drupal_goto('admin/reports/status');
}

/**
 * Menu callback: return information about PHP.
 */
function system_php() {
  phpinfo();
  exit();
}

/**
 * Theme a SQL result table.
 *
 * @param $data
 *   The actual table data.
 * @param $keys
 *   Data keys and descriptions.
 * @return
 *   The output HTML.
 */
function _system_sql($data, $keys) {
  $rows = array();
  foreach ($keys as $key => $explanation) {
    if (isset($data[$key])) {
      $rows[] = array(check_plain($key), check_plain($data[$key]), $explanation);
    }
  }

  return theme('table', array(t('Variable'), t('Value'), t('Description')), $rows);
}

/**
 * Menu callback: return information about the database.
 */
function system_sql() {

  $result = db_query("SHOW STATUS");
  while ($entry = db_fetch_object($result)) {
    // 'SHOW STATUS' returns fields named 'Variable_name' and 'Value',
    // case is important.
    $data[$entry->Variable_name] = $entry->Value;
  }

  $output  = '<h2>'. t('Command counters') .'</h2>';
  $output .= _system_sql($data, array(
   'Com_select' => t('The number of <code>SELECT</code>-statements.'),
   'Com_insert' => t('The number of <code>INSERT</code>-statements.'),
   'Com_update' => t('The number of <code>UPDATE</code>-statements.'),
   'Com_delete' => t('The number of <code>DELETE</code>-statements.'),
   'Com_lock_tables' => t('The number of table locks.'),
   'Com_unlock_tables' => t('The number of table unlocks.')
  ));

  $output .= '<h2>'. t('Query performance') .'</h2>';
  $output .= _system_sql($data, array(
   'Select_full_join' => t('The number of joins without an index; should be zero.'),
   'Select_range_check' => t('The number of joins without keys that check for key usage after each row; should be zero.'),
   'Sort_scan' => t('The number of sorts done without using an index; should be zero.'),
   'Table_locks_immediate' => t('The number of times a lock could be acquired immediately.'),
   'Table_locks_waited' => t('The number of times the server had to wait for a lock.')
  ));

  $output .= '<h2>'. t('Query cache information') .'</h2>';
  $output .= '<p>'. t('The MySQL query cache can improve performance of your site by storing the result of queries. Then, if an identical query is received later, the MySQL server retrieves the result from the query cache rather than parsing and executing the statement again.') .'</p>';
  $output .= _system_sql($data, array(
   'Qcache_queries_in_cache' => t('The number of queries in the query cache.'),
   'Qcache_hits' => t('The number of times MySQL found previous results in the cache.'),
   'Qcache_inserts' => t('The number of times MySQL added a query to the cache (misses).'),
   'Qcache_lowmem_prunes' => t('The number of times MySQL had to remove queries from the cache because it ran out of memory. Ideally should be zero.')
  ));

  return $output;
}

/**
 * Default page callback for batches.
 */
function system_batch_page() {
  require_once './includes/batch.inc';
  $output = _batch_page();
  if ($output === FALSE) {
    drupal_access_denied();
  }
  elseif (isset($output)) {
    // Force a page without blocks or messages to
    // display a list of collected messages later.
    print theme('page', $output, FALSE, FALSE);
  }
}

/**
 * This function formats an administrative block for display.
 *
 * @param $block
 *   An array containing information about the block. It should
 *   include a 'title', a 'description' and a formatted 'content'.
 * @ingroup themeable
 */
function theme_admin_block($block) {
  // Don't display the block if it has no content to display.
  if (empty($block['content'])) {
    return '';
  }

  $output = <<< EOT
  <div class="admin-panel">
    <h3>
      $block[title]
    </h3>
    <div class="body">
      <p class="description">
        $block[description]
      </p>
      $block[content]
    </div>
  </div>
EOT;
  return $output;
}

/**
 * This function formats the content of an administrative block.
 *
 * @param $content
 *   An array containing information about the block. It should
 *   include a 'title', a 'description' and a formatted 'content'.
 * @ingroup themeable
 */
function theme_admin_block_content($content) {
  if (!$content) {
    return '';
  }

  if (system_admin_compact_mode()) {
    $output = '<ul class="menu">';
    foreach ($content as $item) {
      $output .= '<li class="leaf">'. l($item['title'], $item['href'], $item['localized_options']) .'</li>';
    }
    $output .= '</ul>';
  }
  else {
    $output = '<dl class="admin-list">';
    foreach ($content as $item) {
      $output .= '<dt>'. l($item['title'], $item['href'], $item['localized_options']) .'</dt>';
      $output .= '<dd>'. $item['description'] .'</dd>';
    }
    $output .= '</dl>';
  }
  return $output;
}

/**
 * This function formats an administrative page for viewing.
 *
 * @param $blocks
 *   An array of blocks to display. Each array should include a
 *   'title', a 'description', a formatted 'content' and a
 *   'position' which will control which container it will be
 *   in. This is usually 'left' or 'right'.
 * @ingroup themeable
 */
function theme_admin_page($blocks) {
  $stripe = 0;
  $container = array();

  foreach ($blocks as $block) {
    if ($block_output = theme('admin_block', $block)) {
      if (empty($block['position'])) {
        // perform automatic striping.
        $block['position'] = ++$stripe % 2 ? 'left' : 'right';
      }
      if (!isset($container[$block['position']])) {
        $container[$block['position']] = '';
      }
      $container[$block['position']] .= $block_output;
    }
  }

  $output = '<div class="admin clear-block">';
  $output .= '<div class="compact-link">';
  if (system_admin_compact_mode()) {
    $output .= l(t('Show descriptions'), 'admin/compact/off', array('attributes' => array('title' => t('Expand layout to include descriptions.'))));
  }
  else {
    $output .= l(t('Hide descriptions'), 'admin/compact/on', array('attributes' => array('title' => t('Compress layout by hiding descriptions.'))));
  }
  $output .= '</div>';

  foreach ($container as $id => $data) {
    $output .= '<div class="'. $id .' clear-block">';
    $output .= $data;
    $output .= '</div>';
  }
  $output .= '</div>';
  return $output;
}

/**
 * Theme output of the dashboard page.
 *
 * @param $menu_items
 *   An array of modules to be displayed.
 * @ingroup themeable
 */
function theme_system_admin_by_module($menu_items) {
  $stripe = 0;
  $output = '';
  $container = array('left' => '', 'right' => '');
  $flip = array('left' => 'right', 'right' => 'left');
  $position = 'left';

  // Iterate over all modules
  foreach ($menu_items as $module => $block) {
    list($description, $items) = $block;

    // Output links
    if (count($items)) {
      $block = array();
      $block['title'] = $module;
      $block['content'] = theme('item_list', $items);
      $block['description'] = t($description);

      if ($block_output = theme('admin_block', $block)) {
        if (!isset($block['position'])) {
          // Perform automatic striping.
          $block['position'] = $position;
          $position = $flip[$position];
        }
        $container[$block['position']] .= $block_output;
      }
    }
  }

  $output = '<div class="admin clear-block">';
  foreach ($container as $id => $data) {
    $output .= '<div class="'. $id .' clear-block">';
    $output .= $data;
    $output .= '</div>';
  }
  $output .= '</div>';

  return $output;
}

/**
 * Theme requirements status report.
 *
 * @param $requirements
 *   An array of requirements.
 * @ingroup themeable
 */
function theme_status_report($requirements) {
  $i = 0;
  $output = '<table class="system-status-report">';
  foreach ($requirements as $requirement) {
    if (empty($requirement['#type'])) {
      $class = ++$i % 2 == 0 ? 'even' : 'odd';

      $classes = array(
        REQUIREMENT_INFO => 'info',
        REQUIREMENT_OK => 'ok',
        REQUIREMENT_WARNING => 'warning',
        REQUIREMENT_ERROR => 'error',
      );
      $class = $classes[isset($requirement['severity']) ? (int)$requirement['severity'] : 0] .' '. $class;

      // Output table row(s)
      if (!empty($requirement['description'])) {
        $output .= '<tr class="'. $class .' merge-down"><th>'. $requirement['title'] .'</th><td>'. $requirement['value'] .'</td></tr>';
        $output .= '<tr class="'. $class .' merge-up"><td colspan="2">'. $requirement['description'] .'</td></tr>';
      }
      else {
        $output .= '<tr class="'. $class .'"><th>'. $requirement['title'] .'</th><td>'. $requirement['value'] .'</td></tr>';
      }
    }
  }

  $output .= '</table>';
  return $output;
}

/**
 * Theme callback for the modules form.
 *
 * @param $form
 *   An associative array containing the structure of the form.
 * @ingroup themeable
 */
function theme_system_modules($form) {
  if (isset($form['confirm'])) {
    return drupal_render($form);
  }

  // Individual table headers.
  $header = array();
  $header[] = array('data' => t('Enabled'), 'class' => 'checkbox');
  if (module_exists('throttle')) {
    $header[] = array('data' => t('Throttle'), 'class' => 'checkbox');
  }
  $header[] = t('Name');
  $header[] = t('Version');
  $header[] = t('Description');

  // Pull package information from module list and start grouping modules.
  $modules = $form['validation_modules']['#value'];
  foreach ($modules as $module) {
    if (!isset($module->info['package']) || !$module->info['package']) {
      $module->info['package'] = t('Other');
    }
    $packages[$module->info['package']][$module->name] = $module->info;
  }
  ksort($packages);

  // Display packages.
  $output = '';
  foreach ($packages as $package => $modules) {
    $rows = array();
    foreach ($modules as $key => $module) {
      $row = array();
      $description = drupal_render($form['description'][$key]);
      if (isset($form['status']['#incompatible_modules_core'][$key])) {
        unset($form['status'][$key]);
        $status = theme('image', 'misc/watchdog-error.png', t('incompatible'), t('Incompatible with this version of Drupal core'));
        $description .= '<div class="incompatible">'. t('This version is incompatible with the !core_version version of Drupal core.', array('!core_version' => VERSION)) .'</div>';
      }
      elseif (isset($form['status']['#incompatible_modules_php'][$key])) {
        unset($form['status'][$key]);
        $status = theme('image', 'misc/watchdog-error.png', t('incompatible'), t('Incompatible with this version of PHP'));
        $php_required = $form['status']['#incompatible_modules_php'][$key];
        if (substr_count($php_required, '.') < 2) {
          $php_required .= '.*';
        }
        $description .= '<div class="incompatible">'. t('This module requires PHP version @php_required and is incompatible with PHP version !php_version.', array('@php_required' => $php_required, '!php_version' => phpversion())) .'</div>';
      }
      else {
        $status = drupal_render($form['status'][$key]);
      }
      $row[] = array('data' => $status, 'class' => 'checkbox');
      if (module_exists('throttle')) {
        $row[] = array('data' => drupal_render($form['throttle'][$key]), 'class' => 'checkbox');
      }

      // Add labels only when there is also a checkbox.
      if (isset($form['status'][$key])) {
        $row[] = '<strong><label for="'. $form['status'][$key]['#id'] .'">'. drupal_render($form['name'][$key]) .'</label></strong>';
      }
      else {
        $row[] = '<strong>'. drupal_render($form['name'][$key]) .'</strong>';
      }

      $row[] = array('data' => drupal_render($form['version'][$key]), 'class' => 'version');
      $row[] = array('data' => $description, 'class' => 'description');
      $rows[] = $row;
    }
    $fieldset = array(
      '#title' => t($package),
      '#collapsible' => TRUE,
      '#collapsed' => ($package == 'Core - required'),
      '#value' => theme('table', $header, $rows, array('class' => 'package')),
    );
    $output .= theme('fieldset', $fieldset);
  }

  $output .= drupal_render($form);
  return $output;
}

/**
 * Themes a table of currently disabled modules.
 *
 * @ingroup themeable
 * @param $form
 *   The form array representing the currently disabled modules.
 * @return
 *   An HTML string representing the table.
 */
function theme_system_modules_uninstall($form) {
  // No theming for the confirm form.
  if (isset($form['confirm'])) {
    return drupal_render($form);
  }

  // Table headers.
  $header = array(t('Uninstall'),
    t('Name'),
    t('Description'),
  );

  // Display table.
  $rows = array();
  foreach (element_children($form['modules']) as $module) {
    $rows[] = array(
      array('data' => drupal_render($form['uninstall'][$module]), 'align' => 'center'),
      '<strong>'. drupal_render($form['modules'][$module]['name']) .'</strong>',
      array('data' => drupal_render($form['modules'][$module]['description']), 'class' => 'description'),
    );
  }

  // Only display table if there are modules that can be uninstalled.
  if (empty($rows)) {
    $rows[] = array(array('data' => t('No modules are available to uninstall.'), 'colspan' => '3', 'align' => 'center', 'class' => 'message'));
  }

  $output  = theme('table', $header, $rows);
  $output .= drupal_render($form);

  return $output;
}

/**
 * Theme the theme select form.
 * @param $form
 *   An associative array containing the structure of the form.
 * @ingroup themeable
 */
function theme_system_theme_select_form($form) {
  foreach (element_children($form) as $key) {
    $row = array();
    if (isset($form[$key]['description']) && is_array($form[$key]['description'])) {
      $row[] = drupal_render($form[$key]['screenshot']);
      $row[] = drupal_render($form[$key]['description']);
      $row[] = drupal_render($form['theme'][$key]);
    }
    $rows[] = $row;
  }

  $header = array(t('Screenshot'), t('Name'), t('Selected'));
  $output = theme('table', $header, $rows);
  return $output;
}

/**
 * Theme function for the system themes form.
 *
 * @param $form
 *   An associative array containing the structure of the form.
 * @ingroup themeable
 */
function theme_system_themes_form($form) {
  foreach (element_children($form) as $key) {
    // Only look for themes
    if (!isset($form[$key]['info'])) {
      continue;
    }

    // Fetch info
    $info = $form[$key]['info']['#value'];
    // Localize theme description.
    $description = t($info['description']);
    // Make sure it is compatible and render the checkbox if so.
    if (isset($form['status']['#incompatible_themes_core'][$key])) {
      unset($form['status'][$key]);
      $status = theme('image', 'misc/watchdog-error.png', t('incompatible'), t('Incompatible with this version of Drupal core'));
      $description .= '<div class="incompatible">'. t('This version is incompatible with the !core_version version of Drupal core.', array('!core_version' => VERSION)) .'</div>';
    }
    elseif (isset($form['status']['#incompatible_themes_php'][$key])) {
      unset($form['status'][$key]);
      $status = theme('image', 'misc/watchdog-error.png', t('incompatible'), t('Incompatible with this version of PHP'));
      $php_required = $form['status']['#incompatible_themes_php'][$key];
      if (substr_count($php_required, '.') < 2) {
        $php_required .= '.*';
      }
      $description .= '<div class="incompatible">'. t('This theme requires PHP version @php_required and is incompatible with PHP version !php_version.', array('@php_required' => $php_required, '!php_version' => phpversion())) .'</div>';
    }
    else {
      $status = drupal_render($form['status'][$key]);
    }

    // Style theme info
    $theme = '<div class="theme-info"><h2>'. $info['name'] .'</h2><div class="description">'. $description .'</div></div>';

    // Build rows
    $row = array();
    $row[] = drupal_render($form[$key]['screenshot']);
    $row[] = $theme;
    $row[] = isset($info['version']) ? $info['version'] : '';
    $row[] = array('data' => $status, 'align' => 'center');
    if ($form['theme_default']) {
      $row[] = array('data' => drupal_render($form['theme_default'][$key]), 'align' => 'center');
      $row[] = array('data' => drupal_render($form[$key]['operations']), 'align' => 'center');
    }
    $rows[] = $row;
  }

  $header = array(t('Screenshot'), t('Name'), t('Version'), t('Enabled'), t('Default'), t('Operations'));
  $output = theme('table', $header, $rows);
  $output .= drupal_render($form);
  return $output;
}
