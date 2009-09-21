<?php
// $Id$

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
  if ($admin = db_query("SELECT menu_name, mlid FROM {menu_links} WHERE link_path = 'admin' AND module = 'system'")->fetchAssoc()) {
    $result = db_query("
      SELECT m.*, ml.*
      FROM {menu_links} ml
      INNER JOIN {menu_router} m ON ml.router_path = m.path
      WHERE ml.link_path != 'admin/help' AND menu_name = :menu_name AND ml.plid = :mlid AND hidden = 0", $admin, array('fetch' => PDO::FETCH_ASSOC));
    foreach ($result as $item) {
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
      $block['show'] = FALSE;
      if ($item['block_callback'] && function_exists($item['block_callback'])) {
        $function = $item['block_callback'];
        $block['content'] .= $function();
      }
      $content = system_admin_menu_block($item);
      if ((isset($item['page_callback']) && !in_array($item['page_callback'], array('system_admin_menu_block_page', 'system_admin_config_page', 'system_settings_overview'))) || count($content)) {
        // Only show blocks for items which are not containers, or those which
        // are containers and do have items we can show.
        $block['show'] = TRUE;
        if (empty($content)) {
          // If no items found below, but access checks did not fail, show.
          $block['title'] = l($item['title'], $item['href'], $item['localized_options']);
        }
        else {
          // Theme items below.
          $block['content'] .= theme('admin_block_content', $content);
        }
      }
      // Prepare for sorting as in function _menu_tree_check_access().
      // The weight is offset so it is always positive, with a uniform 5-digits.
      $blocks[(50000 + $item['weight']) . ' ' . $item['title'] . ' ' . $item['mlid']] = $block;
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
 * Menu callback; Provide the administration overview page.
 */
function system_admin_config_page() {
  // Check for status report errors.
  if (system_status(TRUE) && user_access('administer site configuration')) {
    drupal_set_message(t('One or more problems were detected with your Drupal installation. Check the <a href="@status">status report</a> for more information.', array('@status' => url('admin/reports/status'))), 'error');
  }
  $blocks = array();
  if ($admin = db_query("SELECT menu_name, mlid FROM {menu_links} WHERE link_path = 'admin/config' AND module = 'system'")->fetchAssoc()) {
    $result = db_query("
      SELECT m.*, ml.*
      FROM {menu_links} ml
      INNER JOIN {menu_router} m ON ml.router_path = m.path
      WHERE ml.link_path != 'admin/help' AND menu_name = :menu_name AND ml.plid = :mlid AND hidden = 0", $admin, array('fetch' => PDO::FETCH_ASSOC));
    foreach ($result as $item) {
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
      $block['show'] = TRUE;
      if ($item['block_callback'] && function_exists($item['block_callback'])) {
        $function = $item['block_callback'];
        $block['content'] .= $function();
      }
      $block['content'] .= theme('admin_block_content', system_admin_menu_block($item));
      // Prepare for sorting as in function _menu_tree_check_access().
      // The weight is offset so it is always positive, with a uniform 5-digits.
      $blocks[(50000 + $item['weight']) . ' ' . $item['title'] . ' ' . $item['mlid']] = $block;
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
 * This function is often a destination for these blocks.
 * For example, 'admin/structure/types' needs to have a destination to be valid
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
 * Menu callback; prints a listing of admin tasks for each installed module.
 */
function system_admin_by_module() {

  $modules = system_get_module_data();
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
 * Menu callback; displays a module's settings page.
 */
function system_settings_overview() {
  // Check database setup if necessary
  if (function_exists('db_check_setup') && empty($_POST)) {
    db_check_setup();
  }

  $item = menu_get_item('admin/config');
  $content = system_admin_menu_block($item);

  $output = theme('admin_block_content', $content);

  return $output;
}

/**
 * Menu callback; displays a listing of all themes.
 *
 * @ingroup forms
 * @see system_themes_form_submit()
 */
function system_themes_form() {

  $themes = system_get_theme_data();
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
    $screenshot = $screenshot ? theme('image', $screenshot, t('Screenshot for %theme theme', array('%theme' => $theme->info['name'])), '', array('class' => array('screenshot')), FALSE) : t('no screenshot');

    $form[$theme->name]['screenshot'] = array('#markup' => $screenshot);
    $form[$theme->name]['info'] = array(
      '#type' => 'value',
      '#value' => $theme->info,
    );
    $options[$theme->name] = $theme->info['name'];

    if (!empty($theme->status) || $theme->name == variable_get('admin_theme', 0)) {
      $form[$theme->name]['operations'] = array('#markup' => l(t('configure'), 'admin/appearance/settings/' . $theme->name) );
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
      // Require the 'content' region to make sure the main page
      // content has a common place in all themes.
      if (!isset($theme->info['core']) || ($theme->info['core'] != DRUPAL_CORE_COMPATIBILITY) || (!isset($theme->info['regions']['content']))) {
        $incompatible_core[] = $theme->name;
      }
      if (version_compare(phpversion(), $theme->info['php']) < 0) {
        $incompatible_php[$theme->name] = $theme->info['php'];
      }
    }
  }

  $form['status'] = array(
    '#type' => 'checkboxes',
    '#options' => array_fill_keys(array_keys($options), ''),
    '#default_value' => $status,
    '#incompatible_themes_core' => drupal_map_assoc($incompatible_core),
    '#incompatible_themes_php' => $incompatible_php,
  );
  $form['theme_default'] = array(
    '#type' => 'radios',
    '#options' => array_fill_keys(array_keys($options), ''),
    '#default_value' => variable_get('theme_default', 'garland'),
  );

  // Administration theme settings.
  $form['admin_theme'] = array(
    '#type' => 'fieldset',
    '#title' => t('Administration theme'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  );
  $form['admin_theme']['admin_theme'] = array(
    '#type' => 'select',
    '#options' => array(0 => t('Default theme')) + $options,
    '#title' => t('Administration theme'),
    '#description' => t('Choose "Default theme" to always use the same theme as the rest of the site.'),
    '#default_value' => variable_get('admin_theme', 0),
  );
  $form['admin_theme']['node_admin_theme'] = array(
    '#type' => 'checkbox',
    '#title' => t('Use the administration theme when editing or creating content'),
    '#default_value' => variable_get('node_admin_theme', '0'),
  );

  $form['buttons']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save configuration'),
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
  db_update('system')
    ->fields(array('status' => 0))
    ->condition('type', 'theme')
    ->execute();

  if ($form_state['values']['op'] == t('Save configuration')) {
    if (is_array($form_state['values']['status'])) {
      foreach ($form_state['values']['status'] as $key => $choice) {
        // Always enable the default theme, despite its status checkbox being checked:
        if ($choice || $form_state['values']['theme_default'] == $key) {
          $new_theme_list[] = $key;
          db_update('system')
            ->fields(array('status' => 1))
            ->condition('type', 'theme')
            ->condition('name', $key)
            ->execute();
        }
      }
    }
    if ($form_state['values']['admin_theme'] && $form_state['values']['admin_theme'] != $form_state['values']['theme_default']) {
      drupal_set_message(t('Please note that the administration theme is still set to the %admin_theme theme; consequently, the theme on this page remains unchanged. All non-administrative sections of the site, however, will show the selected %selected_theme theme by default.', array(
        '%admin_theme' => $form_state['values']['admin_theme'],
        '%selected_theme' => $form_state['values']['theme_default'],
      )));
    }

    // Save the variables.
    variable_set('theme_default', $form_state['values']['theme_default']);
    variable_set('admin_theme', $form_state['values']['admin_theme']);
    variable_set('node_admin_theme', $form_state['values']['node_admin_theme']);
  }
  else {
    // Revert to defaults: only Garland is enabled.
    variable_del('theme_default');
    variable_del('admin_theme');
    variable_del('node_admin_theme');
    db_update('system')
      ->fields(array('status' => 1))
      ->condition('type', 'theme')
      ->condition('name', 'garland')
      ->execute();
    $new_theme_list = array('garland');
  }

  list_themes(TRUE);
  menu_rebuild();
  drupal_theme_rebuild();
  drupal_set_message(t('The configuration options have been saved.'));
  $form_state['redirect'] = 'admin/appearance';

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
function system_theme_settings($form, &$form_state, $key = '') {
  $directory_path = file_directory_path();
  if (!file_prepare_directory($directory_path, FILE_CREATE_DIRECTORY)) {
    drupal_set_message(t('The directory %directory does not exist or is not writable.', array('%directory' => $directory_path)), 'warning');
  }

  // Default settings are defined in theme_get_settings() in includes/theme.inc
  if ($key) {
    $settings = theme_get_settings($key);
    $var = str_replace('/', '_', 'theme_' . $key . '_settings');
    $themes = system_get_theme_data();
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
    $filename = ($key) ? str_replace('/', '_', $key) . '_logo.' . $parts['extension'] : 'logo.' . $parts['extension'];

    // The image was saved using file_save_upload() and was added to the
    // files table as a temporary file. We'll make a copy and let the garbage
    // collector delete the original upload.
    if ($filepath = file_unmanaged_copy($file->uri, $filename, FILE_EXISTS_REPLACE)) {
      $_POST['default_logo'] = 0;
      $_POST['logo_path'] = $filepath;
      $_POST['toggle_logo'] = 1;
    }
  }

  // Check for a new uploaded favicon, and use that instead.
  if ($file = file_save_upload('favicon_upload')) {
    $parts = pathinfo($file->filename);
    $filename = ($key) ? str_replace('/', '_', $key) . '_favicon.' . $parts['extension'] : 'favicon.' . $parts['extension'];

    // The image was saved using file_save_upload() and was added to the
    // files table as a temporary file. We'll make a copy and let the garbage
    // collector delete the original upload.
    if ($filepath = file_unmanaged_copy($file->uri, $filename, FILE_EXISTS_REPLACE)) {
      $_POST['default_favicon'] = 0;
      $_POST['favicon_path'] = $filepath;
      $_POST['toggle_favicon'] = 1;
    }
  }

  // Toggle settings
  $toggles = array(
    'logo'                      => t('Logo'),
    'name'                      => t('Site name'),
    'slogan'                    => t('Site slogan'),
    'node_user_picture'         => t('User pictures in posts'),
    'comment_user_picture'      => t('User pictures in comments'),
    'comment_user_verification' => t('User verification status in comments'),
    'search'                    => t('Search box'),
    'favicon'                   => t('Shortcut icon'),
    'main_menu'                 => t('Main menu'),
    'secondary_menu'            => t('Secondary menu'),
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
      $form['theme_settings']['toggle_' . $name] = array('#type' => 'checkbox', '#title' => $title, '#default_value' => $settings['toggle_' . $name]);
      // Disable checkboxes for features not supported in the current configuration.
      if (isset($disabled['toggle_' . $name])) {
        $form['theme_settings']['toggle_' . $name]['#disabled'] = TRUE;
      }
    }
  }

  if (!element_children($form['theme_settings'])) {
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
      '#attributes' => array('class' => array('theme-settings-bottom')),
    );
    $form['logo']['default_logo'] = array(
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
      '#description' => t("Your shortcut icon, or 'favicon', is displayed in the address bar and bookmarks of most browsers."),
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
    // Include the theme's theme-settings.php file
    $filename = DRUPAL_ROOT . '/' . str_replace("/$key.info", '', $themes[$key]->filename) . '/theme-settings.php';
    if (!file_exists($filename) and !empty($themes[$key]->info['base theme'])) {
      // If the theme doesn't have a theme-settings.php file, use the base theme's.
      $base = $themes[$key]->info['base theme'];
      $filename = DRUPAL_ROOT . '/' . str_replace("/$base.info", '', $themes[$base]->filename) . '/theme-settings.php';
    }
    if (file_exists($filename)) {
      require_once $filename;
    }

    // Call engine-specific settings.
    $function = $themes[$key]->prefix . '_engine_settings';
    if (function_exists($function)) {
      $group = $function($settings, $form);
      if (!empty($group)) {
        $form['engine_specific'] = array('#type' => 'fieldset', '#title' => t('Theme-engine-specific settings'), '#description' => t('These settings only exist for all the templates and styles based on the %engine theme engine.', array('%engine' => $themes[$key]->prefix)));
        $form['engine_specific'] = array_merge($form['engine_specific'], $group);
      }
    }
    // Call theme-specific settings.
    $function = $key . '_settings';
    if (!function_exists($function)) {
      $function = $themes[$key]->prefix . '_settings';
    }
    if (function_exists($function)) {
      $group = $function($settings, $form);
      if (!empty($group)) {
        $form['theme_specific'] = array('#type' => 'fieldset', '#title' => t('Theme-specific settings'), '#description' => t('These settings only exist for the %theme theme and all the styles based on it.', array('%theme' => $themes[$key]->info['name'])));
        $form['theme_specific'] = array_merge($form['theme_specific'], $group);
      }
    }
  }

  $form = system_settings_form($form, FALSE);
  // We don't want to call system_settings_form_submit(), so change #submit.
  $form['#submit'] = array('system_theme_settings_submit');
  return $form;
}

/**
 * Process system_theme_settings form submissions.
 */
function system_theme_settings_submit($form, &$form_state) {
  $values = $form_state['values'];
  if (empty($values['default_favicon']) && !empty($values['favicon_path'])) {
    $values['favicon_mimetype'] = file_get_mimetype($values['favicon_path']);
  }
  $key = $values['var'];

  // Exclude unnecessary elements before saving.
  unset($values['var'], $values['submit'], $values['reset'], $values['form_id'], $values['op'], $values['form_build_id'], $values['form_token']);
  variable_set($key, $values);
  drupal_set_message(t('The configuration options have been saved.'));

  cache_clear_all();
}

/**
 * Recursively check compatibility.
 *
 * @param $incompatible
 *   An associative array which at the end of the check contains all
 *   incompatible files as the keys, their values being TRUE.
 * @param $files
 *   The set of files that will be tested.
 * @param $file
 *   The file at which the check starts.
 * @return
 *   Returns TRUE if an incompatible file is found, NULL (no return value)
 *   otherwise.
 */
function _system_is_incompatible(&$incompatible, $files, $file) {
  if (isset($incompatible[$file->name])) {
    return TRUE;
  }
  // Recursively traverse required modules, looking for incompatible modules.
  foreach ($file->requires as $requires) {
    if (isset($files[$requires]) && _system_is_incompatible($incompatible, $files, $files[$requires])) {
      $incompatible[$file->name] = TRUE;
      return TRUE;
    }
  }
}

/**
 * Menu callback; provides module enable/disable interface.
 *
 * The list of modules gets populated by module.info files, which contain each module's name,
 * description and information about which modules it requires.
 * @see drupal_parse_info_file for information on module.info descriptors.
 *
 * Dependency checking is performed to ensure that a module:
 * - can not be enabled if there are disabled modules it requires.
 * - can not be disabled if there are enabled modules which depend on it.
 *
 * @param $form_state
 *   An associative array containing the current state of the form.
 * @ingroup forms
 * @see theme_system_modules()
 * @see system_modules_submit()
 * @return
 *   The form array.
 */
function system_modules($form, $form_state = array()) {
  // Get current list of modules.
  $files = system_get_module_data();

  // Remove hidden modules from display list.
  foreach ($files as $filename => $file) {
    if (!empty($file->info['hidden']) || !empty($file->info['required'])) {
      unset($files[$filename]);
    }
  }

  uasort($files, 'system_sort_modules_by_info_name');

  // If the modules form was submitted, then system_modules_submit() runs first
  // and if there are unfilled required modules, then form_state['storage'] is
  // filled, triggering a rebuild. In this case we need to display a
  // confirmation form.
  if (!empty($form_state['storage'])) {
    return system_modules_confirm_form($files, $form_state['storage']);
  }

  $modules = array();
  $form['modules'] = array('#tree' => TRUE);

  // Used when checking if module implements a help page.
  $help_arg = module_exists('help') ? drupal_help_arg() : FALSE;

  // Iterate through each of the modules.
  foreach ($files as $filename => $module) {
    $extra = array();
    $extra['enabled'] = (bool) $module->status;
    // If this module requires other modules, add them to the array.
    foreach ($module->requires as $requires => $v) {
      if (!isset($files[$requires])) {
        $extra['requires'][$requires] = t('@module (<span class="admin-missing">missing</span>)', array('@module' => drupal_ucfirst($requires)));
        $extra['disabled'] = TRUE;
      }
      else {
        $requires_name = $files[$requires]->info['name'];
        if ($incompatible_version = drupal_check_incompatibility($v, str_replace(DRUPAL_CORE_COMPATIBILITY . '-', '', $files[$requires]->info['version']))) {
          $extra['requires'][$requires] = t('@module (<span class="admin-missing">incompatible with</span> version @version)', array(
            '@module' => $requires_name . $incompatible_version,
            '@version' => $files[$requires]->info['version'],
          ));
          $extra['disabled'] = TRUE;
        }
        elseif ($files[$requires]->status) {
          $extra['requires'][$requires] = t('@module (<span class="admin-enabled">enabled</span>)', array('@module' => $requires_name));
        }
        else {
          $extra['requires'][$requires] = t('@module (<span class="admin-disabled">disabled</span>)', array('@module' => $requires_name));
        }
      }
    }
    // Generate link for module's help page, if there is one.
    if ($help_arg && $module->status && in_array($filename, module_implements('help'))) {
      if (module_invoke($filename, 'help', "admin/help#$filename", $help_arg)) {
        // Module has a help page.
        $extra['help'] = theme('more_help_link', url("admin/help/$filename"));
      }
    }
    // Mark dependents disabled so the user cannot remove required modules.
    $dependents = array();
    // If this module is required by other modules, list those, and then make it
    // impossible to disable this one.
    foreach ($module->required_by as $required_by => $v) {
      // Hidden modules are unset already.
      if (isset($files[$required_by])) {
        if ($files[$required_by]->status == 1) {
          $extra['required_by'][] = t('@module (<span class="admin-enabled">enabled</span>)', array('@module' => $files[$required_by]->info['name']));
          $extra['disabled'] = TRUE;
        }
        else {
          $extra['required_by'][] = t('@module (<span class="admin-disabled">disabled</span>)', array('@module' => $files[$required_by]->info['name']));
        }
      }
    }
    $form['modules'][$module->info['package']][$filename] = _system_modules_build_row($module->info, $extra);
  }
  // Add basic information to the fieldsets.
  foreach (element_children($form['modules']) as $package) {
    $form['modules'][$package] += array(
      '#type' => 'fieldset',
      '#title' => t($package),
      '#collapsible' => TRUE,
      '#theme' => 'system_modules_fieldset',
      '#header' => array(
        array('data' => t('Enabled'), 'class' => array('checkbox')),
        t('Name'),
        t('Version'),
        t('Description'),
      ),
    );
  }

  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save configuration'),
  );
  $form['#action'] = url('admin/config/modules/list/confirm');

  return $form;
}

/**
 * Array sorting callback; sorts modules or themes by their name.
 */
function system_sort_modules_by_info_name($a, $b) {
  return strcasecmp($a->info['name'], $b->info['name']);
}

/**
 * Build a table row for the system modules page.
 */
function _system_modules_build_row($info, $extra) {
  // Add in the defaults.
  $extra += array(
    'requires' => array(),
    'required_by' => array(),
    'disabled' => FALSE,
    'enabled' => FALSE,
    'help' => '',
  );
  $form = array(
    '#tree' => TRUE,
  );
  // Set the basic properties.
  $form['name'] = array(
    '#markup' => t($info['name']),
  );
  $form['description'] = array(
    '#markup' => t($info['description']),
  );
  $form['version'] = array(
    '#markup' => $info['version'],
  );
  $form['#requires'] = $extra['requires'];
  $form['#required_by'] = $extra['required_by'];

  // Check the compatibilities.
  $compatible = TRUE;
  $status_short = '';
  $status_long = '';

  // Check the core compatibility.
  if (!isset($info['core']) || $info['core'] != DRUPAL_CORE_COMPATIBILITY || empty($info['files'])) {
    $compatible = FALSE;
    $status_short .= t('Incompatible with this version of Drupal core. ');
    $status_long .= t('This version is incompatible with the !core_version version of Drupal core. ', array('!core_version' => VERSION));
  }

  // Ensure this module is compatible with the currently installed version of PHP.
  if (version_compare(phpversion(), $info['php']) < 0) {
    $compatible = FALSE;
    $status_short .= t('Incompatible with this version of PHP');
    if (substr_count($info['php'], '.') < 2) {
      $php_required .= '.*';
    }
    $status_long .= t('This module requires PHP version @php_required and is incompatible with PHP version !php_version.', array('@php_required' => $php_required, '!php_version' => phpversion()));
  }

  // If this module is compatible, present a checkbox indicating
  // this module may be installed. Otherwise, show a big red X.
  if ($compatible) {
    $form['enable'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enable'),
      '#default_value' => $extra['enabled'],
    );
    if ($extra['disabled']) {
      $form['enable']['#disabled'] = TRUE;
    }
  }
  else {
    $form['enable'] = array(
      '#markup' =>  theme('image', 'misc/watchdog-error.png', t('incompatible'), $status_short),
    );
    $form['description']['#markup'] .= theme('system_modules_incompatible', $status_long);
  }

  // Show a "more help" link for modules that have them.
  if ($extra['help']) {
    $form['help'] = array(
      '#markup' => $extra['help'],
    );
  }
  return $form;
}

/**
 * Display confirmation form for required modules.
 *
 * @param $modules
 *   Array of module file objects as returned from system_get_module_data().
 * @param $storage
 *   The contents of $form_state['storage']; an array with two
 *   elements: the list of required modules and the list of status
 *   form field values from the previous screen.
 * @ingroup forms
 */
function system_modules_confirm_form($modules, $storage) {
  $items = array();

  $form['validation_modules'] = array('#type' => 'value', '#value' => $modules);
  $form['status']['#tree'] = TRUE;

  foreach ($storage['more_modules'] as $info) {
    $t_argument = array(
      '@module' => $info['name'],
      '@required' => implode(', ', $info['requires']),
    );
    $items[] = format_plural(count($info['requires']), 'You must enable the @required module to install @module.', 'You must enable the @required modules to install @module.', $t_argument);
  }
  $form['text'] = array('#markup' => theme('item_list', $items));

  if ($form) {
    // Set some default form values
    $form = confirm_form(
      $form,
      t('Some required modules must be enabled'),
      'admin/config/modules',
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
  include_once DRUPAL_ROOT . '/includes/install.inc';
  $modules = array();
  // If we're not coming from the confirmation form, build the list of modules.
  if (!isset($form_state['storage'])) {
    foreach ($form_state['values']['modules'] as $group_name => $group) {
      foreach ($group as $module => $enabled) {
        $modules[$module] = array('group' => $group_name, 'enabled' => $enabled['enable']);
      }
    }
  }
  else {
    // If we are coming from the confirmation form, fetch
    // the modules out of $form_state.
    $modules = $form_state['storage']['modules'];
  }

  // Get a list of all modules, it will be used to find which module requires
  // which.
  $files = system_get_module_data();

  // The modules to be enabled.
  $modules_to_be_enabled = array();
  // The modules to be disabled.
  $disable_modules = array();
  // The modules to be installed.
  $new_modules = array();
  // Modules that need to be switched on because other modules require them.
  $more_modules = array();
  // Go through each module, finding out if we should enable, install, or
  // disable it. Also, we find out if there are modules it requires that are
  // not enabled.
  foreach ($modules as $name => $module) {
    // If it's enabled, find out whether to just
    // enable it, or install it.
    if ($module['enabled']) {
      if (drupal_get_installed_schema_version($name) == SCHEMA_UNINSTALLED) {
        $new_modules[$name] = $name;
      }
      elseif (!module_exists($name)) {
        $modules_to_be_enabled[$name] = $name;
      }
      // If we're not coming from a confirmation form, search for modules the
      // new ones require and see whether there are any that additionally
      // need to be switched on.
      if (empty($form_state['storage'])) {
        foreach ($form['modules'][$module['group']][$name]['#requires'] as $requires => $v) {
          if (!$modules[$requires]['enabled']) {
            if (!isset($more_modules[$name])) {
              $more_modules[$name]['name'] = $files[$name]->info['name'];
            }
            $more_modules[$name]['requires'][$requires] = $files[$requires]->info['name'];
          }
          $modules[$requires] = array('group' => $files[$requires]->info['package'], 'enabled' => TRUE);
        }
      }
    }
  }
  // A second loop is necessary, otherwise the modules set to be enabled in the
  // previous loop would not be found.
  foreach ($modules as $name => $module) {
    if (module_exists($name) && !$module['enabled']) {
      $disable_modules[$name] = $name;
    }
  }
  if ($more_modules) {
    // If we need to switch on more modules because other modules require
    // them and they haven't confirmed, don't process the submission yet. Store
    // the form submission data needed later.
    if (!isset($form_state['values']['confirm'])) {
      $form_state['storage'] = array('more_modules' => $more_modules, 'modules' => $modules);
      return;
    }
    // Otherwise, install or enable the modules.
    else {
      foreach ($form_state['storage']['more_modules'] as $info) {
        foreach ($info['requires'] as $requires => $name) {
          if (drupal_get_installed_schema_version($name) == SCHEMA_UNINSTALLED) {
            $new_modules[$name] = $name;
          }
          else {
            $modules_to_be_enabled[$name] = $name;
          }
        }
      }
    }
  }
  // Now we have installed every module as required (either by the user or
  // because other modules require them) so we don't need the temporary
  // storage anymore.
  unset($form_state['storage']);

  $old_module_list = module_list();

  // Enable the modules needing enabling.
  if (!empty($modules_to_be_enabled)) {
    $sort = array();
    foreach ($modules_to_be_enabled as $module) {
      $sort[$module] = $files[$module]->sort;
    }
    array_multisort($sort, $modules_to_be_enabled);
    module_enable($modules_to_be_enabled);
  }
  // Disable the modules that need disabling.
  if (!empty($disable_modules)) {
    $sort = array();
    foreach ($disable_modules as $module) {
      $sort[$module] = $files[$module]->sort;
    }
    array_multisort($sort, $disable_modules);
    module_disable($disable_modules);
  }

  // Install new modules.
  if (!empty($new_modules)) {
    $sort = array();
    foreach ($new_modules as $key => $module) {
      if (!drupal_check_module($module)) {
        unset($new_modules[$key]);
      }
      $sort[$module] = $files[$module]->sort;
    }
    array_multisort($sort, $new_modules);
    drupal_install_modules($new_modules);
  }

  $current_module_list = module_list(TRUE);
  if ($old_module_list != $current_module_list) {
    drupal_set_message(t('The configuration options have been saved.'));
  }

  // Clear all caches.
  registry_rebuild();
  drupal_theme_rebuild();
  node_types_rebuild();
  menu_rebuild();
  cache_clear_all('schema', 'cache');
  cache_clear_all('entity_info', 'cache');
  drupal_clear_css_cache();
  drupal_clear_js_cache();

  $form_state['redirect'] = 'admin/config/modules';

  // Notify locale module about module changes, so translations can be
  // imported. This might start a batch, and only return to the redirect
  // path after that.
  module_invoke('locale', 'system_update', $new_modules);

  // Synchronize to catch any actions that were added or removed.
  actions_synchronize();

  return;
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
function system_modules_uninstall($form, $form_state = NULL) {
  // Make sure the install API is available.
  include_once DRUPAL_ROOT . '/includes/install.inc';

  // Display the confirm form if any modules have been submitted.
  if (isset($form_state) && $confirm_form = system_modules_uninstall_confirm_form($form_state['storage'])) {
    return $confirm_form;
  }

  // Pull all disabled modules from the system table.
  $disabled_modules = db_query("SELECT name, filename, info FROM {system} WHERE type = 'module' AND status = 0 AND schema_version > :schema ORDER BY name", array(':schema' => SCHEMA_UNINSTALLED));
  foreach ($disabled_modules as $module) {
    // Grab the module info
    $info = unserialize($module->info);

    // Load the .install file, and check for an uninstall or schema hook.
    // If the hook exists, the module can be uninstalled.
    module_load_install($module->name);
    if (module_hook($module->name, 'uninstall') || module_hook($module->name, 'schema')) {
      $form['modules'][$module->name]['name'] = array('#markup' => $info['name'] ? $info['name'] : $module->name);
      $form['modules'][$module->name]['description'] = array('#markup' => t($info['description']));
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
    $form['#action'] = url('admin/config/modules/uninstall/confirm');
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
    $info = drupal_parse_info_file(dirname(drupal_get_filename('module', $module)) . '/' . $module . '.info');
    $uninstall[] = $info['name'];
    $form['uninstall'][$module] = array('#type' => 'hidden',
      '#value' => 1,
    );
  }

  // Display a confirm form if modules have been selected.
  if (isset($uninstall)) {
    $form['#confirmed'] = TRUE;
    $form['uninstall']['#tree'] = TRUE;
    $form['modules'] = array('#markup' => '<p>' . t('The following modules will be completely uninstalled from your site, and <em>all data from these modules will be lost</em>!') . '</p>' . theme('item_list', $uninstall));
    $form = confirm_form(
      $form,
      t('Confirm uninstall'),
      'admin/config/modules/uninstall',
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
    drupal_goto('admin/config/modules/uninstall');
  }
}

/**
 * Processes the submitted uninstall form.
 */
function system_modules_uninstall_submit($form, &$form_state) {
  // Make sure the install API is available.
  include_once DRUPAL_ROOT . '/includes/install.inc';

  if (!empty($form['#confirmed'])) {
    // Call the uninstall routine for each selected module.
    $modules = array_keys($form_state['values']['uninstall']);
    drupal_uninstall_modules($modules);
    drupal_set_message(t('The selected modules have been uninstalled.'));

    unset($form_state['storage']);
    $form_state['redirect'] = 'admin/config/modules/uninstall';
  }
  else {
    $form_state['storage'] = $form_state['values'];
  }
}

/**
 * Menu callback. Display blocked IP addresses.
 */
function system_ip_blocking() {
  $output = '';
  $rows = array();
  $header = array(t('IP address'), t('Operations'));
  $result = db_query('SELECT * FROM {blocked_ips}');
  foreach ($result as $ip) {
    $rows[] = array(
      $ip->ip,
      l(t('delete'), "admin/config/people/ip-blocking/delete/$ip->iid"),
    );
  }

  $build['system_ip_blocking_form'] = drupal_get_form('system_ip_blocking_form');

  $build['system_ip_blocking_table'] = array(
    '#theme' => 'table',
    '#header' => $header,
    '#rows' => $rows,
  );

  return $build;
}

/**
 * Define the form for blocking IP addresses.
 *
 * @ingroup forms
 * @see system_ip_blocking_form_validate()
 * @see system_ip_blocking_form_submit()
 */
function system_ip_blocking_form($form, $form_state) {
  $form['ip'] = array(
    '#title' => t('IP address'),
    '#type' => 'textfield',
    '#size' => 64,
    '#maxlength' => 32,
    '#default_value' => arg(3),
    '#description' => t('Enter a valid IP address.'),
  );
  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save'),
  );
  $form['#submit'][] = 'system_ip_blocking_form_submit';
  $form['#validate'][] = 'system_ip_blocking_form_validate';
  return $form;
}

function system_ip_blocking_form_validate($form, &$form_state) {
  $ip = trim($form_state['values']['ip']);
  if (db_query("SELECT * FROM {blocked_ips} WHERE ip = :ip", array(':ip' => $ip))->fetchField()) {
    form_set_error('ip', t('This IP address is already blocked.'));
  }
  elseif ($ip == ip_address()) {
    form_set_error('ip', t('You may not block your own IP address.'));
  }
  elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE) == FALSE) {
    form_set_error('ip', t('Please enter a valid IP address.'));
  }
}

function system_ip_blocking_form_submit($form, &$form_state) {
  $ip = trim($form_state['values']['ip']);
  db_insert('blocked_ips')
    ->fields(array('ip' => $ip))
    ->execute();
  drupal_set_message(t('The IP address %ip has been blocked.', array('%ip' => $ip)));
  $form_state['redirect'] = 'admin/config/people/ip-blocking';
  return;
}

/**
 * IP deletion confirm page.
 *
 * @see system_ip_blocking_delete_submit()
 */
function system_ip_blocking_delete($form, &$form_state, $iid) {
  $form['blocked_ip'] = array(
    '#type' => 'value',
    '#value' => $iid,
  );
  return confirm_form($form, t('Are you sure you want to delete %ip?', array('%ip' => $iid['ip'])), 'admin/config/people/ip-blocking', t('This action cannot be undone.'), t('Delete'), t('Cancel'));
}

/**
 * Process system_ip_blocking_delete form submissions.
 */
function system_ip_blocking_delete_submit($form, &$form_state) {
  $blocked_ip = $form_state['values']['blocked_ip'];
  db_delete('blocked_ips')
    ->condition('iid', $blocked_ip['iid'])
    ->execute();
  watchdog('user', 'Deleted %ip', array('%ip' => $blocked_ip['ip']));
  drupal_set_message(t('The IP address %ip was deleted.', array('%ip' => $blocked_ip['ip'])));
  $form_state['redirect'] = 'admin/config/people/ip-blocking';
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
    '#title' => t('Site name'),
    '#default_value' => 'Drupal',
    '#required' => TRUE
  );
  $form['site_mail'] = array(
    '#type' => 'textfield',
    '#title' => t('E-mail address'),
    '#default_value' => ini_get('sendmail_from'),
    '#description' => t("The <em>From</em> address in automated e-mails sent during registration and new password requests, and other notifications. (Use an address ending in your site's domain to help prevent this e-mail being flagged as spam.)"),
    '#required' => TRUE,
  );
  $form['site_slogan'] = array(
    '#type' => 'textfield',
    '#title' => t('Slogan'),
    '#default_value' => '',
  );
  $form['site_frontpage'] = array(
    '#type' => 'textfield',
    '#title' => t('Default front page'),
    '#default_value' => 'node',
    '#size' => 40,
    '#description' => t('The home page displays content from this relative URL. If unsure, specify "node".'),
    '#field_prefix' => url(NULL, array('absolute' => TRUE)) . (variable_get('clean_url', 0) ? '' : '?q='),
    '#required' => TRUE,
  );
  $form['default_nodes_main'] = array(
    '#type' => 'select', '#title' => t('Number of posts on front page'),
    '#default_value' => 10,
    '#options' => drupal_map_assoc(array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 15, 20, 25, 30)),
    '#description' => t('The maximum number of posts displayed on overview pages like the frontpage.')
  );
  $form['site_403'] = array(
    '#type' => 'textfield',
    '#title' => t('Default 403 (access denied) page'),
    '#default_value' => '',
    '#size' => 40,
    '#description' => t('This page is displayed when the requested document is denied to the current user. Leave blank to display a generic "access denied" page.'),
    '#field_prefix' => url(NULL, array('absolute' => TRUE)) . (variable_get('clean_url', 0) ? '' : '?q=')
  );
  $form['site_404'] = array(
    '#type' => 'textfield',
    '#title' => t('Default 404 (not found) page'),
    '#default_value' => '',
    '#size' => 40,
    '#description' => t('This page is displayed when no other content matches the requested document. Leave blank to display a generic "page not found" page.'),
    '#field_prefix' => url(NULL, array('absolute' => TRUE)) . (variable_get('clean_url', 0) ? '' : '?q=')
  );
  $form['cron_safe_threshold'] = array(
    '#type' => 'select',
    '#title' => t('Automatically run cron'),
    '#default_value' => variable_get('cron_safe_threshold', DRUPAL_CRON_DEFAULT_THRESHOLD),
    '#options' => array(0 => t('Never')) + drupal_map_assoc(array(3600, 10800, 21600, 43200, 86400, 604800), 'format_interval'),
    '#description' => t('When enabled, the site will check whether cron has been run in the configured interval and automatically run it upon the next page request. For more information visit the <a href="@status-report-url">status report page</a>.', array('@status-report-url' => url('admin/reports/status'))),
  );

  $form['#validate'][] = 'system_site_information_settings_validate';
  $form['#submit'][] = 'system_site_information_settings_submit';

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
  $item = array('link_path' => drupal_get_normal_path($form_state['values']['site_frontpage']));
  if (!menu_valid_path($item)) {
    form_set_error('site_frontpage', t("The path '@path' is either invalid or you do not have access to it.", array('@path' => $item['link_path'])));
  }
}

/**
 * Form submit handler for the site-information form.
 */
function system_site_information_settings_submit($form, &$form_state) {
  // Clear caches when the cron threshold is changed to ensure that the cron
  // image is not contained in cached pages.
  $cron_threshold = variable_get('cron_safe_threshold', DRUPAL_CRON_DEFAULT_THRESHOLD);
  if (($cron_threshold > 0 && $form_state['input']['cron_safe_threshold'] == 0) || ($cron_threshold == 0 && $form_state['input']['cron_safe_threshold'] > 0)) {
    cache_clear_all();
  }
}

/**
 * Form builder; Configure error reporting settings.
 *
 * @ingroup forms
 * @see system_settings_form()
 */
function system_logging_settings() {
  $form['error_level'] = array(
    '#type' => 'radios',
    '#title' => t('Display PHP messages'),
    '#default_value' => ERROR_REPORTING_DISPLAY_ALL,
    '#options' => array(
      ERROR_REPORTING_HIDE => t('None'),
      ERROR_REPORTING_DISPLAY_SOME => t('Errors and warnings'),
      ERROR_REPORTING_DISPLAY_ALL => t('All messages'),
    ),
  );

  return system_settings_form($form);
}

/**
 * Form builder; Configure site performance settings.
 *
 * @ingroup forms
 * @see system_settings_form()
 */
function system_performance_settings() {
  drupal_add_js(drupal_get_path('module', 'system') . '/system.js');

  $form['clear_cache'] = array(
    '#type' => 'fieldset',
    '#title' => t('Clear cache'),
  );

  $form['clear_cache']['clear'] = array(
    '#type' => 'submit',
    '#value' => t('Clear all caches'),
    '#submit' => array('system_clear_cache_submit'),
  );

  $form['caching'] = array(
    '#type' => 'fieldset',
    '#title' => t('Caching'),
  );

  $cache = variable_get('cache', CACHE_DISABLED);
  $form['caching']['cache'] = array(
    '#type' => 'radios',
    '#title' => t('Page cache for anonymous users'),
    '#default_value' => $cache,
    '#options' => array(CACHE_DISABLED => t('Disabled'), CACHE_NORMAL => t('Normal (recommended)')),
  );
  $period = drupal_map_assoc(array(0, 60, 180, 300, 600, 900, 1800, 2700, 3600, 10800, 21600, 32400, 43200, 86400), 'format_interval');
  $period[0] = '<' . t('none') . '>';
  $form['caching']['cache_lifetime'] = array(
    '#type' => 'select',
    '#title' => t('Minimum cache lifetime'),
    '#default_value' => variable_get('cache_lifetime', 0),
    '#options' => $period,
    '#description' => t('The minimum amount of time that will elapse before the caches are recreated.')
  );

  $directory = 'public://';
  $is_writable = is_dir($directory) && is_writable($directory);
  $disabled = !$is_writable;
  $disabled_message = '';
  if(!$is_writable) {
    $disabled_message = ' ' . t('<strong class="error">Please set up the <a href="!file-system">public files directory</a> to make these optimizations available.</strong>', array('!file-system' => url('admin/config/media/file-system')));
  }

  $form['bandwidth_optimization'] = array(
    '#type' => 'fieldset',
    '#title' => t('Bandwidth optimization'),
    '#description' => t('External resources  can be optimized automatically, which can reduce both the size and number of requests made to your website.') . $disabled_message,
  );

  // Do not allow Drupal to gzip pages if the server alredy does that.
  if (strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') === FALSE) {
    $js_hide = $cache == CACHE_DISABLED ? ' class="js-hide"' : '';
    $form['bandwidth_optimization']['page_compression'] = array(
      '#type' => 'checkbox',
      '#title' => t('Compress cached pages.'),
      '#default_value' => variable_get('page_compression', TRUE),
      '#prefix' => '<div id="page-compression-wrapper"' . $js_hide . '>',
      '#suffix' => '</div>',
    );
  }
  $form['bandwidth_optimization']['preprocess_css'] = array(
    '#type' => 'checkbox',
    '#title' => t('Aggregate and compress CSS files into one file.'),
    '#default_value' => intval(variable_get('preprocess_css', 0) && $is_writable),
    '#disabled' => $disabled,
  );
  $form['bandwidth_optimization']['preprocess_js'] = array(
    '#type' => 'checkbox',
    '#title' => t('Aggregate JavaScript files into one file.'),
    '#default_value' => intval(variable_get('preprocess_js', 0) && $is_writable),
    '#disabled' => $disabled,
  );

  $form['#submit'][] = 'drupal_clear_css_cache';
  $form['#submit'][] = 'drupal_clear_js_cache';

  return system_settings_form($form, FALSE);
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

  $form['file_public_path'] = array(
    '#type' => 'textfield',
    '#title' => t('Public file system path'),
    '#default_value' => file_directory_path(),
    '#maxlength' => 255,
    '#description' => t('A local file system path where public files will be stored. This directory must exist and be writable by Drupal. This directory must be relative to the Drupal installation directory and be accessible over the web.'),
    '#after_build' => array('system_check_directory'),
  );

  $form['file_private_path'] = array(
    '#type' => 'textfield',
    '#title' => t('Private file system path'),
    '#default_value' => file_directory_path('private'),
    '#maxlength' => 255,
    '#description' => t('A local file system path where private files will be stored. This directory must exist and be writable by Drupal. This directory should not be accessible over the web.'),
    '#after_build' => array('system_check_directory'),
  );

  $form['file_temporary_path'] = array(
    '#type' => 'textfield',
    '#title' => t('Temporary directory'),
    '#default_value' => file_directory_path('temporary'),
    '#maxlength' => 255,
    '#description' => t('A local file system path where temporary files will be stored. This directory should not be accessible over the web.'),
    '#after_build' => array('system_check_directory'),
  );
  $wrappers = file_get_stream_wrappers();
  $options = array(
    'public' => $wrappers['public']['description'],
    'private' => $wrappers['private']['description']
  );
  $form['file_default_scheme'] = array(
    '#type' => 'radios',
    '#title' => t('Default download method'),
    '#default_value' => 'public',
    '#options' => $options,
    '#description' => t('This setting is used as the preferred download method. The use of public files is more efficient, but does not provide any access control.'),
  );

  return system_settings_form($form, TRUE);
}

/**
 * Form builder; Configure site image toolkit usage.
 *
 * @ingroup forms
 * @see system_settings_form()
 */
function system_image_toolkit_settings() {
  $toolkits_available = image_get_available_toolkits();
  $current_toolkit = image_get_toolkit();

  if (count($toolkits_available) == 0) {
    variable_del('image_toolkit');
    $form['image_toolkit_help'] = array(
      '#markup' => t("No image toolkits were detected. Drupal includes support for <a href='!gd-link'>PHP's built-in image processing functions</a> but they were not detected on this system. You should consult your system administrator to have them enabled, or try using a third party toolkit.", array('gd-link' => url('http://php.net/gd'))),
    );
    return $form;
  }

  if (count($toolkits_available) > 1) {
    $form['image_toolkit'] = array(
      '#type' => 'radios',
      '#title' => t('Select an image processing toolkit'),
      '#default_value' => $current_toolkit,
      '#options' => $toolkits_available
    );
  }
  else {
    variable_set('image_toolkit', key($toolkits_available));
  }

  // Get the toolkit's settings form.
  $function = 'image_' . $current_toolkit . '_settings';
  if (function_exists($function)) {
    $form['image_toolkit_settings'] = $function();
  }

  return system_settings_form($form, TRUE);
}

/**
 * Form builder; Configure how the site handles RSS feeds.
 *
 * @ingroup forms
 * @see system_settings_form()
 */
function system_rss_feeds_settings() {

  $form['feed_description'] = array(
    '#type' => 'textarea',
    '#title' => t('Feed description'),
    '#default_value' => '',
    '#description' => t('Description of your site, included in each feed.')
  );
  $form['feed_default_items'] = array(
    '#type' => 'select',
    '#title' => t('Number of items in each feed'),
    '#default_value' => 10,
    '#options' => drupal_map_assoc(array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 15, 20, 25, 30)),
    '#description' => t('Default number of items to include in each feed.')
  );
  $form['feed_item_length'] = array(
    '#type' => 'select',
    '#title' => t('Feed content'),
    '#default_value' => 'teaser',
    '#options' => array('title' => t('Titles only'), 'teaser' => t('Titles plus teaser'), 'fulltext' => t('Full text')),
    '#description' => t('Global setting for the default display of content items in each feed.')
  );

  return system_settings_form($form, TRUE);
}

/**
 * Form builder; Configure the site date and time settings.
 *
 * @ingroup forms
 * @see system_settings_form()
 * @see system_regional_settings_submit()
 */
function system_regional_settings() {
  drupal_add_js(drupal_get_path('module', 'system') . '/system.js');
  drupal_add_js(array('dateTime' => array('lookup' => url('admin/config/regional/settings/lookup'))), 'setting');

  include_once DRUPAL_ROOT . '/includes/locale.inc';
  $countries = country_get_list();
  // Add a 'No default country' option to the start of the list.
  $countries = array_merge(array('' => t('No default country')), $countries);

  // Date settings:
  $zones = system_time_zones();

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
    $date_short_choices[$f] = format_date(REQUEST_TIME, 'custom', $f);
  }
  foreach ($date_medium as $f) {
    $date_medium_choices[$f] = format_date(REQUEST_TIME, 'custom', $f);
  }
  foreach ($date_long as $f) {
    $date_long_choices[$f] = format_date(REQUEST_TIME, 'custom', $f);
  }

  $date_long_choices['custom'] = $date_medium_choices['custom'] = $date_short_choices['custom'] = t('Custom format');

  $form['locale'] = array(
    '#type' => 'fieldset',
    '#title' => t('Locale'),
  );

  $form['locale']['site_default_country'] = array(
    '#type' => 'select',
    '#title' => t('Default country'),
    '#default_value' => variable_get('site_default_country', ''),
    '#options' => $countries,
    '#attributes' => array('class' => array('country-detect')),
  );

  $form['locale']['date_first_day'] = array(
    '#type' => 'select',
    '#title' => t('First day of week'),
    '#default_value' => variable_get('date_first_day', 0),
    '#options' => array(0 => t('Sunday'), 1 => t('Monday'), 2 => t('Tuesday'), 3 => t('Wednesday'), 4 => t('Thursday'), 5 => t('Friday'), 6 => t('Saturday')),
  );

  $form['timezone'] = array(
    '#type' => 'fieldset',
    '#title' => t('Time zones'),
  );

  $form['timezone']['date_default_timezone'] = array(
    '#type' => 'select',
    '#title' => t('Default time zone'),
    '#default_value' => variable_get('date_default_timezone', date_default_timezone_get()),
    '#options' => $zones,
  );

  $configurable_timezones = variable_get('configurable_timezones', 1);
  $form['timezone']['configurable_timezones'] = array(
    '#type' => 'checkbox',
    '#title' => t('Users may set their own time zone.'),
    '#default_value' => $configurable_timezones,
  );

  $js_hide = !$configurable_timezones ? ' class="js-hide"' : '';
  $form['timezone']['configurable_timezones_wrapper'] =  array(
    '#prefix' => '<div id="empty-timezone-message-wrapper"' . $js_hide . '>',
    '#suffix' => '</div>',
  );

  $form['timezone']['configurable_timezones_wrapper']['empty_timezone_message'] = array(
    '#type' => 'checkbox',
    '#title' => t('Remind users at login if their time zone is not set.'),
    '#default_value' => variable_get('empty_timezone_message', 0),
    '#description' => t('Only applied if users may set their own time zone.')
  );

  $form['timezone']['configurable_timezones_wrapper']['user_default_timezone'] = array(
    '#type' => 'radios',
    '#title' => t('Time zone for new users'),
    '#default_value' => variable_get('user_default_timezone', DRUPAL_USER_TIMEZONE_DEFAULT),
    '#options' => array(
      DRUPAL_USER_TIMEZONE_DEFAULT => t('Default time zone.'),
      DRUPAL_USER_TIMEZONE_EMPTY   => t('Empty time zone.'),
      DRUPAL_USER_TIMEZONE_SELECT  => t('Users may set their own time zone at registration.'),
    ),
    '#description' => t('Only applied if users may set their own time zone.')
  );

  $form['date_formats'] = array(
    '#type' => 'fieldset',
    '#title' => t('Date formats'),
  );

  $date_format_short = variable_get('date_format_short', $date_short[1]);
  $form['date_formats']['date_format_short'] = array(
    '#prefix' => '<div class="date-container"><div class="select-container">',
    '#suffix' => '</div>',
    '#type' => 'select',
    '#title' => t('Short date format'),
    '#attributes' => array('class' => array('date-format')),
    '#default_value' => (isset($date_short_choices[$date_format_short]) ? $date_format_short : 'custom'),
    '#options' => $date_short_choices,
  );

  $default_short_custom = variable_get('date_format_short_custom', (isset($date_short_choices[$date_format_short]) ? $date_format_short : ''));
  $form['date_formats']['date_format_short_custom'] = array(
    '#prefix' => '<div class="custom-container">',
    '#suffix' => '</div></div>',
    '#type' => 'textfield',
    '#title' => t('Custom short date format'),
    '#attributes' => array('class' => array('custom-format')),
    '#default_value' => $default_short_custom,
    '#description' => t('A user-defined short date format. See the <a href="@url">PHP manual</a> for available options. This format is currently set to display as <span>%date</span>.', array('@url' => 'http://php.net/manual/function.date.php', '%date' => format_date(REQUEST_TIME, 'custom', $default_short_custom))),
  );

  $date_format_medium = variable_get('date_format_medium', $date_medium[1]);
  $form['date_formats']['date_format_medium'] = array(
    '#prefix' => '<div class="date-container"><div class="select-container">',
    '#suffix' => '</div>',
    '#type' => 'select',
    '#title' => t('Medium date format'),
    '#attributes' => array('class' => array('date-format')),
    '#default_value' => (isset($date_medium_choices[$date_format_medium]) ? $date_format_medium : 'custom'),
    '#options' => $date_medium_choices,
  );

  $default_medium_custom = variable_get('date_format_medium_custom', (isset($date_medium_choices[$date_format_medium]) ? $date_format_medium : ''));
  $form['date_formats']['date_format_medium_custom'] = array(
    '#prefix' => '<div class="custom-container">',
    '#suffix' => '</div></div>',
    '#type' => 'textfield',
    '#title' => t('Custom medium date format'),
    '#attributes' => array('class' => array('custom-format')),
    '#default_value' => $default_medium_custom,
    '#description' => t('A user-defined medium date format. See the <a href="@url">PHP manual</a> for available options. This format is currently set to display as <span>%date</span>.', array('@url' => 'http://php.net/manual/function.date.php', '%date' => format_date(REQUEST_TIME, 'custom', $default_medium_custom))),
  );

  $date_format_long = variable_get('date_format_long', $date_long[0]);
  $form['date_formats']['date_format_long'] = array(
    '#prefix' => '<div class="date-container"><div class="select-container">',
    '#suffix' => '</div>',
    '#type' => 'select',
    '#title' => t('Long date format'),
    '#attributes' => array('class' => array('date-format')),
    '#default_value' => (isset($date_long_choices[$date_format_long]) ? $date_format_long : 'custom'),
    '#options' => $date_long_choices,
  );

  $default_long_custom = variable_get('date_format_long_custom', (isset($date_long_choices[$date_format_long]) ? $date_format_long : ''));
  $form['date_formats']['date_format_long_custom'] = array(
    '#prefix' => '<div class="custom-container">',
    '#suffix' => '</div></div>',
    '#type' => 'textfield',
    '#title' => t('Custom long date format'),
    '#attributes' => array('class' => array('custom-format')),
    '#default_value' => $default_long_custom,
    '#description' => t('A user-defined long date format. See the <a href="@url">PHP manual</a> for available options. This format is currently set to display as <span>%date</span>.', array('@url' => 'http://php.net/manual/function.date.php', '%date' => format_date(REQUEST_TIME, 'custom', $default_long_custom))),
  );

  $form = system_settings_form($form, FALSE);
  // We will call system_settings_form_submit() manually, so remove it for now.
  unset($form['#submit']);
  return $form;
}

/**
 * Process system_regional_settings form submissions.
 */
function system_regional_settings_submit($form, &$form_state) {
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
  $result = format_date(REQUEST_TIME, 'custom', $_GET['format']);
  drupal_json_output($result);
}

/**
 * Form builder; Configure the site's maintenance status.
 *
 * @ingroup forms
 * @see system_settings_form()
 */
function system_site_maintenance_mode() {
  $form['maintenance_mode'] = array(
    '#type' => 'checkbox',
    '#title' => t('Put site into maintenance mode'),
    '#default_value' => 0,
    '#description' => t('When enabled, only users with the "Access site in maintenance mode" <a href="@permissions-url">permission</a> are able to access your site to perform maintenance; all other visitors see the maintenance mode message configured below. Authorized users can log in directly via the <a href="@user-login">user login</a> page.', array('@permissions-url' => url('admin/config/people/permissions'), '@user-login' => url('user'))),
  );
  $form['maintenance_mode_message'] = array(
    '#type' => 'textarea',
    '#title' => t('Maintenance mode message'),
    '#default_value' => t('@site is currently under maintenance. We should be back shortly. Thank you for your patience.', array('@site' => variable_get('site_name', 'Drupal'))),
    '#description' => t('Message to show visitors when the site is in maintenance mode.')
  );

  return system_settings_form($form, TRUE);
}

/**
 * Form builder; Configure clean URL settings.
 *
 * @ingroup forms
 * @see system_settings_form()
 */
function system_clean_url_settings($form, &$form_state) {
  global $base_url;

  // When accessing this form using a non-clean URL, allow a re-check to make
  // sure clean URLs can be disabled at all times.
  $available = FALSE;
  if (strpos(request_uri(), '?q=') === FALSE || !empty($_SESSION['clean_url'])) {
    $available = TRUE;
  }
  else {
    $request = drupal_http_request($base_url . '/admin/config/search/clean-urls/check');
    if (isset($request->code) && $request->code == 200) {
      $available = TRUE;
    }
  }

  if ($available) {
    $_SESSION['clean_url'] = TRUE;

    $form['clean_url'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enable clean URLs'),
      '#default_value' => 0,
      '#description' => t('Use URLs like <code>example.com/user</code> instead of <code>example.com/?q=user</code>.'),
    );
    $form = system_settings_form($form);
  }
  else {
    drupal_add_js(drupal_get_path('module', 'system') . '/system.js');

    $form_state['redirect'] = $base_url . '/admin/config/search/clean-urls';
    $form['clean_url_description'] = array(
      '#type' => 'markup',
      '#markup' => '<p>' . t('Use URLs like <code>example.com/user</code> instead of <code>example.com/?q=user</code>.') . ' ' . t('If you are directed to a <em>Page not found (404)</em> error after testing for clean URLs, see the <a href="@handbook">online handbook</a>.', array('@handbook' => 'http://drupal.org/node/15365')) . '</p>',
    );
    $form['clean_url_test'] = array(
      '#type' => 'submit',
      '#value' => t('Run the clean URL test'),
    );
  }

  return $form;
}

/**
 * Menu callback: displays the site status report. Can also be used as a pure check.
 *
 * @param $check
 *   If true, only returns a boolean whether there are system status errors.
 */
function system_status($check = FALSE) {
  // Load .install files
  include_once DRUPAL_ROOT . '/includes/install.inc';
  drupal_load_updates();

  // Check run-time requirements and status information.
  $requirements = module_invoke_all('requirements', 'runtime');
  usort($requirements, '_system_sort_requirements');

  if ($check) {
    return drupal_requirements_severity($requirements) == REQUIREMENT_ERROR;
  }
  // MySQL import might have set the uid of the anonymous user to autoincrement
  // value. Let's try fixing it. See http://drupal.org/node/204411
  db_update('users')
    ->expression('uid', 'uid - uid')
    ->condition('name', '')
    ->condition('pass', '')
    ->condition('status', 0)
    ->execute();
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
 * Default page callback for batches.
 */
function system_batch_page() {
  require_once DRUPAL_ROOT . '/includes/batch.inc';
  $output = _batch_page();
  
  // Use the same theme that the page that started the batch.
  $batch = &batch_get();
  $GLOBALS['custom_theme'] = $batch['theme'];

  if ($output === FALSE) {
    drupal_access_denied();
  }
  elseif (isset($output)) {
    // Force a page without blocks or messages to
    // display a list of collected messages later.
    drupal_set_page_content($output);
    $page = element_info('page');
    $page['#show_messages'] = FALSE;
    return $page;
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
  if (empty($block['show'])) {
    return '';
  }

  if (empty($block['content'])) {
    $output = <<< EOT
    <div class="admin-panel">
      <h3>
        $block[title]
      </h3>
      <div class="description">
        $block[description]
      </div>
    </div>
EOT;
  }
  else {
    $output = <<< EOT
    <div class="admin-panel">
      <h3>
        $block[title]
      </h3>
     <div class="body">
       $block[content]
      </div>
    </div>
EOT;
  }
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
      $output .= '<li class="leaf">' . l($item['title'], $item['href'], $item['localized_options']) . '</li>';
    }
    $output .= '</ul>';
  }
  else {
    $output = '<dl class="admin-list">';
    foreach ($content as $item) {
      $output .= '<dt>' . l($item['title'], $item['href'], $item['localized_options']) . '</dt>';
      $output .= '<dd>' . $item['description'] . '</dd>';
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

  $output = '<div class="admin clearfix">';
  $output .= theme('system_compact_link');

  foreach ($container as $id => $data) {
    $output .= '<div class="' . $id . ' clearfix">';
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
      $block['show'] = TRUE;

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

  $output = '<div class="admin clearfix">';
  foreach ($container as $id => $data) {
    $output .= '<div class="' . $id . ' clearfix">';
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
      $class = $classes[isset($requirement['severity']) ? (int)$requirement['severity'] : 0] . ' ' . $class;

      // Output table row(s)
      if (!empty($requirement['description'])) {
        $output .= '<tr class="' . $class . ' merge-down"><th>' . $requirement['title'] . '</th><td>' . $requirement['value'] . '</td></tr>';
        $output .= '<tr class="' . $class . ' merge-up"><td colspan="2">' . $requirement['description'] . '</td></tr>';
      }
      else {
        $output .= '<tr class="' . $class . '"><th>' . $requirement['title'] . '</th><td>' . $requirement['value'] . '</td></tr>';
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
function theme_system_modules_fieldset($form) {
  // Individual table headers.
  $rows = array();
  // Iterate through all the modules, which are
  // children of this fieldset.
  foreach (element_children($form) as $key) {
    // Stick it into $module for easier accessing.
    $module = $form[$key];
    $row = array();
    unset($module['enable']['#title']);
    $row[] = array('class' => array('checkbox'), 'data' => drupal_render($module['enable']));
    $label = '<label';
    if (isset($module['enable']['#id'])) {
      $label .= ' for="' . $module['enable']['#id'] . '"';
    }
    $row[] = $label . '><strong>' . drupal_render($module['name']) . '</strong></label>';
    $row[] = drupal_render($module['version']);
    $description = '';
    // If we have help, it becomes the first part
    // of the description - with CSS, it is float: right'd.
    if (isset($module['help'])) {
      $description = '<div class="module-help">' . drupal_render($module['help']) . '</div>';
    }
    // Add the description, along with any modules it requires.
    $description .= drupal_render($module['description']);
    if ($module['#requires']) {
      $description .= '<div class="admin-requirements">' . t('Requires: !module-list', array('!module-list' => implode(', ', $module['#requires']))) . '</div>';
    }
    if ($module['#required_by']) {
      $description .= '<div class="admin-requirements">' . t('Required by: !module-list', array('!module-list' => implode(', ', $module['#required_by']))) . '</div>';
    }
    $row[] = array('data' => $description, 'class' => array('description'));
    $rows[] = $row;
  }

  return theme('table', $form['#header'], $rows);
}

/**
 * Themes an incompatible message.
 *
 * @ingroup themeable
 * @param $message
 *   The form array representing the currently disabled modules.
 * @return
 *   An HTML string for the message.
 */
function theme_system_modules_incompatible($message) {
  return '<div class="incompatible">' . $message . '</div>';
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
      '<strong><label for="' . $form['uninstall'][$module]['#id'] . '">' . drupal_render($form['modules'][$module]['name']) . '</label></strong>',
      array('data' => drupal_render($form['modules'][$module]['description']), 'class' => array('description')),
    );
  }

  // Only display table if there are modules that can be uninstalled.
  if (empty($rows)) {
    $rows[] = array(array('data' => t('No modules are available to uninstall.'), 'colspan' => '3', 'align' => 'center', 'class' => array('message')));
  }

  $output  = theme('table', $header, $rows);
  $output .= drupal_render_children($form);

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
      $description .= '<div class="incompatible">' . t('This version is incompatible with the !core_version version of Drupal core.', array('!core_version' => VERSION)) . '</div>';
    }
    elseif (isset($form['status']['#incompatible_themes_php'][$key])) {
      unset($form['status'][$key]);
      $status = theme('image', 'misc/watchdog-error.png', t('incompatible'), t('Incompatible with this version of PHP'));
      $php_required = $form['status']['#incompatible_themes_php'][$key];
      if (substr_count($php_required, '.') < 2) {
        $php_required .= '.*';
      }
      $description .= '<div class="incompatible">' . t('This theme requires PHP version @php_required and is incompatible with PHP version !php_version.', array('@php_required' => $php_required, '!php_version' => phpversion())) . '</div>';
    }
    else {
      $status = drupal_render($form['status'][$key]);
    }

    // Style theme info
    $theme = '<div class="theme-info"><h2>' . $info['name'] . '</h2><div class="description">' . $description . '</div></div>';

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
  $output .= drupal_render_children($form);
  return $output;
}

/**
 * Menu callback; Displays an overview of available and configured actions.
 */
function system_actions_manage() {
  actions_synchronize();
  $actions = actions_list();
  $actions_map = actions_actions_map($actions);
  $options = array(t('Choose an advanced action'));
  $unconfigurable = array();

  foreach ($actions_map as $key => $array) {
    if ($array['configurable']) {
      $options[$key] = $array['label'] . '...';
    }
    else {
      $unconfigurable[] = $array;
    }
  }

  $row = array();
  $instances_present = db_query("SELECT aid FROM {actions} WHERE parameters <> ''")->fetchField();
  $header = array(
    array('data' => t('Action type'), 'field' => 'type'),
    array('data' => t('Label'), 'field' => 'label'),
    array('data' => $instances_present ? t('Operations') : '', 'colspan' => '2')
  );
  $query = db_select('actions')->extend('PagerDefault')->extend('TableSort');
  $result = $query
    ->fields('actions')
    ->limit(50)
    ->orderByHeader($header)
    ->execute();

  foreach ($result as $action) {
    $row[] = array(
      array('data' => $action->type),
      array('data' => $action->label),
      array('data' => $action->parameters ? l(t('configure'), "admin/config/system/actions/configure/$action->aid") : ''),
      array('data' => $action->parameters ? l(t('delete'), "admin/config/system/actions/delete/$action->aid") : '')
    );
  }

  if ($row) {
    $pager = theme('pager', NULL);
    if (!empty($pager)) {
      $row[] = array(array('data' => $pager, 'colspan' => '3'));
    }
    $build['system_actions_header'] = array('#markup' => '<h3>' . t('Actions available to Drupal:') . '</h3>');
    $build['system_actions_table'] = array('#markup' => theme('table', $header, $row));
  }

  if ($actions_map) {
    $build['system_actions_manage_form'] = drupal_get_form('system_actions_manage_form', $options);
  }

  return $build;
}

/**
 * Define the form for the actions overview page.
 *
 * @param $form_state
 *   An associative array containing the current state of the form; not used.
 * @param $options
 *   An array of configurable actions.
 * @return
 *   Form definition.
 *
 * @ingroup forms
 * @see system_actions_manage_form_submit()
 */
function system_actions_manage_form($form, &$form_state, $options = array()) {
  $form['parent'] = array(
    '#type' => 'fieldset',
    '#title' => t('Make a new advanced action available'),
    '#prefix' => '<div class="container-inline">',
    '#suffix' => '</div>',
  );
  $form['parent']['action'] = array(
    '#type' => 'select',
    '#default_value' => '',
    '#options' => $options,
    '#description' => '',
  );
  $form['parent']['buttons']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Create'),
  );
  return $form;
}

/**
 * Process system_actions_manage form submissions.
 *
 * @see system_actions_manage_form()
 */
function system_actions_manage_form_submit($form, &$form_state) {
  if ($form_state['values']['action']) {
    $form_state['redirect'] = 'admin/config/system/actions/configure/' . $form_state['values']['action'];
  }
}

/**
 * Menu callback; Creates the form for configuration of a single action.
 *
 * We provide the "Description" field. The rest of the form is provided by the
 * action. We then provide the Save button. Because we are combining unknown
 * form elements with the action configuration form, we use an 'actions_' prefix
 * on our elements.
 *
 * @param $action
 *   md5 hash of an action ID or an integer. If it is an md5 hash, we are
 *   creating a new instance. If it is an integer, we are editing an existing
 *   instance.
 * @return
 *   A form definition.
 *
 * @see system_actions_configure_validate()
 * @see system_actions_configure_submit()
 */
function system_actions_configure($form, &$form_state, $action = NULL) {
  if ($action === NULL) {
    drupal_goto('admin/config/system/actions');
  }

  $actions_map = actions_actions_map(actions_list());
  $edit = array();

  // Numeric action denotes saved instance of a configurable action.
  if (is_numeric($action)) {
    $aid = $action;
    // Load stored parameter values from database.
    $data = db_query("SELECT * FROM {actions} WHERE aid = :aid", array(':aid' => $aid))->fetch();
    $edit['actions_label'] = $data->label;
    $edit['actions_type'] = $data->type;
    $function = $data->callback;
    $action = md5($data->callback);
    $params = unserialize($data->parameters);
    if ($params) {
      foreach ($params as $name => $val) {
        $edit[$name] = $val;
      }
    }
  }
  // Otherwise, we are creating a new action instance.
  else {
    $function = $actions_map[$action]['callback'];
    $edit['actions_label'] = $actions_map[$action]['label'];
    $edit['actions_type'] = $actions_map[$action]['type'];
  }

  $form['actions_label'] = array(
    '#type' => 'textfield',
    '#title' => t('Label'),
    '#default_value' => $edit['actions_label'],
    '#maxlength' => '255',
    '#description' => t('A unique label for this advanced action. This label will be displayed in the interface of modules that integrate with actions, such as Trigger module.'),
    '#weight' => -10
  );
  $action_form = $function . '_form';
  $form = array_merge($form, $action_form($edit));
  $form['actions_type'] = array(
    '#type' => 'value',
    '#value' => $edit['actions_type'],
  );
  $form['actions_action'] = array(
    '#type' => 'hidden',
    '#value' => $action,
  );
  // $aid is set when configuring an existing action instance.
  if (isset($aid)) {
    $form['actions_aid'] = array(
      '#type' => 'hidden',
      '#value' => $aid,
    );
  }
  $form['actions_configured'] = array(
    '#type' => 'hidden',
    '#value' => '1',
  );
  $form['buttons']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save'),
    '#weight' => 13
  );

  return $form;
}

/**
 * Validate system_actions_configure() form submissions.
 */
function system_actions_configure_validate($form, &$form_state) {
  $function = actions_function_lookup($form_state['values']['actions_action']) . '_validate';
  // Hand off validation to the action.
  if (function_exists($function)) {
    $function($form, $form_state);
  }
}

/**
 * Process system_actions_configure() form submissions.
 */
function system_actions_configure_submit($form, &$form_state) {
  $function = actions_function_lookup($form_state['values']['actions_action']);
  $submit_function = $function . '_submit';

  // Action will return keyed array of values to store.
  $params = $submit_function($form, $form_state);
  $aid = isset($form_state['values']['actions_aid']) ? $form_state['values']['actions_aid'] : NULL;

  actions_save($function, $form_state['values']['actions_type'], $params, $form_state['values']['actions_label'], $aid);
  drupal_set_message(t('The action has been successfully saved.'));

  $form_state['redirect'] = 'admin/config/system/actions/manage';
}

/**
 * Create the form for confirmation of deleting an action.
 *
 * @see system_actions_delete_form_submit()
 * @ingroup forms
 */
function system_actions_delete_form($form, &$form_state, $action) {
  $form['aid'] = array(
    '#type' => 'hidden',
    '#value' => $action->aid,
  );
  return confirm_form($form,
    t('Are you sure you want to delete the action %action?', array('%action' => $action->label)),
    'admin/config/system/actions/manage',
    t('This cannot be undone.'),
    t('Delete'),
    t('Cancel')
  );
}

/**
 * Process system_actions_delete form submissions.
 *
 * Post-deletion operations for action deletion.
 */
function system_actions_delete_form_submit($form, &$form_state) {
  $aid = $form_state['values']['aid'];
  $action = actions_load($aid);
  actions_delete($aid);
  $label = check_plain($action->label);
  watchdog('user', 'Deleted action %aid (%action)', array('%aid' => $aid, '%action' => $label));
  drupal_set_message(t('Action %action was deleted', array('%action' => $label)));
  $form_state['redirect'] = 'admin/config/system/actions/manage';
}

/**
 * Post-deletion operations for deleting action orphans.
 *
 * @param $orphaned
 *   An array of orphaned actions.
 */
function system_action_delete_orphans_post($orphaned) {
  foreach ($orphaned as $callback) {
    drupal_set_message(t("Deleted orphaned action (%action).", array('%action' => $callback)));
  }
}

/**
 * Remove actions that are in the database but not supported by any enabled module.
 */
function system_actions_remove_orphans() {
  actions_synchronize(TRUE);
  drupal_goto('admin/config/system/actions/manage');
}

