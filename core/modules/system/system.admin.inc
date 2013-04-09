<?php

/**
 * @file
 * Admin page callbacks for the system module.
 */

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Menu callback; Provide the administration overview page.
 */
function system_admin_config_page() {
  // Check for status report errors.
  if (system_status(TRUE) && user_access('administer site configuration')) {
    drupal_set_message(t('One or more problems were detected with your Drupal installation. Check the <a href="@status">status report</a> for more information.', array('@status' => url('admin/reports/status'))), 'error');
  }
  $blocks = array();
  if ($system_link = entity_load_multiple_by_properties('menu_link', array('link_path' => 'admin/config', 'module' => 'system'))) {
    $system_link = reset($system_link);
    $query = entity_query('menu_link')
      ->condition('link_path', 'admin/help', '<>')
      ->condition('menu_name', $system_link->menu_name)
      ->condition('plid', $system_link->id())
      ->condition('hidden', 0);
    $result = $query->execute();
    if (!empty($result)) {
      $menu_links = menu_link_load_multiple($result);

      foreach ($menu_links as $item) {
        _menu_link_translate($item);
        if (!$item['access']) {
          continue;
        }
        // The link description, either derived from 'description' in hook_menu()
        // or customized via menu module is used as title attribute.
        if (!empty($item['localized_options']['attributes']['title'])) {
          $item['description'] = $item['localized_options']['attributes']['title'];
          unset($item['localized_options']['attributes']['title']);
        }
        $block = $item;
        $block['content'] = '';
        $block['content'] .= theme('admin_block_content', array('content' => system_admin_menu_block($item)));
        if (!empty($block['content'])) {
          $block['show'] = TRUE;
        }

        // Prepare for sorting as in function _menu_tree_check_access().
        // The weight is offset so it is always positive, with a uniform 5-digits.
        $blocks[(50000 + $item['weight']) . ' ' . $item['title'] . ' ' . $item['mlid']] = $block;
      }
    }
  }
  if ($blocks) {
    ksort($blocks);
    return theme('admin_page', array('blocks' => $blocks));
  }
  else {
    return t('You do not have any administrative items.');
  }
}

/**
 * Provide a single block from the administration menu as a page.
 *
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
    $output = theme('admin_block_content', array('content' => $content));
  }
  else {
    $output = t('You do not have any administrative items.');
  }
  return $output;
}

/**
 * Menu callback; prints a listing of admin tasks, organized by module.
 */
function system_admin_index() {
  $module_info = system_get_info('module');
  foreach ($module_info as $module => $info) {
    $module_info[$module] = new stdClass();
    $module_info[$module]->info = $info;
  }
  uasort($module_info, 'system_sort_modules_by_info_name');
  $menu_items = array();

  foreach ($module_info as $module => $info) {
    // Only display a section if there are any available tasks.
    if ($admin_tasks = system_get_module_admin_tasks($module, $info->info)) {
      // Sort links by title.
      uasort($admin_tasks, 'drupal_sort_title');
      // Move 'Configure permissions' links to the bottom of each section.
      $permission_key = "admin/people/permissions#module-$module";
      if (isset($admin_tasks[$permission_key])) {
        $permission_task = $admin_tasks[$permission_key];
        unset($admin_tasks[$permission_key]);
        $admin_tasks[$permission_key] = $permission_task;
      }

      $menu_items[$info->info['name']] = array($info->info['description'], $admin_tasks);
    }
  }
  return theme('system_admin_index', array('menu_items' => $menu_items));
}

/**
 * Menu callback; displays a listing of all themes.
 */
function system_themes_page() {
  // Get current list of themes.
  $themes = system_rebuild_theme_data();
  uasort($themes, 'system_sort_modules_by_info_name');

  $theme_default = config('system.theme')->get('default');
  $theme_groups  = array();
  $admin_theme = config('system.theme')->get('admin');

  foreach ($themes as &$theme) {
    if (!empty($theme->info['hidden'])) {
      continue;
    }
    $theme->is_default = ($theme->name == $theme_default);

    // Identify theme screenshot.
    $theme->screenshot = NULL;
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
        $theme->screenshot = array(
          'uri' => $themes[$theme_key]->info['screenshot'],
          'alt' => t('Screenshot for !theme theme', array('!theme' => $theme->info['name'])),
          'title' => t('Screenshot for !theme theme', array('!theme' => $theme->info['name'])),
          'attributes' => array('class' => array('screenshot')),
        );
        break;
      }
    }

    if (empty($theme->status)) {
     // Ensure this theme is compatible with this version of core.
     // Require the 'content' region to make sure the main page
     // content has a common place in all themes.
      $theme->incompatible_core = !isset($theme->info['core']) || ($theme->info['core'] != DRUPAL_CORE_COMPATIBILITY) || (!isset($theme->info['regions']['content']));
      $theme->incompatible_php = version_compare(phpversion(), $theme->info['php']) < 0;
      // Confirmed that the base theme is available.
      $theme->incompatible_base = (isset($theme->info['base theme']) && !isset($themes[$theme->info['base theme']]));
      // Confirm that the theme engine is available.
      $theme->incompatible_engine = (isset($theme->info['engine']) && !isset($theme->owner));
    }
    $query['token'] = drupal_get_token('system-theme-operation-link');
    $theme->operations = array();
    if (!empty($theme->status) || !$theme->incompatible_core && !$theme->incompatible_php && !$theme->incompatible_base && !$theme->incompatible_engine) {
      // Create the operations links.
      $query['theme'] = $theme->name;
      if (drupal_theme_access($theme)) {
        $theme->operations[] = array(
          'title' => t('Settings'),
          'href' => 'admin/appearance/settings/' . $theme->name,
          'attributes' => array('title' => t('Settings for !theme theme', array('!theme' => $theme->info['name']))),
        );
      }
      if (!empty($theme->status)) {
        if (!$theme->is_default) {
          if ($theme->name != $admin_theme) {
            $theme->operations[] = array(
              'title' => t('Disable'),
              'href' => 'admin/appearance/disable',
              'query' => $query,
              'attributes' => array('title' => t('Disable !theme theme', array('!theme' => $theme->info['name']))),
            );
          }
          $theme->operations[] = array(
            'title' => t('Set default'),
            'href' => 'admin/appearance/default',
            'query' => $query,
            'attributes' => array('title' => t('Set !theme as default theme', array('!theme' => $theme->info['name']))),
          );
        }
        $admin_theme_options[$theme->name] = $theme->info['name'];
      }
      else {
        $theme->operations[] = array(
          'title' => t('Enable'),
          'href' => 'admin/appearance/enable',
          'query' => $query,
          'attributes' => array('title' => t('Enable !theme theme', array('!theme' => $theme->info['name']))),
        );
        $theme->operations[] = array(
          'title' => t('Enable and set default'),
          'href' => 'admin/appearance/default',
          'query' => $query,
          'attributes' => array('title' => t('Enable !theme as default theme', array('!theme' => $theme->info['name']))),
        );
      }
    }

    // Add notes to default and administration theme.
    $theme->notes = array();
    $theme->classes = array();
    if ($theme->is_default) {
      $theme->classes[] = 'theme-default';
      $theme->notes[] = t('default theme');
    }
    if ($theme->name == $admin_theme || ($theme->is_default && $admin_theme == '0')) {
      $theme->classes[] = 'theme-admin';
      $theme->notes[] = t('admin theme');
    }

    // Sort enabled and disabled themes into their own groups.
    $theme_groups[$theme->status ? 'enabled' : 'disabled'][] = $theme;
  }

  // There are two possible theme groups.
  $theme_group_titles = array(
    'enabled' => format_plural(count($theme_groups['enabled']), 'Enabled theme', 'Enabled themes'),
  );
  if (!empty($theme_groups['disabled'])) {
    $theme_group_titles['disabled'] = format_plural(count($theme_groups['disabled']), 'Disabled theme', 'Disabled themes');
  }

  uasort($theme_groups['enabled'], 'system_sort_themes');
  drupal_alter('system_themes_page', $theme_groups);

  $admin_form = drupal_get_form('system_themes_admin_form', $admin_theme_options);
  return theme('system_themes_page', array('theme_groups' => $theme_groups, 'theme_group_titles' => $theme_group_titles)) . drupal_render($admin_form);
}

/**
 * Form to select the administration theme.
 *
 * @ingroup forms
 * @see system_themes_admin_form_submit()
 */
function system_themes_admin_form($form, &$form_state, $theme_options) {
  // Administration theme settings.
  $form['admin_theme'] = array(
    '#type' => 'details',
    '#title' => t('Administration theme'),
  );
  $form['admin_theme']['admin_theme'] = array(
    '#type' => 'select',
    '#options' => array(0 => t('Default theme')) + $theme_options,
    '#title' => t('Administration theme'),
    '#description' => t('Choose "Default theme" to always use the same theme as the rest of the site.'),
    '#default_value' => config('system.theme')->get('admin'),
  );
  $form['admin_theme']['actions'] = array('#type' => 'actions');
  $form['admin_theme']['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save configuration'),
  );
  return $form;
}

/**
 * Process system_themes_admin_form form submissions.
 */
function system_themes_admin_form_submit($form, &$form_state) {
  drupal_set_message(t('The configuration options have been saved.'));
  config('system.theme')->set('admin', $form_state['values']['admin_theme'])->save();
}

/**
 * Menu callback; Enables a theme.
 */
function system_theme_enable() {
  if (isset($_REQUEST['theme']) && isset($_REQUEST['token']) && drupal_valid_token($_REQUEST['token'], 'system-theme-operation-link')) {
    $theme = $_REQUEST['theme'];
    // Get current list of themes.
    $themes = list_themes();

    // Check if the specified theme is one recognized by the system.
    if (!empty($themes[$theme])) {
      theme_enable(array($theme));
      drupal_set_message(t('The %theme theme has been enabled.', array('%theme' => $themes[$theme]->info['name'])));
    }
    else {
      drupal_set_message(t('The %theme theme was not found.', array('%theme' => $theme)), 'error');
    }
    drupal_goto('admin/appearance');
  }
  throw new AccessDeniedHttpException();
}

/**
 * Menu callback; Disables a theme.
 */
function system_theme_disable() {
  if (isset($_REQUEST['theme']) && isset($_REQUEST['token']) && drupal_valid_token($_REQUEST['token'], 'system-theme-operation-link')) {
    $theme = $_REQUEST['theme'];
    // Get current list of themes.
    $themes = list_themes();

    // Check if the specified theme is one recognized by the system.
    if (!empty($themes[$theme])) {
      // Do not disable the default or admin theme.
      if ($theme === config('system.theme')->get('default') || $theme === config('system.theme')->get('admin')) {
        drupal_set_message(t('%theme is the default theme and cannot be disabled.', array('%theme' => $themes[$theme]->info['name'])), 'error');
      }
      else {
        theme_disable(array($theme));
        drupal_set_message(t('The %theme theme has been disabled.', array('%theme' => $themes[$theme]->info['name'])));
      }
    }
    else {
      drupal_set_message(t('The %theme theme was not found.', array('%theme' => $theme)), 'error');
    }
    drupal_goto('admin/appearance');
  }
  throw new AccessDeniedHttpException();
}

/**
 * Menu callback; Set the default theme.
 */
function system_theme_default() {
  if (isset($_REQUEST['theme']) && isset($_REQUEST['token']) && drupal_valid_token($_REQUEST['token'], 'system-theme-operation-link')) {
    $theme = $_REQUEST['theme'];
    // Get current list of themes.
    $themes = list_themes();

    // Check if the specified theme is one recognized by the system.
    if (!empty($themes[$theme])) {
      // Enable the theme if it is currently disabled.
      if (empty($themes[$theme]->status)) {
       theme_enable(array($theme));
      }
      // Set the default theme.
      config('system.theme')
        ->set('default', $theme)
        ->save();

      // Rebuild the menu. This duplicates the menu_router_rebuild() in
      // theme_enable(). However, modules must know the current default theme in
      // order to use this information in hook_menu() or hook_menu_alter()
      // implementations, and doing the variable_set() before the theme_enable()
      // could result in a race condition where the theme is default but not
      // enabled.
      menu_router_rebuild();

      // The status message depends on whether an admin theme is currently in use:
      // a value of 0 means the admin theme is set to be the default theme.
      $admin_theme = config('system.theme')->get('admin');
      if ($admin_theme != 0 && $admin_theme != $theme) {
        drupal_set_message(t('Please note that the administration theme is still set to the %admin_theme theme; consequently, the theme on this page remains unchanged. All non-administrative sections of the site, however, will show the selected %selected_theme theme by default.', array(
          '%admin_theme' => $themes[$admin_theme]->info['name'],
          '%selected_theme' => $themes[$theme]->info['name'],
        )));
      }
      else {
        drupal_set_message(t('%theme is now the default theme.', array('%theme' => $themes[$theme]->info['name'])));
      }
    }
    else {
      drupal_set_message(t('The %theme theme was not found.', array('%theme' => $theme)), 'error');
    }
    drupal_goto('admin/appearance');
  }
  throw new AccessDeniedHttpException();
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
  // Default settings are defined in theme_get_setting() in includes/theme.inc
  if ($key) {
    $var = 'theme_' . $key . '_settings';
    $themes = list_themes();
    $features = $themes[$key]->info['features'];
  }
  else {
    $var = 'theme_settings';
  }

  $form['var'] = array('#type' => 'hidden', '#value' => $var);

  // Toggle settings
  $toggles = array(
    'logo'                      => t('Logo'),
    'name'                      => t('Site name'),
    'slogan'                    => t('Site slogan'),
    'node_user_picture'         => t('User pictures in posts'),
    'comment_user_picture'      => t('User pictures in comments'),
    'comment_user_verification' => t('User verification status in comments'),
    'favicon'                   => t('Shortcut icon'),
    'main_menu'                 => t('Main menu'),
    'secondary_menu'            => t('Secondary menu'),
  );

  // Some features are not always available
  $disabled = array();
  if (!user_picture_enabled()) {
    $disabled['toggle_node_user_picture'] = TRUE;
    $disabled['toggle_comment_user_picture'] = TRUE;
  }
  if (!module_exists('comment')) {
    $disabled['toggle_comment_user_picture'] = TRUE;
    $disabled['toggle_comment_user_verification'] = TRUE;
  }

  $form['theme_settings'] = array(
    '#type' => 'details',
    '#title' => t('Toggle display'),
    '#description' => t('Enable or disable the display of certain page elements.'),
  );
  foreach ($toggles as $name => $title) {
    if ((!$key) || in_array($name, $features)) {
      $form['theme_settings']['toggle_' . $name] = array('#type' => 'checkbox', '#title' => $title, '#default_value' => theme_get_setting('toggle_' . $name, $key));
      // Disable checkboxes for features not supported in the current configuration.
      if (isset($disabled['toggle_' . $name])) {
        $form['theme_settings']['toggle_' . $name]['#disabled'] = TRUE;
      }
    }
  }

  if (!element_children($form['theme_settings'])) {
    // If there is no element in the theme settings details then do not show
    // it -- but keep it in the form if another module wants to alter.
    $form['theme_settings']['#access'] = FALSE;
  }

  // Logo settings, only available when file.module is enabled.
  if ((!$key) || in_array('logo', $features) && module_exists('file')) {
    $form['logo'] = array(
      '#type' => 'details',
      '#title' => t('Logo image settings'),
      '#attributes' => array('class' => array('theme-settings-bottom')),
    );
    $form['logo']['default_logo'] = array(
      '#type' => 'checkbox',
      '#title' => t('Use the default logo supplied by the theme'),
      '#default_value' => theme_get_setting('default_logo', $key),
      '#tree' => FALSE,
    );
    $form['logo']['settings'] = array(
      '#type' => 'container',
      '#states' => array(
        // Hide the logo settings when using the default logo.
        'invisible' => array(
          'input[name="default_logo"]' => array('checked' => TRUE),
        ),
      ),
    );
    $form['logo']['settings']['logo_path'] = array(
      '#type' => 'textfield',
      '#title' => t('Path to custom logo'),
      '#default_value' => theme_get_setting('logo_path', $key),
    );
    $form['logo']['settings']['logo_upload'] = array(
      '#type' => 'file',
      '#title' => t('Upload logo image'),
      '#maxlength' => 40,
      '#description' => t("If you don't have direct file access to the server, use this field to upload your logo.")
    );
  }

  if ((!$key) || in_array('favicon', $features) && module_exists('file')) {
    $form['favicon'] = array(
      '#type' => 'details',
      '#title' => t('Shortcut icon settings'),
      '#description' => t("Your shortcut icon, or 'favicon', is displayed in the address bar and bookmarks of most browsers."),
    );
    $form['favicon']['default_favicon'] = array(
      '#type' => 'checkbox',
      '#title' => t('Use the default shortcut icon supplied by the theme'),
      '#default_value' => theme_get_setting('default_favicon', $key),
    );
    $form['favicon']['settings'] = array(
      '#type' => 'container',
      '#states' => array(
        // Hide the favicon settings when using the default favicon.
        'invisible' => array(
          'input[name="default_favicon"]' => array('checked' => TRUE),
        ),
      ),
    );
    $form['favicon']['settings']['favicon_path'] = array(
      '#type' => 'textfield',
      '#title' => t('Path to custom icon'),
      '#default_value' => theme_get_setting('favicon_path', $key),
    );
    $form['favicon']['settings']['favicon_upload'] = array(
      '#type' => 'file',
      '#title' => t('Upload icon image'),
      '#description' => t("If you don't have direct file access to the server, use this field to upload your shortcut icon.")
    );
  }

  // Inject human-friendly values and form element descriptions for logo and
  // favicon.
  foreach (array('logo' => 'logo.png', 'favicon' => 'favicon.ico') as $type => $default) {
    if (isset($form[$type]['settings'][$type . '_path'])) {
      $element = &$form[$type]['settings'][$type . '_path'];

      // If path is a public:// URI, display the path relative to the files
      // directory; stream wrappers are not end-user friendly.
      $original_path = $element['#default_value'];
      $friendly_path = NULL;
      if (file_uri_scheme($original_path) == 'public') {
        $friendly_path = file_uri_target($original_path);
        $element['#default_value'] = $friendly_path;
      }

      // Prepare local file path for description.
      if ($original_path && isset($friendly_path)) {
        $local_file = strtr($original_path, array('public:/' => variable_get('file_public_path', conf_path() . '/files')));
      }
      elseif ($key) {
        $local_file = drupal_get_path('theme', $key) . '/' . $default;
      }
      else {
        $local_file = path_to_theme() . '/' . $default;
      }

      $element['#description'] = t('Examples: <code>@implicit-public-file</code> (for a file in the public filesystem), <code>@explicit-file</code>, or <code>@local-file</code>.', array(
        '@implicit-public-file' => isset($friendly_path) ? $friendly_path : $default,
        '@explicit-file' => file_uri_scheme($original_path) !== FALSE ? $original_path : 'public://' . $default,
        '@local-file' => $local_file,
      ));
    }
  }

  if ($key) {
    // Call engine-specific settings.
    $function = $themes[$key]->prefix . '_engine_settings';
    if (function_exists($function)) {
      $form['engine_specific'] = array(
        '#type' => 'details',
        '#title' => t('Theme-engine-specific settings'),
        '#description' => t('These settings only exist for the themes based on the %engine theme engine.', array('%engine' => $themes[$key]->prefix)),
      );
      $function($form, $form_state);
    }

    // Create a list which includes the current theme and all its base themes.
    if (isset($themes[$key]->base_themes)) {
      $theme_keys = array_keys($themes[$key]->base_themes);
      $theme_keys[] = $key;
    }
    else {
      $theme_keys = array($key);
    }

    // Save the name of the current theme (if any), so that we can temporarily
    // override the current theme and allow theme_get_setting() to work
    // without having to pass the theme name to it.
    $default_theme = !empty($GLOBALS['theme_key']) ? $GLOBALS['theme_key'] : NULL;
    $GLOBALS['theme_key'] = $key;

    // Process the theme and all its base themes.
    foreach ($theme_keys as $theme) {
      // Include the theme-settings.php file.
      $filename = DRUPAL_ROOT . '/' . str_replace("/$theme.info.yml", '', $themes[$theme]->filename) . '/theme-settings.php';
      if (file_exists($filename)) {
        require_once $filename;
      }

      // Call theme-specific settings.
      $function = $theme . '_form_system_theme_settings_alter';
      if (function_exists($function)) {
        $function($form, $form_state);
      }
    }

    // Restore the original current theme.
    if (isset($default_theme)) {
      $GLOBALS['theme_key'] = $default_theme;
    }
    else {
      unset($GLOBALS['theme_key']);
    }
  }

  $form = system_settings_form($form);
  // We don't want to call system_settings_form_submit(), so change #submit.
  array_pop($form['#submit']);
  $form['#submit'][] = 'system_theme_settings_submit';
  $form['#validate'][] = 'system_theme_settings_validate';
  return $form;
}

/**
 * Validator for the system_theme_settings() form.
 */
function system_theme_settings_validate($form, &$form_state) {
  if (module_exists('file')) {
    // Handle file uploads.
    $validators = array('file_validate_is_image' => array());

    // Check for a new uploaded logo.
    $file = file_save_upload('logo_upload', $validators);
    if (isset($file)) {
      // File upload was attempted.
      if ($file) {
        // Put the temporary file in form_values so we can save it on submit.
        $form_state['values']['logo_upload'] = $file;
      }
      else {
        // File upload failed.
        form_set_error('logo_upload', t('The logo could not be uploaded.'));
      }
    }

    $validators = array('file_validate_extensions' => array('ico png gif jpg jpeg apng svg'));

    // Check for a new uploaded favicon.
    $file = file_save_upload('favicon_upload', $validators);
    if (isset($file)) {
      // File upload was attempted.
      if ($file) {
        // Put the temporary file in form_values so we can save it on submit.
        $form_state['values']['favicon_upload'] = $file;
      }
      else {
        // File upload failed.
        form_set_error('favicon_upload', t('The favicon could not be uploaded.'));
      }
    }

    // If the user provided a path for a logo or favicon file, make sure a file
    // exists at that path.
    if ($form_state['values']['logo_path']) {
      $path = _system_theme_settings_validate_path($form_state['values']['logo_path']);
      if (!$path) {
        form_set_error('logo_path', t('The custom logo path is invalid.'));
      }
    }
    if ($form_state['values']['favicon_path']) {
      $path = _system_theme_settings_validate_path($form_state['values']['favicon_path']);
      if (!$path) {
        form_set_error('favicon_path', t('The custom favicon path is invalid.'));
      }
    }
  }
}

/**
 * Helper function for the system_theme_settings form.
 *
 * Attempts to validate normal system paths, paths relative to the public files
 * directory, or stream wrapper URIs. If the given path is any of the above,
 * returns a valid path or URI that the theme system can display.
 *
 * @param $path
 *   A path relative to the Drupal root or to the public files directory, or
 *   a stream wrapper URI.
 * @return mixed
 *   A valid path that can be displayed through the theme system, or FALSE if
 *   the path could not be validated.
 */
function _system_theme_settings_validate_path($path) {
  // Absolute local file paths are invalid.
  if (drupal_realpath($path) == $path) {
    return FALSE;
  }
  // A path relative to the Drupal root or a fully qualified URI is valid.
  if (is_file($path)) {
    return $path;
  }
  // Prepend 'public://' for relative file paths within public filesystem.
  if (file_uri_scheme($path) === FALSE) {
    $path = 'public://' . $path;
  }
  if (is_file($path)) {
    return $path;
  }
  return FALSE;
}

/**
 * Process system_theme_settings form submissions.
 */
function system_theme_settings_submit($form, &$form_state) {
  // Exclude unnecessary elements before saving.
  form_state_values_clean($form_state);
  $values = $form_state['values'];

  // Extract the name of the theme from the submitted form values, then
  // remove it from the array so that it is not saved as part of the variable.
  $key = $values['var'];
  unset($values['var']);

  // If the user uploaded a new logo or favicon, save it to a permanent location
  // and use it in place of the default theme-provided file.
  if (module_exists('file')) {
    if ($file = $values['logo_upload']) {
      unset($values['logo_upload']);
      $filename = file_unmanaged_copy($file->uri);
      $values['default_logo'] = 0;
      $values['logo_path'] = $filename;
      $values['toggle_logo'] = 1;
    }
    if ($file = $values['favicon_upload']) {
      unset($values['favicon_upload']);
      $filename = file_unmanaged_copy($file->uri);
      $values['default_favicon'] = 0;
      $values['favicon_path'] = $filename;
      $values['toggle_favicon'] = 1;
    }

    // If the user entered a path relative to the system files directory for
    // a logo or favicon, store a public:// URI so the theme system can handle it.
    if (!empty($values['logo_path'])) {
      $values['logo_path'] = _system_theme_settings_validate_path($values['logo_path']);
    }
    if (!empty($values['favicon_path'])) {
      $values['favicon_path'] = _system_theme_settings_validate_path($values['favicon_path']);
    }

    if (empty($values['default_favicon']) && !empty($values['favicon_path'])) {
      $values['favicon_mimetype'] = file_get_mimetype($values['favicon_path']);
    }
  }

  variable_set($key, $values);
  drupal_set_message(t('The configuration options have been saved.'));

  cache_invalidate_tags(array('content' => TRUE));
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
 * The list of modules gets populated by module.info.yml files, which contain
 * each module's name, description, and information about which modules it
 * requires.
 * See drupal_parse_info_file() for information on module.info.yml descriptors.
 *
 * Dependency checking is performed to ensure that a module:
 * - can not be enabled if there are disabled modules it requires.
 * - can not be disabled if there are enabled modules which depend on it.
 *
 * @param $form_state
 *   An associative array containing the current state of the form.
 *
 * @return
 *   The form array.
 *
 * @ingroup forms
 * @see theme_system_modules()
 * @see system_modules_submit()
 */
function system_modules($form, $form_state = array()) {
  // Get current list of modules.
  $files = system_rebuild_module_data();

  // Remove hidden modules from display list.
  $visible_files = $files;
  foreach ($visible_files as $filename => $file) {
    if (!empty($file->info['hidden'])) {
      unset($visible_files[$filename]);
    }
  }

  uasort($visible_files, 'system_sort_modules_by_info_name');

  // If the modules form was submitted, then system_modules_submit() runs first
  // and if there are unfilled required modules, then $form_state['storage'] is
  // filled, triggering a rebuild. In this case we need to display a
  // confirmation form.
  if (!empty($form_state['storage'])) {
    return system_modules_confirm_form($visible_files, $form_state['storage']);
  }

  // JS-only table filters.
  $form['filters'] = array(
    '#type' => 'container',
    '#attributes' => array(
      'class' => array('table-filter', 'js-show'),
    ),
  );
  $form['filters']['text'] = array(
    '#type' => 'search',
    '#title' => t('Search'),
    '#size' => 30,
    '#placeholder' => t('Enter module name…'),
    '#attributes' => array(
      'class' => array('table-filter-text'),
      'data-table' => '#system-modules',
      'autocomplete' => 'off',
      'title' => t('Enter a part of the module name or description to filter by.'),
    ),
  );

  $modules = array();
  $form['modules'] = array('#tree' => TRUE);

  // Used when checking if module implements a help page.
  $help_arg = module_exists('help') ? drupal_help_arg() : FALSE;

  // Used when displaying modules that are required by the installation profile.
  require_once DRUPAL_ROOT . '/core/includes/install.inc';
  $distribution_name = check_plain(drupal_install_profile_distribution_name());

  // Iterate through each of the modules.
  foreach ($visible_files as $filename => $module) {
    $extra = array();
    $extra['enabled'] = (bool) $module->status;
    if (!empty($module->info['required'] )) {
      $extra['disabled'] = TRUE;
      $extra['required_by'][] = $distribution_name . (!empty($module->info['explanation']) ? ' ('. $module->info['explanation'] .')' : '');
    }

    // If this module requires other modules, add them to the array.
    foreach ($module->requires as $requires => $v) {
      if (!isset($files[$requires])) {
        $extra['requires'][$requires] = t('@module (<span class="admin-missing">missing</span>)', array('@module' => drupal_ucfirst($requires)));
        $extra['disabled'] = TRUE;
      }
      // Only display visible modules.
      elseif (isset($visible_files[$requires])) {
        $requires_name = $files[$requires]->info['name'];
        // Disable this module if it is incompatible with the dependency's version.
        if ($incompatible_version = drupal_check_incompatibility($v, str_replace(DRUPAL_CORE_COMPATIBILITY . '-', '', $files[$requires]->info['version']))) {
          $extra['requires'][$requires] = t('@module (<span class="admin-missing">incompatible with</span> version @version)', array(
            '@module' => $requires_name . $incompatible_version,
            '@version' => $files[$requires]->info['version'],
          ));
          $extra['disabled'] = TRUE;
        }
        // Disable this module if the dependency is incompatible with this
        // version of Drupal core.
        elseif ($files[$requires]->info['core'] != DRUPAL_CORE_COMPATIBILITY) {
          $extra['requires'][$requires] = t('@module (<span class="admin-missing">incompatible with</span> this version of Drupal core)', array(
            '@module' => $requires_name,
          ));
          $extra['disabled'] = TRUE;
        }
        elseif ($files[$requires]->status) {
          $extra['requires'][$requires] = t('@module', array('@module' => $requires_name));
        }
        else {
          $extra['requires'][$requires] = t('@module (<span class="admin-disabled">disabled</span>)', array('@module' => $requires_name));
        }
      }
    }
    // Generate link for module's help page, if there is one.
    if ($help_arg && $module->status && in_array($filename, module_implements('help'))) {
      if (module_invoke($filename, 'help', "admin/help#$filename", $help_arg)) {
        $extra['links']['help'] = array(
          '#type' => 'link',
          '#title' => t('Help'),
          '#href' => "admin/help/$filename",
          '#options' => array('attributes' => array('class' =>  array('module-link', 'module-link-help'), 'title' => t('Help'))),
        );
      }
    }
    // Generate link for module's permission, if the user has access to it.
    if ($module->status && user_access('administer permissions') && in_array($filename, module_implements('permission'))) {
      $extra['links']['permissions'] = array(
        '#type' => 'link',
        '#title' => t('Permissions'),
        '#href' => 'admin/people/permissions',
        '#options' => array('fragment' => 'module-' . $filename, 'attributes' => array('class' => array('module-link', 'module-link-permissions'), 'title' => t('Configure permissions'))),
      );
    }
    // Generate link for module's configuration page, if the module provides
    // one.
    if ($module->status && isset($module->info['configure'])) {
      $configure_link = menu_get_item($module->info['configure']);
      if ($configure_link['access']) {
        $extra['links']['configure'] = array(
          '#type' => 'link',
          '#title' => t('Configure'),
          '#href' => $configure_link['href'],
          '#options' => array('attributes' => array('class' => array('module-link', 'module-link-configure'), 'title' => $configure_link['description'])),
        );
      }
    }

    // If this module is required by other modules, list those, and then make it
    // impossible to disable this one.
    foreach ($module->required_by as $required_by => $v) {
      // Hidden modules are unset already.
      if (isset($visible_files[$required_by])) {
        if ($files[$required_by]->status == 1 && $module->status == 1) {
          $extra['required_by'][] = t('@module', array('@module' => $files[$required_by]->info['name']));
          $extra['disabled'] = TRUE;
        }
        else {
          $extra['required_by'][] = t('@module (<span class="admin-disabled">disabled</span>)', array('@module' => $files[$required_by]->info['name']));
        }
      }
    }
    $form['modules'][$module->info['package']][$filename] = _system_modules_build_row($module->info, $extra);
  }

  // Add basic information to the details.
  foreach (element_children($form['modules']) as $package) {
    $form['modules'][$package] += array(
      '#type' => 'details',
      '#title' => t($package),
      '#theme' => 'system_modules_details',
      '#header' => array(
        array('data' => t('<span class="element-invisible">Enabled</span>'), 'class' => array('checkbox')),
        array('data' => t('Name'), 'class' => array('name')),
        array('data' => t('Description'), 'class' => array('description', RESPONSIVE_PRIORITY_LOW)),
      ),
      // Ensure that the "Core" package comes first.
      '#weight' => $package == 'Core' ? -10 : NULL,
    );
  }

  // Lastly, sort all packages by title.
  uasort($form['modules'], 'element_sort_by_title');

  $form['#attached']['library'][] = array('system', 'drupal.system.modules');
  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save configuration'),
  );
  $form['#action'] = url('admin/modules/list/confirm');

  return $form;
}

/**
 * Array sorting callback; sorts modules or themes by their name.
 */
function system_sort_modules_by_info_name($a, $b) {
  return strcasecmp($a->info['name'], $b->info['name']);
}

/**
 * Array sorting callback; sorts modules or themes by their name.
 */
function system_sort_themes($a, $b) {
  if ($a->is_default) {
    return -1;
  }
  if ($b->is_default) {
    return 1;
  }
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
    'links' => array(),
  );
  $form = array(
    '#tree' => TRUE,
  );
  // Set the basic properties.
  $form['name'] = array(
    '#markup' => $info['name'],
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
  if (!isset($info['core']) || $info['core'] != DRUPAL_CORE_COMPATIBILITY) {
    $compatible = FALSE;
    $status_short .= t('Incompatible with this version of Drupal core.');
    $status_long .= t('This version is not compatible with Drupal !core_version and should be replaced.', array('!core_version' => DRUPAL_CORE_COMPATIBILITY));
  }

  // Ensure this module is compatible with the currently installed version of PHP.
  if (version_compare(phpversion(), $info['php']) < 0) {
    $compatible = FALSE;
    $status_short .= t('Incompatible with this version of PHP');
    $php_required = $info['php'];
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
      '#attributes' => array('role' => 'disabled'),
    );
    if ($extra['disabled']) {
      $form['enable']['#disabled'] = TRUE;
    }
  }
  else {
    $form['enable'] = array(
      '#markup' =>  theme('image', array('uri' => 'core/misc/watchdog-error.png', 'alt' => $status_short, 'title' => $status_short)),
    );
    $form['description']['#markup'] .= theme('system_modules_incompatible', array('message' => $status_long));
  }

  // Build operation links.
  foreach (array('help', 'permissions', 'configure') as $key) {
    $form['links'][$key] = (isset($extra['links'][$key]) ? $extra['links'][$key] : array());
  }

  return $form;
}

/**
 * Display confirmation form for required modules.
 *
 * @param $modules
 *   Array of module file objects as returned from system_rebuild_module_data().
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

  foreach ($storage['more_required'] as $info) {
    $t_argument = array(
      '@module' => $info['name'],
      '@required' => implode(', ', $info['requires']),
    );
    $items[] = format_plural(count($info['requires']), 'You must enable the @required module to install @module.', 'You must enable the @required modules to install @module.', $t_argument);
  }

  foreach ($storage['missing_modules'] as $name => $info) {
    $t_argument = array(
      '@module' => $name,
      '@depends' => implode(', ', $info['depends']),
    );
    $items[] = format_plural(count($info['depends']), 'The @module module is missing, so the following module will be disabled: @depends.', 'The @module module is missing, so the following modules will be disabled: @depends.', $t_argument);
  }

  $form['text'] = array('#markup' => theme('item_list', array('items' => $items)));

    // Set some default form values
  $form = confirm_form(
    $form,
    t('Some required modules must be enabled'),
    'admin/modules',
    t('Would you like to continue with the above?'),
    t('Continue'),
    t('Cancel'));

  return $form;
}

/**
 * Submit callback; handles modules form submission.
 */
function system_modules_submit($form, &$form_state) {
  include_once DRUPAL_ROOT . '/core/includes/install.inc';

  // Builds list of modules.
  $modules = array();
  // If we're not coming from the confirmation form, build the list of modules.
  if (empty($form_state['storage'])) {
    // If we're not coming from the confirmation form, build the module list.
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

  // Collect data for all modules to be able to determine dependencies.
  $files = system_rebuild_module_data();

  // Sorts modules by weight.
  $sort = array();
  foreach (array_keys($modules) as $module) {
    $sort[$module] = $files[$module]->sort;
  }
  array_multisort($sort, $modules);

  // Makes sure all required modules are set to be enabled.
  $more_required = array();
  $missing_modules = array();
  foreach ($modules as $name => $module) {
    if ($module['enabled']) {
      // Checks that all dependencies are set to be enabled.  Stores the ones
      // that are not in $dependencies variable so that the user can be alerted
      // in the confirmation form that more modules need to be enabled.
      $dependencies = array();
      foreach (array_keys($files[$name]->requires) as $required) {
        if (empty($modules[$required]['enabled'])) {
          if (isset($files[$required])) {
            $dependencies[] = $files[$required]->info['name'];
            $modules[$required]['enabled'] = TRUE;
          }
          else {
            $missing_modules[$required]['depends'][] = $name;
            $modules[$name]['enabled'] = FALSE;
          }
        }
      }

      // Stores additional modules that need to be enabled in $more_required.
      if (!empty($dependencies)) {
        $more_required[$name] = array(
          'name' => $files[$name]->info['name'],
          'requires' => $dependencies,
        );
      }
    }
  }

  // Redirects to confirmation form if more modules need to be enabled.
  if ((!empty($more_required) || !empty($missing_modules)) && !isset($form_state['values']['confirm'])) {
    $form_state['storage'] = array(
      'more_required' => $more_required,
      'modules' => $modules,
      'missing_modules' => $missing_modules,
    );
    $form_state['rebuild'] = TRUE;
    return;
  }

  // Invokes hook_requirements('install').  If failures are detected, makes sure
  // the dependent modules aren't installed either.
  foreach ($modules as $name => $module) {
    // Only invoke hook_requirements() on modules that are going to be installed.
    if ($module['enabled'] && drupal_get_installed_schema_version($name) == SCHEMA_UNINSTALLED) {
      if (!drupal_check_module($name)) {
        $modules[$name]['enabled'] = FALSE;
        foreach (array_keys($files[$name]->required_by) as $required_by) {
          $modules[$required_by]['enabled'] = FALSE;
        }
      }
    }
  }

  // Initializes array of actions.
  $actions = array(
    'enable' => array(),
    'disable' => array(),
    'install' => array(),
  );

  // Builds arrays of modules that need to be enabled, disabled, and installed.
  foreach ($modules as $name => $module) {
    if ($module['enabled']) {
      if (drupal_get_installed_schema_version($name) == SCHEMA_UNINSTALLED) {
        $actions['install'][] = $name;
        $actions['enable'][] = $name;
      }
      elseif (!module_exists($name)) {
        $actions['enable'][] = $name;
      }
    }
    elseif (module_exists($name)) {
      $actions['disable'][] = $name;
    }
  }

  // Gets list of modules prior to install process, unsets $form_state['storage']
  // so we don't get redirected back to the confirmation form.
  $pre_install_list = drupal_container()->get('module_handler')->getModuleList();
  unset($form_state['storage']);

  // Reverse the 'enable' list, to order dependencies before dependents.
  krsort($actions['enable']);

  // Installs, enables, and disables modules.
  module_enable($actions['enable'], FALSE);
  module_disable($actions['disable'], FALSE);

  // Gets module list after install process, flushes caches and displays a
  // message if there are changes.
  $post_install_list = drupal_container()->get('module_handler')->getModuleList();
  if ($pre_install_list != $post_install_list) {
    drupal_flush_all_caches();
    drupal_set_message(t('The configuration options have been saved.'));
  }

  $form_state['redirect'] = 'admin/modules';
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
  include_once DRUPAL_ROOT . '/core/includes/install.inc';

  // Display the confirm form if any modules have been submitted.
  if (!empty($form_state['storage']) && $confirm_form = system_modules_uninstall_confirm_form($form_state['storage'])) {
    return $confirm_form;
  }

  // Get a list of disabled, installed modules.
  $all_modules = system_rebuild_module_data();
  $disabled_modules = array();
  foreach ($all_modules as $name => $module) {
    if (empty($module->status) && drupal_get_installed_schema_version($name) > SCHEMA_UNINSTALLED) {
      $disabled_modules[$name] = $module;
    }
  }

  // Only build the rest of the form if there are any modules available to
  // uninstall.
  if (!empty($disabled_modules)) {
    $profile = drupal_get_profile();
    uasort($disabled_modules, 'system_sort_modules_by_info_name');
    $form['uninstall'] = array('#tree' => TRUE);
    foreach ($disabled_modules as $module) {
      $module_name = $module->info['name'] ? $module->info['name'] : $module->name;
      $form['modules'][$module->name]['#module_name'] = $module_name;
      $form['modules'][$module->name]['name']['#markup'] = $module_name;
      $form['modules'][$module->name]['description']['#markup'] = t($module->info['description']);
      $form['uninstall'][$module->name] = array(
        '#type' => 'checkbox',
        '#title' => t('Uninstall @module module', array('@module' => $module_name)),
        '#title_display' => 'invisible',
      );
      // All modules which depend on this one must be uninstalled first, before
      // we can allow this module to be uninstalled. (The installation profile
      // is excluded from this list.)
      foreach (array_keys($module->required_by) as $dependent) {
        if ($dependent != $profile && drupal_get_installed_schema_version($dependent) != SCHEMA_UNINSTALLED) {
          $dependent_name = isset($all_modules[$dependent]->info['name']) ? $all_modules[$dependent]->info['name'] : $dependent;
          $form['modules'][$module->name]['#required_by'][] = $dependent_name;
          $form['uninstall'][$module->name]['#disabled'] = TRUE;
        }
      }
    }
    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Uninstall'),
    );
    $form['#action'] = url('admin/modules/uninstall/confirm');
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
  if (empty($storage)) {
    return;
  }

  // Construct the hidden form elements and list items.
  foreach (array_filter($storage['uninstall']) as $module => $value) {
    $info = drupal_parse_info_file(drupal_get_path('module', $module) . '/' . $module . '.info.yml');
    $uninstall[] = $info['name'];
    $form['uninstall'][$module] = array('#type' => 'hidden',
      '#value' => 1,
    );
  }

  // Display a confirm form if modules have been selected.
  if (isset($uninstall)) {
    $form['#confirmed'] = TRUE;
    $form['uninstall']['#tree'] = TRUE;
    $form['modules'] = array('#markup' => '<p>' . t('The following modules will be completely uninstalled from your site, and <em>all data from these modules will be lost</em>!') . '</p>' . theme('item_list', array('items' => $uninstall)));
    $form = confirm_form(
      $form,
      t('Confirm uninstall'),
      'admin/modules/uninstall',
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
    drupal_goto('admin/modules/uninstall');
  }
}

/**
 * Processes the submitted uninstall form.
 */
function system_modules_uninstall_submit($form, &$form_state) {
  // Make sure the install API is available.
  include_once DRUPAL_ROOT . '/core/includes/install.inc';

  if (!empty($form['#confirmed'])) {
    // Call the uninstall routine for each selected module.
    $modules = array_keys($form_state['values']['uninstall']);
    module_uninstall($modules);
    drupal_set_message(t('The selected modules have been uninstalled.'));

    $form_state['redirect'] = 'admin/modules/uninstall';
  }
  else {
    $form_state['storage'] = $form_state['values'];
    $form_state['rebuild'] = TRUE;
  }
}

/**
 * Form builder; The general site information form.
 *
 * @ingroup forms
 * @see system_settings_form()
 */
function system_site_information_settings($form, &$form_state) {
  config_context_enter('config.context.free');
  $site_config = config('system.site');
  $site_mail = $site_config->get('mail');
  if (empty($site_mail)) {
    $site_mail = ini_get('sendmail_from');
  }

  $form['site_information'] = array(
    '#type' => 'details',
    '#title' => t('Site details'),
  );
  $form['site_information']['site_name'] = array(
    '#type' => 'textfield',
    '#title' => t('Site name'),
    '#default_value' => $site_config->get('name'),
    '#required' => TRUE
  );
  $form['site_information']['site_slogan'] = array(
    '#type' => 'textfield',
    '#title' => t('Slogan'),
    '#default_value' => $site_config->get('slogan'),
    '#description' => t("How this is used depends on your site's theme."),
  );
  $form['site_information']['site_mail'] = array(
    '#type' => 'email',
    '#title' => t('E-mail address'),
    '#default_value' => $site_mail,
    '#description' => t("The <em>From</em> address in automated e-mails sent during registration and new password requests, and other notifications. (Use an address ending in your site's domain to help prevent this e-mail being flagged as spam.)"),
    '#required' => TRUE,
  );
  $form['front_page'] = array(
    '#type' => 'details',
    '#title' => t('Front page'),
  );
  $front_page = $site_config->get('page.front') != 'user' ? drupal_container()->get('path.alias_manager')->getPathAlias($site_config->get('page.front')) : '';
  $form['front_page']['site_frontpage'] = array(
    '#type' => 'textfield',
    '#title' => t('Default front page'),
    '#default_value' => $front_page,
    '#size' => 40,
    '#description' => t('Optionally, specify a relative URL to display as the front page. Leave blank to display the default front page.'),
    '#field_prefix' => url(NULL, array('absolute' => TRUE)),
  );
  $form['error_page'] = array(
    '#type' => 'details',
    '#title' => t('Error pages'),
  );
  $form['error_page']['site_403'] = array(
    '#type' => 'textfield',
    '#title' => t('Default 403 (access denied) page'),
    '#default_value' => $site_config->get('page.403'),
    '#size' => 40,
    '#description' => t('This page is displayed when the requested document is denied to the current user. Leave blank to display a generic "access denied" page.'),
    '#field_prefix' => url(NULL, array('absolute' => TRUE)),
  );
  $form['error_page']['site_404'] = array(
    '#type' => 'textfield',
    '#title' => t('Default 404 (not found) page'),
    '#default_value' => $site_config->get('page.404'),
    '#size' => 40,
    '#description' => t('This page is displayed when no other content matches the requested document. Leave blank to display a generic "page not found" page.'),
    '#field_prefix' => url(NULL, array('absolute' => TRUE)),
  );

  $form['#validate'][] = 'system_site_information_settings_validate';

  return system_config_form($form, $form_state);
}

/**
 * Validates the submitted site-information form.
 */
function system_site_information_settings_validate($form, &$form_state) {
  // Check for empty front page path.
  if (empty($form_state['values']['site_frontpage'])) {
    // Set to default "user".
    form_set_value($form['front_page']['site_frontpage'], 'user', $form_state);
  }
  else {
    // Get the normal path of the front page.
    form_set_value($form['front_page']['site_frontpage'], drupal_container()->get('path.alias_manager')->getSystemPath($form_state['values']['site_frontpage']), $form_state);
  }
  // Validate front page path.
  if (!drupal_valid_path($form_state['values']['site_frontpage'])) {
    form_set_error('site_frontpage', t("The path '%path' is either invalid or you do not have access to it.", array('%path' => $form_state['values']['site_frontpage'])));
  }
  // Get the normal paths of both error pages.
  if (!empty($form_state['values']['site_403'])) {
    form_set_value($form['error_page']['site_403'], drupal_container()->get('path.alias_manager')->getSystemPath($form_state['values']['site_403']), $form_state);
  }
  if (!empty($form_state['values']['site_404'])) {
    form_set_value($form['error_page']['site_404'], drupal_container()->get('path.alias_manager')->getSystemPath($form_state['values']['site_404']), $form_state);
  }
  // Validate 403 error path.
  if (!empty($form_state['values']['site_403']) && !drupal_valid_path($form_state['values']['site_403'])) {
    form_set_error('site_403', t("The path '%path' is either invalid or you do not have access to it.", array('%path' => $form_state['values']['site_403'])));
  }
  // Validate 404 error path.
  if (!empty($form_state['values']['site_404']) && !drupal_valid_path($form_state['values']['site_404'])) {
    form_set_error('site_404', t("The path '%path' is either invalid or you do not have access to it.", array('%path' => $form_state['values']['site_404'])));
  }
}

/**
 * Form submission handler for system_site_information_settings().
 */
function system_site_information_settings_submit($form, &$form_state) {
  config_context_enter('config.context.free');
  config('system.site')
    ->set('name', $form_state['values']['site_name'])
    ->set('mail', $form_state['values']['site_mail'])
    ->set('slogan', $form_state['values']['site_slogan'])
    ->set('page.front', $form_state['values']['site_frontpage'])
    ->set('page.403', $form_state['values']['site_403'])
    ->set('page.404', $form_state['values']['site_404'])
    ->save();
}

/**
 * Form builder; Cron form.
 *
 * @ingroup forms
 */
function system_cron_settings($form, &$form_state) {
  config_context_enter('config.context.free');
  $form['description'] = array(
    '#markup' => '<p>' . t('Cron takes care of running periodic tasks like checking for updates and indexing content for search.') . '</p>',
  );
  $form['run'] = array(
    '#type' => 'submit',
    '#value' => t('Run cron'),
    '#submit' => array('system_run_cron_submit'),
  );

  $status = '<p>' . t('Last run: %cron-last ago.', array('%cron-last' => format_interval(REQUEST_TIME - state()->get('system.cron_last')))) . '</p>';
  $form['status'] = array(
    '#markup' => $status,
  );

  $form['cron_url'] = array(
    '#markup' => '<p>' . t('To run cron from outside the site, go to <a href="!cron">!cron</a>', array('!cron' => url('cron/' . state()->get('system.cron_key'), array('absolute' => TRUE)))) . '</p>',
  );

  $form['cron'] = array(
    '#type' => 'details',
  );
  $form['cron']['cron_safe_threshold'] = array(
    '#type' => 'select',
    '#title' => t('Run cron every'),
    '#default_value' => config('system.cron')->get('threshold.autorun'),
    '#options' => array(0 => t('Never')) + drupal_map_assoc(array(3600, 10800, 21600, 43200, 86400, 604800), 'format_interval'),
  );

  return system_config_form($form, $form_state);
}

/**
 * Form builder submit handler; Handle submission for cron settings.
 *
 * @ingroup forms
 */
function system_cron_settings_submit($form, &$form_state) {
  config_context_enter('config.context.free');
  config('system.cron')
    ->set('threshold.autorun', $form_state['values']['cron_safe_threshold'])
    ->save();
}

/**
 * Submit callback; run cron.
 *
 * @ingroup forms
 */
function system_run_cron_submit($form, &$form_state) {
  // Run cron manually from Cron form.
  if (drupal_cron_run()) {
    drupal_set_message(t('Cron run successfully.'));
  }
  else {
    drupal_set_message(t('Cron run failed.'), 'error');
  }

  drupal_goto('admin/config/system/cron');
}

/**
 * Form builder; Configure error reporting settings.
 *
 * @ingroup forms
 * @see system_logging_settings_submit()
 */
function system_logging_settings($form, &$form_state) {
  config_context_enter('config.context.free');
  $form['error_level'] = array(
    '#type' => 'radios',
    '#title' => t('Error messages to display'),
    '#default_value' => config('system.logging')->get('error_level'),
    '#options' => array(
      ERROR_REPORTING_HIDE => t('None'),
      ERROR_REPORTING_DISPLAY_SOME => t('Errors and warnings'),
      ERROR_REPORTING_DISPLAY_ALL => t('All messages'),
      ERROR_REPORTING_DISPLAY_VERBOSE => t('All messages, with backtrace information'),
    ),
    '#description' => t('It is recommended that sites running on production environments do not display any errors.'),
  );

  return system_config_form($form, $form_state);
}

/**
 * Form submission handler for system_logging_settings().
 *
 * @ingroup forms
 */
function system_logging_settings_submit($form, &$form_state) {
  config_context_enter('config.context.free');
  config('system.logging')
    ->set('error_level', $form_state['values']['error_level'])
    ->save();
}

/**
 * Form builder; Configure site performance settings.
 *
 * @ingroup forms
 * @see system_performance_settings_submit().
 */
function system_performance_settings($form, &$form_state) {
  drupal_add_library('system', 'drupal.system');
  config_context_enter('config.context.free');
  $config = config('system.performance');

  $form['clear_cache'] = array(
    '#type' => 'details',
    '#title' => t('Clear cache'),
  );

  $form['clear_cache']['clear'] = array(
    '#type' => 'submit',
    '#value' => t('Clear all caches'),
    '#submit' => array('system_clear_cache_submit'),
  );

  $form['caching'] = array(
    '#type' => 'details',
    '#title' => t('Caching'),
  );

  $period = drupal_map_assoc(array(0, 60, 180, 300, 600, 900, 1800, 2700, 3600, 10800, 21600, 32400, 43200, 86400), 'format_interval');
  $period[0] = '<' . t('none') . '>';
  $form['caching']['page_cache_maximum_age'] = array(
    '#type' => 'select',
    '#title' => t('Page cache maximum age'),
    '#default_value' => $config->get('cache.page.max_age'),
    '#options' => $period,
    '#description' => t('The maximum time a page can be cached. This is used as the value for max-age in Cache-Control headers.'),
  );

  $form['caching']['cache'] = array(
    '#type' => 'checkbox',
    '#title' => t('Use internal page cache'),
    '#description' => t("If a reverse proxy cache isn't available, use Drupal's internal cache system to store cached pages."),
    '#default_value' => $config->get('cache.page.use_internal'),
  );

  $directory = 'public://';
  $is_writable = is_dir($directory) && is_writable($directory);
  $disabled = !$is_writable;
  $disabled_message = '';
  if (!$is_writable) {
    $disabled_message = ' ' . t('<strong class="error">Set up the <a href="!file-system">public files directory</a> to make these optimizations available.</strong>', array('!file-system' => url('admin/config/media/file-system')));
  }

  $form['bandwidth_optimization'] = array(
    '#type' => 'details',
    '#title' => t('Bandwidth optimization'),
    '#description' => t('External resources can be optimized automatically, which can reduce both the size and number of requests made to your website.') . $disabled_message,
  );

  $js_hide = ($config->get('cache.page.max_age') > 0) ? '' : ' class="js-hide"';
  $form['bandwidth_optimization']['page_compression'] = array(
    '#type' => 'checkbox',
    '#title' => t('Compress cached pages.'),
    '#default_value' => $config->get('response.gzip'),
    '#states' => array(
      'visible' => array(
        'input[name="cache"]' => array('checked' => TRUE),
      ),
    ),
  );
  $form['bandwidth_optimization']['preprocess_css'] = array(
    '#type' => 'checkbox',
    '#title' => t('Aggregate CSS files.'),
    '#default_value' => $config->get('css.preprocess'),
    '#disabled' => $disabled,
  );
  $form['bandwidth_optimization']['preprocess_js'] = array(
    '#type' => 'checkbox',
    '#title' => t('Aggregate JavaScript files.'),
    '#default_value' => $config->get('js.preprocess'),
    '#disabled' => $disabled,
  );

  $form['#submit'][] = 'drupal_clear_css_cache';
  $form['#submit'][] = 'drupal_clear_js_cache';
  // This form allows page compression settings to be changed, which can
  // invalidate the page cache, so it needs to be cleared on form submit.
  $form['#submit'][] = 'system_clear_page_cache_submit';
  $form['#submit'][] = 'system_performance_settings_submit';

  return system_config_form($form, $form_state);
}

/**
 * Form submission handler for system_performance_settings().
 *
 * @ingroup forms
 */
function system_performance_settings_submit($form, &$form_state) {
  config_context_enter('config.context.free');
  $config = config('system.performance');
  $config->set('cache.page.use_internal', $form_state['values']['cache']);
  $config->set('cache.page.max_age', $form_state['values']['page_cache_maximum_age']);
  $config->set('response.gzip', $form_state['values']['page_compression']);
  $config->set('css.preprocess', $form_state['values']['preprocess_css']);
  $config->set('js.preprocess', $form_state['values']['preprocess_js']);
  $config->save();
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
 * Submit callback; clear the page cache.
 *
 * @ingroup forms
 */
function system_clear_page_cache_submit($form, &$form_state) {
  cache('page')->deleteAll();
}

/**
 * Form builder; Configure the site file handling.
 *
 * @ingroup forms
 * @see system_settings_form_sumbit()
 */
function system_file_system_settings($form, $form_state) {
  $config = config('system.file');
  $form['file_public_path'] = array(
    '#type' => 'textfield',
    '#title' => t('Public file system path'),
    '#default_value' => variable_get('file_public_path', conf_path() . '/files'),
    '#maxlength' => 255,
    '#description' => t('A local file system path where public files will be stored. This directory must exist and be writable by Drupal. This directory must be relative to the Drupal installation directory and be accessible over the web.'),
    '#after_build' => array('system_check_directory'),
  );

  $form['file_private_path'] = array(
    '#type' => 'textfield',
    '#title' => t('Private file system path'),
    '#default_value' => $config->get('path.private'),
    '#maxlength' => 255,
    '#description' => t('An existing local file system path for storing private files. It should be writable by Drupal and not accessible over the web. See the online handbook for <a href="@handbook">more information about securing private files</a>.', array('@handbook' => 'http://drupal.org/documentation/modules/file')),
    '#after_build' => array('system_check_directory'),
  );

  $form['file_temporary_path'] = array(
    '#type' => 'textfield',
    '#title' => t('Temporary directory'),
    '#default_value' => $config->get('path.temporary'),
    '#maxlength' => 255,
    '#description' => t('A local file system path where temporary files will be stored. This directory should not be accessible over the web.'),
    '#after_build' => array('system_check_directory'),
  );
  // Any visible, writeable wrapper can potentially be used for the files
  // directory, including a remote file system that integrates with a CDN.
  foreach (file_get_stream_wrappers(STREAM_WRAPPERS_WRITE_VISIBLE) as $scheme => $info) {
    $options[$scheme] = check_plain($info['description']);
  }

  if (!empty($options)) {
    $form['file_default_scheme'] = array(
      '#type' => 'radios',
      '#title' => t('Default download method'),
      '#default_value' => $config->get('default_scheme'),
      '#options' => $options,
      '#description' => t('This setting is used as the preferred download method. The use of public files is more efficient, but does not provide any access control.'),
    );
  }

  return system_config_form($form, $form_state);
}

/**
 * Save configuration values for file system.
 *
 * @ingroup forms
 * @see system_file_system_settings()
 */
function system_file_system_settings_submit($form, &$form_state) {
  $config = config('system.file')
    ->set('path.private', $form_state['values']['file_private_path'])
    ->set('path.temporary', $form_state['values']['file_temporary_path']);
  variable_set('file_public_path', $form_state['values']['file_public_path']);


  if(isset($form_state['values']['file_default_scheme'])) {
    $config->set('default_scheme', $form_state['values']['file_default_scheme']);
  }
  $config->save();
}

/**
 * Form builder; Configure site image toolkit usage.
 *
 * @ingroup forms
 * @see system_image_toolkit_settings_submit()
 */
function system_image_toolkit_settings($form, &$form_state) {
  $manager = Drupal::service('image.toolkit.manager');
  $toolkits_available = $manager->getAvailableToolkits();
  $current_toolkit = config('system.image')->get('toolkit');

  // If we have available toolkits allow the user to select the image toolkit to
  // use and load the settings forms.
  if (count($toolkits_available)) {
    $options = array();
    foreach($toolkits_available as $id => $definition) {
      $options[$id] = $definition['title'];
    }

    $form['image_toolkit'] = array(
      '#type' => 'radios',
      '#title' => t('Select an image processing toolkit'),
      '#default_value' => $current_toolkit,
      '#options' => $options,
    );

    // Get the toolkit settings forms.
    foreach ($toolkits_available as $id => $definition) {
      $toolkit = $manager->createInstance($id);
      $form['image_toolkit_settings'][$id] = array(
        '#type' => 'fieldset',
        '#title' => t('@toolkit settings', array('@toolkit' => $definition['title'])),
        '#collapsible' => TRUE,
        '#collapsed' => ($id == $current_toolkit) ? FALSE : TRUE,
        '#tree' => TRUE,
      );
      $form['image_toolkit_settings'][$id] += $toolkit->settingsForm();
    }

    $form = system_config_form($form, $form_state);
  }
  else {
    form_set_error('image_toolkit', t('There are no image toolkits available. Drupal comes with support for PHP\'s GD image toolkit. This requires that the GD module for PHP be installed and configured properly. For more information see <a href="@url">PHP\'s image documentation</a>.', array('@url' => 'http://php.net/image')));
  }

  return $form;
}

/**
 * Form submission handler for system_image_toolkit_settings().
 */
function system_image_toolkit_settings_submit($form, &$form_state) {
  config('system.image')
    ->set('toolkit', $form_state['values']['image_toolkit'])
    ->save();

  // Call the form submit handler for each of the toolkits.
  // Get the toolkit settings forms.
  $manager = Drupal::service('image.toolkit.manager');
  foreach ($manager->getAvailableToolkits() as $id => $definition) {
    $toolkit = $manager->createInstance($id);
    $toolkit->settingsFormSubmit($form, $form_state);
  }
}

/**
 * Form builder; Configure how the site handles RSS feeds.
 *
 * @ingroup forms
 */
function system_rss_feeds_settings($form, &$form_state) {
  config_context_enter('config.context.free');
  $rss_config = config('system.rss');
  $form['feed_description'] = array(
    '#type' => 'textarea',
    '#title' => t('Feed description'),
    '#default_value' => $rss_config->get('channel.description'),
    '#description' => t('Description of your site, included in each feed.')
  );
  $form['feed_default_items'] = array(
    '#type' => 'select',
    '#title' => t('Number of items in each feed'),
    '#default_value' => $rss_config->get('items.limit'),
    '#options' => drupal_map_assoc(array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 15, 20, 25, 30)),
    '#description' => t('Default number of items to include in each feed.')
  );
  $form['feed_item_length'] = array(
    '#type' => 'select',
    '#title' => t('Feed content'),
    '#default_value' => $rss_config->get('items.view_mode'),
    '#options' => array(
      'title' => t('Titles only'),
      'teaser' => t('Titles plus teaser'),
      'fulltext' => t('Full text'),
    ),
    '#description' => t('Global setting for the default display of content items in each feed.')
  );

  return system_config_form($form, $form_state);
}

/**
 * Form builder submit handler; Handle submission for RSS feeds settings.
 *
 * @ingroup forms
 */
function system_rss_feeds_settings_submit($form, &$form_state) {
  config_context_enter('config.context.free');
  config('system.rss')
    ->set('channel.description', $form_state['values']['feed_description'])
    ->set('items.limit', $form_state['values']['feed_default_items'])
    ->set('items.view_mode', $form_state['values']['feed_item_length'])
    ->save();
}

/**
 * Form builder; Configure the site regional settings.
 *
 * @ingroup forms
 * @see system_config_form()
 * @see system_regional_settings_submit()
 */
function system_regional_settings($form, &$form_state) {
  $countries = country_get_list();
  $system_timezone = config('system.timezone');
  $system_date = config('system.date');

  // Date settings:
  $zones = system_time_zones();

  $form['locale'] = array(
    '#type' => 'details',
    '#title' => t('Locale'),
  );

  $form['locale']['site_default_country'] = array(
    '#type' => 'select',
    '#title' => t('Default country'),
    '#empty_value' => '',
    '#default_value' => $system_date->get('country.default'),
    '#options' => $countries,
    '#attributes' => array('class' => array('country-detect')),
  );

  $form['locale']['date_first_day'] = array(
    '#type' => 'select',
    '#title' => t('First day of week'),
    '#default_value' => $system_date->get('first_day'),
    '#options' => array(0 => t('Sunday'), 1 => t('Monday'), 2 => t('Tuesday'), 3 => t('Wednesday'), 4 => t('Thursday'), 5 => t('Friday'), 6 => t('Saturday')),
  );

  $form['timezone'] = array(
    '#type' => 'details',
    '#title' => t('Time zones'),
  );

  $form['timezone']['date_default_timezone'] = array(
    '#type' => 'select',
    '#title' => t('Default time zone'),
    '#default_value' => $system_timezone->get('default') ?: date_default_timezone_get(),
    '#options' => $zones,
  );

  $configurable_timezones = $system_timezone->get('user.configurable');
  $form['timezone']['configurable_timezones'] = array(
    '#type' => 'checkbox',
    '#title' => t('Users may set their own time zone.'),
    '#default_value' => $configurable_timezones,
  );

  $form['timezone']['configurable_timezones_wrapper'] =  array(
    '#type' => 'container',
    '#states' => array(
      // Hide the user configured timezone settings when users are forced to use
      // the default setting.
      'invisible' => array(
        'input[name="configurable_timezones"]' => array('checked' => FALSE),
      ),
    ),
  );
  $form['timezone']['configurable_timezones_wrapper']['empty_timezone_message'] = array(
    '#type' => 'checkbox',
    '#title' => t('Remind users at login if their time zone is not set.'),
    '#default_value' => $system_timezone->get('user.warn'),
    '#description' => t('Only applied if users may set their own time zone.')
  );

  $form['timezone']['configurable_timezones_wrapper']['user_default_timezone'] = array(
    '#type' => 'radios',
    '#title' => t('Time zone for new users'),
    '#default_value' => $system_timezone->get('user.default'),
    '#options' => array(
      DRUPAL_USER_TIMEZONE_DEFAULT => t('Default time zone.'),
      DRUPAL_USER_TIMEZONE_EMPTY   => t('Empty time zone.'),
      DRUPAL_USER_TIMEZONE_SELECT  => t('Users may set their own time zone at registration.'),
    ),
    '#description' => t('Only applied if users may set their own time zone.')
  );

  return system_config_form($form, $form_state);
}

/**
 * Form builder submit handler; Handles submission for regional settings.
 *
 * @ingroup forms
 * @see system_regional_settings()
 */
function system_regional_settings_submit($form, &$form_state) {
  config('system.date')
    ->set('country.default', $form_state['values']['site_default_country'])
    ->set('first_day', $form_state['values']['date_first_day'])
    ->save();
  config('system.timezone')
    ->set('default', $form_state['values']['date_default_timezone'])
    ->set('user.configurable', $form_state['values']['configurable_timezones'])
    ->set('user.warn', $form_state['values']['empty_timezone_message'])
    ->set('user.default', $form_state['values']['user_default_timezone'])
    ->save();
}

/**
 * Form builder; Configure the site's maintenance status.
 *
 * @ingroup forms
 * @see system_site_maintenance_mode_submit()
 */
function system_site_maintenance_mode($form, &$form_state) {
  config_context_enter('config.context.free');
  $config = config('system.maintenance');
  $form['maintenance_mode'] = array(
    '#type' => 'checkbox',
    '#title' => t('Put site into maintenance mode'),
    '#default_value' => $config->get('enabled'),
    '#description' => t('Visitors will only see the maintenance mode message. Only users with the "Access site in maintenance mode" <a href="@permissions-url">permission</a> will be able to access the site. Authorized users can log in directly via the <a href="@user-login">user login</a> page.', array('@permissions-url' => url('admin/config/people/permissions'), '@user-login' => url('user'))),
  );
  $form['maintenance_mode_message'] = array(
    '#type' => 'textarea',
    '#title' => t('Message to display when in maintenance mode'),
    '#default_value' => $config->get('message'),
  );

  return system_config_form($form, $form_state);
}

/**
 * Form submission handler for system_site_maintenance_mode().
 *
 * @ingroup forms
 */
function system_site_maintenance_mode_submit($form, &$form_state) {
  config_context_enter('config.context.free');
  config('system.maintenance')
    ->set('enabled', $form_state['values']['maintenance_mode'])
    ->set('message', $form_state['values']['maintenance_mode_message'])
    ->save();
}

/**
 * Menu callback: displays the site status report. Can also be used as a pure check.
 *
 * @param $check
 *   If true, only returns a boolean whether there are system status errors.
 */
function system_status($check = FALSE) {
  // Load .install files
  include_once DRUPAL_ROOT . '/core/includes/install.inc';
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
  return theme('status_report', array('requirements' => $requirements));
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
  drupal_exit();
}

/**
 * Default page callback for batches.
 */
function system_batch_page() {
  require_once DRUPAL_ROOT . '/core/includes/batch.inc';
  $output = _batch_page();

  if ($output === FALSE) {
    throw new AccessDeniedHttpException();
  }
  elseif ($output instanceof Response) {
    return $output;
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
 * Returns HTML for an administrative block for display.
 *
 * @param $variables
 *   An associative array containing:
 *   - block: An array containing information about the block:
 *     - show: A Boolean whether to output the block. Defaults to FALSE.
 *     - title: The block's title.
 *     - content: (optional) Formatted content for the block.
 *     - description: (optional) Description of the block. Only output if
 *       'content' is not set.
 *
 * @ingroup themeable
 */
function theme_admin_block($variables) {
  $block = $variables['block'];
  $output = '';

  // Don't display the block if it has no content to display.
  if (empty($block['show'])) {
    return $output;
  }

  $output .= '<div class="admin-panel">';
  if (!empty($block['title'])) {
    $output .= '<h3>' . $block['title'] . '</h3>';
  }
  if (!empty($block['content'])) {
    $output .= '<div class="body">' . $block['content'] . '</div>';
  }
  else {
    $output .= '<div class="description">' . $block['description'] . '</div>';
  }
  $output .= '</div>';

  return $output;
}

/**
 * Returns HTML for the content of an administrative block.
 *
 * @param $variables
 *   An associative array containing:
 *   - content: An array containing information about the block. Each element
 *     of the array represents an administrative menu item, and must at least
 *     contain the keys 'title', 'href', and 'localized_options', which are
 *     passed to l(). A 'description' key may also be provided.
 *
 * @ingroup themeable
 */
function theme_admin_block_content($variables) {
  $content = $variables['content'];
  $output = '';

  if (!empty($content)) {
    $class = 'admin-list';
    if ($compact = system_admin_compact_mode()) {
      $class .= ' compact';
    }
    $output .= '<dl class="' . $class . '">';
    foreach ($content as $item) {
      $output .= '<dt>' . l($item['title'], $item['href'], $item['localized_options']) . '</dt>';
      if (!$compact && isset($item['description'])) {
        $output .= '<dd>' . filter_xss_admin($item['description']) . '</dd>';
      }
    }
    $output .= '</dl>';
  }
  return $output;
}

/**
 * Returns HTML for an administrative page.
 *
 * @param $variables
 *   An associative array containing:
 *   - blocks: An array of blocks to display. Each array should include a
 *     'title', a 'description', a formatted 'content' and a 'position' which
 *     will control which container it will be in. This is usually 'left' or
 *     'right'.
 *
 * @ingroup themeable
 */
function theme_admin_page($variables) {
  $blocks = $variables['blocks'];

  $stripe = 0;
  $container = array();

  foreach ($blocks as $block) {
    if ($block_output = theme('admin_block', array('block' => $block))) {
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
 * Returns HTML for the output of the admin index page.
 *
 * @param $variables
 *   An associative array containing:
 *   - menu_items: An array of modules to be displayed.
 *
 * @ingroup themeable
 */
function theme_system_admin_index($variables) {
  $menu_items = $variables['menu_items'];

  $stripe = 0;
  $container = array('left' => '', 'right' => '');
  $flip = array('left' => 'right', 'right' => 'left');
  $position = 'left';

  // Iterate over all modules.
  foreach ($menu_items as $module => $block) {
    list($description, $items) = $block;

    // Output links.
    if (count($items)) {
      $block = array();
      $block['title'] = $module;
      $block['content'] = theme('admin_block_content', array('content' => $items));
      $block['description'] = t($description);
      $block['show'] = TRUE;

      if ($block_output = theme('admin_block', array('block' => $block))) {
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
 * Returns HTML for the status report.
 *
 * @param $variables
 *   An associative array containing:
 *   - requirements: An array of requirements.
 *
 * @ingroup themeable
 */
function theme_status_report($variables) {
  $requirements = $variables['requirements'];
  $severities = array(
    REQUIREMENT_INFO => array(
      'title' => t('Info'),
      'class' => 'info',
    ),
    REQUIREMENT_OK => array(
      'title' => t('OK'),
      'class' => 'ok',
    ),
    REQUIREMENT_WARNING => array(
      'title' => t('Warning'),
      'class' => 'warning',
    ),
    REQUIREMENT_ERROR => array(
      'title' => t('Error'),
      'class' => 'error',
    ),
  );
  $output = '<table class="system-status-report"><thead><tr class="element-invisible">';
  $output .= '<th>' . t('Status') . '</th><th>' . t('Component') . '</th><th>' . t('Details') . '</th>';
  $output .= '</tr></thead><tbody>';

  foreach ($requirements as $requirement) {
    if (empty($requirement['#type'])) {
      // Always use the explicit requirement severity, if defined. Otherwise,
      // default to REQUIREMENT_OK in the installer to visually confirm that
      // installation requirements are met. And default to REQUIREMENT_INFO to
      // denote neutral information without special visualization.
      if (isset($requirement['severity'])) {
        $severity = $severities[(int) $requirement['severity']];
      }
      elseif (defined('MAINTENANCE_MODE') && MAINTENANCE_MODE == 'install') {
        $severity = $severities[REQUIREMENT_OK];
      }
      else {
        $severity = $severities[REQUIREMENT_INFO];
      }

      $severity['icon'] = '<div title="' . $severity['title'] . '"><span class="element-invisible">' . $severity['title'] . '</span></div>';

      // Output table rows.
      $output .= '<tr class="' . $severity['class'] . '">';
      $output .= '<td class="status-icon">' . $severity['icon'] . '</td>';
      $output .= '<td class="status-title">' . $requirement['title'] . '</td>';
      $output .= '<td class="status-value">' . $requirement['value'];
      if (!empty($requirement['description'])) {
        $output .= '<div class="description">' . $requirement['description'] . '</div>';
      }
      $output .= '</td></tr>';
    }
  }

  $output .= '</tbody></table>';
  return $output;
}

/**
 * Returns HTML for the modules form.
 *
 * @param $variables
 *   An associative array containing:
 *   - form: A render element representing the form.
 *
 * @ingroup themeable
 */
function theme_system_modules_details($variables) {
  $form = $variables['form'];

  // Individual table headers.
  $rows = array();
  // Iterate through all the modules, which are children of this element.
  foreach (element_children($form) as $key) {
    // Stick the key into $module for easier access.
    $module = $form[$key];
    // Create the row for the table.
    $row = array();
    // Add the checkbox into the first cell.
    unset($module['enable']['#title']);
    $module['#requires'] = array_filter($module['#requires']);
    $module['#required_by'] = array_filter($module['#required_by']);

    $requires = !empty($module['#requires']);
    $required_by = !empty($module['#required_by']);
    $version = !empty($module['version']['#markup']);

    $row[] = array('class' => array('checkbox'), 'data' => drupal_render($module['enable']));

    // Add the module label and expand/collapse functionalty.
    $col2 = '<label id="module-' . $key . '" for="' . $module['enable']['#id'] . '" class="module-name table-filter-text-source">' . drupal_render($module['name']) . '</label>';
    $row[] = array('class' => array('module'), 'data' => $col2);

    // Add the description, along with any modules it requires.
    $description = '<span class="details"><span class="text table-filter-text-source">' . drupal_render($module['description']) . '</span></span>';
    if ($version || $requires || $required_by) {
      $description .= ' <div class="requirements">';
      if ($version) {
        $description .= '<div class="admin-requirements">' . t('Version: !module-version', array('!module-version' => drupal_render($module['version']))) . '</div>';
      }
      if ($requires) {
        $description .= '<div class="admin-requirements">' . t('Requires: !module-list', array('!module-list' => implode(', ', $module['#requires']))) . '</div>';
      }
      if ($required_by) {
        $description .= '<div class="admin-requirements">' . t('Required by: !module-list', array('!module-list' => implode(', ', $module['#required_by']))) . '</div>';
      }
      $description .= '</div>';
    }
    $links = '';
    foreach (array('help', 'permissions', 'configure') as $key) {
      $links .= drupal_render($module['links'][$key]);
    }
    if ($links) {
      $description .= '  <div class="links">';
      $description .= $links;
      $description .= '</div>';
    }
    $col4 = '<div class="inner" tabindex="0" role="button"><span class="module-description-prefix element-invisible">Show description</span> ' . $description . '</div>';
    $row[] = array('class' => array('description', 'expand'), 'data' => $col4);

    $rows[] = $row;
  }

  return theme('table', array('header' => $form['#header'], 'rows' => $rows));
}

/**
 * Returns HTML for a message about incompatible modules.
 *
 * @param $variables
 *   An associative array containing:
 *   - message: The form array representing the currently disabled modules.
 *
 * @ingroup themeable
 */
function theme_system_modules_incompatible($variables) {
  return '<div class="incompatible">' . $variables['message'] . '</div>';
}

/**
 * Returns HTML for a table of currently disabled modules.
 *
 * @param $variables
 *   An associative array containing:
 *   - form: A render element representing the form.
 *
 * @ingroup themeable
 */
function theme_system_modules_uninstall($variables) {
  $form = $variables['form'];

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
    if (!empty($form['modules'][$module]['#required_by'])) {
      $disabled_message = format_plural(count($form['modules'][$module]['#required_by']),
        'To uninstall @module, the following module must be uninstalled first: @required_modules',
        'To uninstall @module, the following modules must be uninstalled first: @required_modules',
        array('@module' => $form['modules'][$module]['#module_name'], '@required_modules' => implode(', ', $form['modules'][$module]['#required_by'])));
      $disabled_message = '<div class="admin-requirements">' . $disabled_message . '</div>';
    }
    else {
      $disabled_message = '';
    }
    $rows[] = array(
      array('data' => drupal_render($form['uninstall'][$module]), 'align' => 'center'),
      '<strong><label for="' . $form['uninstall'][$module]['#id'] . '">' . drupal_render($form['modules'][$module]['name']) . '</label></strong>',
      array('data' => drupal_render($form['modules'][$module]['description']) . $disabled_message, 'class' => array('description')),
    );
  }

  $output  = theme('table', array('header' => $header, 'rows' => $rows, 'empty' => t('No modules are available to uninstall.')));
  $output .= drupal_render_children($form);

  return $output;
}

/**
 * Returns HTML for the Appearance page.
 *
 * @param $variables
 *   An associative array containing:
 *   - theme_groups: An associative array containing groups of themes.
 *
 * @ingroup themeable
 */
function theme_system_themes_page($variables) {
  $theme_groups = $variables['theme_groups'];

  $output = '<div id="system-themes-page">';

  foreach ($variables['theme_group_titles'] as $state => $title) {
    if (!count($theme_groups[$state])) {
      // Skip this group of themes if no theme is there.
      continue;
    }
    // Start new theme group.
    $output .= '<div class="system-themes-list system-themes-list-'. $state .' clearfix"><h2>'. $title .'</h2>';

    foreach ($theme_groups[$state] as $theme) {

      // Theme the screenshot.
      $screenshot = $theme->screenshot ? theme('image', $theme->screenshot) : '<div class="no-screenshot">' . t('no screenshot') . '</div>';

      // Localize the theme description.
      $description = t($theme->info['description']);

      // Style theme info
      $notes = count($theme->notes) ? ' (' . join(', ', $theme->notes) . ')' : '';
      $theme->classes[] = 'theme-selector';
      $theme->classes[] = 'clearfix';
      $output .= '<div class="'. join(' ', $theme->classes) .'">' . $screenshot . '<div class="theme-info"><h3>' . $theme->info['name'] . ' ' . (isset($theme->info['version']) ? $theme->info['version'] : '') . $notes . '</h3><div class="theme-description">' . $description . '</div>';

      // Make sure to provide feedback on compatibility.
      if (!empty($theme->incompatible_core)) {
        $output .= '<div class="incompatible">' . t('This version is not compatible with Drupal !core_version and should be replaced.', array('!core_version' => DRUPAL_CORE_COMPATIBILITY)) . '</div>';
      }
      elseif (!empty($theme->incompatible_php)) {
        if (substr_count($theme->info['php'], '.') < 2) {
          $theme->info['php'] .= '.*';
        }
        $output .= '<div class="incompatible">' . t('This theme requires PHP version @php_required and is incompatible with PHP version !php_version.', array('@php_required' => $theme->info['php'], '!php_version' => phpversion())) . '</div>';
      }
      elseif (!empty($theme->incompatible_base)) {
        $output .= '<div class="incompatible">' . t('This theme requires the base theme @base_theme to operate correctly.', array('@base_theme' => $theme->info['base theme'])) . '</div>';
      }
      elseif (!empty($theme->incompatible_engine)) {
        $output .= '<div class="incompatible">' . t('This theme requires the theme engine @theme_engine to operate correctly.', array('@theme_engine' => $theme->info['engine'])) . '</div>';
      }
      else {
        $output .= theme('links', array('links' => $theme->operations, 'attributes' => array('class' => array('operations', 'clearfix'))));
      }
      $output .= '</div></div>';
    }
    $output .= '</div>';
  }
  $output .= '</div>';

  return $output;
}

/**
 * Menu callback; present a form for deleting a date format.
 *
 * @param string $date_format_id
 *   The machine name for the date format that may be deleted.
 */
function system_date_delete_format_form($form, &$form_state, $date_format_id) {
  $form['date_format_id'] = array(
    '#type' => 'value',
    '#value' => $date_format_id,
  );
  $format = system_get_date_formats($date_format_id);
  $output = confirm_form($form,
    t('Are you sure you want to remove the format %name : %format?', array('%name' => $format['name'], '%format' => format_date(REQUEST_TIME, $date_format_id))),
    'admin/config/regional/date-time/formats',
    t('This action cannot be undone.'),
    t('Remove'), t('Cancel'),
    'confirm'
  );

  return $output;
}

/**
 * Delete a configured date format.
 */
function system_date_delete_format_form_submit($form, &$form_state) {
  if ($form_state['values']['confirm']) {
    $format = system_get_date_formats($form_state['values']['date_format_id']);
    system_date_format_delete($form_state['values']['date_format_id']);
    drupal_set_message(t('Removed date format %format.', array('%format' => $format['name'])));
    $form_state['redirect'] = 'admin/config/regional/date-time/formats';
  }
}

/**
 * Displays the date format strings overview page.
 */
function system_date_time_formats() {
  $header = array(
    array('data' => t('Machine name'), 'field' => 'machine_name'),
    array('data' => t('Name'), 'field' => 'name'),
    array('data' => t('Pattern'), 'field' => 'pattern'),
    array('data' => t('Operations'))
  );
  $rows = array();

  $formats = system_get_date_formats();

  if (!empty($formats)) {
    foreach ($formats as $date_format_id => $format_info) {
      // Do not display date formats that are locked.
      if (empty($format_info['locked'])) {
        $row = array();
        $row[] = array('data' => $date_format_id);
        $row[] = array('data' => $format_info['name']);
        $row[] = array('data' => format_date(REQUEST_TIME, $date_format_id));

        // Prepare Operational links.
        $links = array();
        $links['edit'] = array(
          'title' => t('Edit'),
          'href' => 'admin/config/regional/date-time/formats/' . $date_format_id . '/edit',
        );
        $links['delete'] = array(
          'title' => t('Delete'),
          'href' => 'admin/config/regional/date-time/formats/' . $date_format_id . '/delete',
        );
        $row['operations'] = array('data' => array(
          '#type' => 'operations',
          '#links' => $links,
        ));

        $rows[] = $row;
      }
    }
  }

  $build['date_formats_table'] = array(
    '#theme' => 'table',
    '#header' => $header,
    '#rows' => $rows,
    '#empty' => t('No custom date formats available. <a href="@link">Add date format</a>.', array('@link' => url('admin/config/regional/date-time/formats/add'))),
  );

  return $build;
}

/**
 * Allow users to add additional date formats.
 *
 * @param string $date_format_id
 *   (optional) When present, provides the machine name of the date format that
 *   is being modified. Defaults to an empty string.
 */
function system_configure_date_formats_form($form, &$form_state, $date_format_id = '') {
  $formats = system_get_date_formats();
  $format_info = config('system.date')->get('formats.' . $date_format_id);
  $patterns = $format_info['pattern'];
  $date = new DrupalDateTime;
  $pattern = $date->canUseIntl() ? $patterns[DrupalDateTime::INTL] : $patterns[DrupalDateTime::PHP];

  $form['date_format_name'] = array(
    '#type' => 'textfield',
    '#title' => 'Name',
    '#maxlength' => 100,
    '#description' => t('Name of the date format'),
    '#default_value' => empty($format_info['name']) ? '' : $format_info['name']
  );

  $now = !empty($date_format_id) ? t('Displayed as %date', array('%date' => format_date(REQUEST_TIME, $date_format_id))) : '';

  $form['date_format_id'] = array(
    '#type' => 'machine_name',
    '#title' => t('Machine-readable name'),
    '#description' => t('A unique machine-readable name. Can only contain lowercase letters, numbers, and underscores.'),
    '#disabled' => !empty($date_format_id),
    '#default_value' => $date_format_id,
    '#machine_name' => array(
      'exists' => 'system_date_format_exists',
      'source' => array('date_format_name'),
    ),
  );

  if (class_exists('intlDateFormatter')) {
    $description = t('A user-defined date format. See the <a href="@url">PHP manual</a> for available options.', array('@url' => 'http://userguide.icu-project.org/formatparse/datetime'));
  }
  else {
    $description = t('A user-defined date format. See the <a href="@url">PHP manual</a> for available options.', array('@url' => 'http://php.net/manual/function.date.php'));
  }
  $form['date_format_pattern'] = array(
    '#type' => 'textfield',
    '#title' => t('Format string'),
    '#maxlength' => 100,
    '#description' => $description,
    '#default_value' => empty($pattern) ? '' : $pattern,
    '#field_suffix' => ' <small id="edit-date-format-suffix">' . $now . '</small>',
    '#ajax' => array(
      'callback' => 'system_date_time_lookup',
      'event' => 'keyup',
      'progress' => array('type' => 'throbber', 'message' => NULL),
    ),
    '#required' => TRUE,
  );

  $languages = language_list();

  $options = array();
  foreach ($languages as $langcode => $data) {
    $options[$langcode] = $data->name;
  }

  if (!empty($options)) {
    $form['date_langcode'] = array(
      '#title' => t('Select localizations'),
      '#type' => 'select',
      '#options' => $options,
      '#multiple' => TRUE,
      '#default_value' => empty($format_info['locales']) ? '' : $format_info['locales']
    );
  }

  $form['actions'] = array('#type' => 'actions');
  $form['actions']['update'] = array(
    '#type' => 'submit',
    '#value' => (!empty($date_format_id) ? t('Save format') : t('Add format')),
  );

  $form['#validate'][] = 'system_date_formats_form_validate';
  $form['#submit'][] = 'system_date_formats_form_submit';

  return $form;
}

/**
 * Checks if the chosen machine_name exists or not.
 */
function system_date_format_exists($candidate_machine_name) {
  if ($formats = system_get_date_formats()) {
    return array_key_exists($candidate_machine_name, $formats);
  }
  return FALSE;
}

/**
 * Validate date format string submission.
 */
function system_date_formats_form_validate($form, &$form_state) {
  $formats = system_get_date_formats();
  $format = trim($form_state['values']['date_format_pattern']);

  // The machine name field should already check to see if the requested
  // machine name is available. Regardless of machine_name or human readable
  // name, check to see if the provided pattern exists.
  if (!empty($formats) && in_array($format, array_values($formats)) && (!isset($form_state['values']['date_format_id']) || $form_state['values']['date_format_id'] != $formats[$format]['date_format_id'])) {
    form_set_error('date_format', t('This format already exists. Enter a unique format string.'));
  }
}

/**
 * Process date format string submission.
 */
function system_date_formats_form_submit($form, &$form_state) {
  $date = new DrupalDateTime;
  $pattern_type = $date->canUseIntl() ? DrupalDateTime::INTL : DrupalDateTime::PHP;
  $format = array();
  $format['name'] = check_plain($form_state['values']['date_format_name']);
  $format['pattern'][$pattern_type] = trim($form_state['values']['date_format_pattern']);
  $format['locales'] = !empty($form_state['values']['date_langcode']) ? $form_state['values']['date_langcode'] : array();
  // Formats created in the UI are not locked.
  $format['locked'] = 0;

  system_date_format_save($form_state['values']['date_format_id'], $format);
  if (!empty($form_state['values']['date_format_id'])) {
    drupal_set_message(t('Custom date format updated.'));
  }
  else {
    drupal_set_message(t('Custom date format added.'));
  }

  $form_state['redirect'] = 'admin/config/regional/date-time/formats';
}

/**
 * Page callback: Displays edit date format links for each language.
 *
 * @see locale_menu()
 */
function system_date_format_language_overview_page() {
  $header = array(t('Language'), t('Operations'));

  $languages = language_list();
  foreach ($languages as $langcode => $language) {
    $row = array();
    $row[] = $language->name;
    $links = array();
    $links['edit'] = array(
      'title' => t('Edit'),
      'href' => "admin/config/regional/date-time/locale/$langcode/edit",
    );
    $links['reset'] = array(
      'title' => t('Reset'),
      'href' => "admin/config/regional/date-time/locale/$langcode/reset",
    );
    $row[] = array(
      'data' => array(
        '#type' => 'operations',
        '#links' => $links,
      ),
    );
    $rows[] = $row;
  }

  return theme('table', array('header' => $header, 'rows' => $rows));
}

/**
 * Form constructor for the date localization configuration form.
 *
 * @param $langcode
 *   The code for the current language.
 *
 * @see locale_menu()
 * @see system_date_format_localize_form_submit()
 * @ingroup forms
 */
function system_date_format_localize_form($form, &$form_state, $langcode) {
  // Display the current language name.
  $form['language'] = array(
    '#type' => 'item',
    '#title' => t('Language'),
    '#markup' => language_load($langcode)->name,
    '#weight' => -10,
  );
  $form['langcode'] = array(
    '#type' => 'value',
    '#value' => $langcode,
  );

  // Get list of available formats.
  $formats = system_get_date_formats();
  $choices = array();
  foreach ($formats as $date_format_id => $format_info) {
    // Ignore values that are localized.
    if (empty($format_info['locales'])) {
      $choices[$date_format_id] = format_date(REQUEST_TIME, $date_format_id);
    }
    else {
      unset($formats[$date_format_id]);
    }
  }

  // Get configured formats for each language.
  $locale_formats = system_date_format_locale($langcode);
  if (!empty($locale_formats)) {
    $formats += $locale_formats;
    foreach ($locale_formats as $date_format_id => $format_info) {
      $choices[$date_format_id] = format_date(REQUEST_TIME, $date_format_id);
    }
  }

  // Display a form field for each format type.
  foreach ($formats as $date_format_id => $format_info) {
    // Show date format select list.
    $form['date_formats']['date_format_' . $date_format_id] = array(
      '#type' => 'select',
      '#title' => check_plain($format_info['name']),
      '#attributes' => array('class' => array('date-format')),
      '#default_value' => isset($choices[$date_format_id]) ? $date_format_id : 'custom',
      '#options' => $choices,
    );
  }

  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save configuration'),
  );

  return $form;
}

/**
 * Ajax callback; Returns the date for a given format string.
 */
function system_date_time_lookup($form, &$form_state) {
  $format = '';
  if (!empty($form_state['values']['date_format_pattern'])) {
    $format = t('Displayed as %date_format', array('%date_format' => format_date(REQUEST_TIME, 'custom', $form_state['values']['date_format_pattern'])));
  }
  // Return a command instead of a string, since the Ajax framework
  // automatically prepends an additional empty DIV element for a string, which
  // breaks the layout.
  $response = new AjaxResponse();
  $response->addCommand(new ReplaceCommand('#edit-date-format-suffix', '<small id="edit-date-format-suffix">' . $format . '</small>'));
  return $response;
}

/**
 * Form submission handler for system_date_format_localize_form().
 */
function system_date_format_localize_form_submit($form, &$form_state) {
  $langcode = $form_state['values']['langcode'];

  $formats = system_get_date_formats();
  foreach ($formats as $date_format_id => $format_info) {
    if (isset($form_state['values']['date_format_' . $date_format_id])) {
      $format = $form_state['values']['date_format_' . $date_format_id];
      system_date_format_localize_form_save($langcode, $date_format_id, $formats[$format]['pattern']);
    }
  }
  drupal_set_message(t('Configuration saved.'));
  $form_state['redirect'] = 'admin/config/regional/date-time/locale';
}

/**
 * Returns HTML for a locale date format form.
 *
 * @param $variables
 *   An associative array containing:
 *   - form: A render element representing the form.
 *
 * @ingroup themeable
 */
function theme_system_date_format_localize_form($variables) {
  $form = $variables['form'];
  $header = array(
    'machine_name' => t('Machine Name'),
    'pattern' => t('Format'),
  );

  foreach (element_children($form['date_formats']) as $key) {
    $row = array();
    $row[] = $form['date_formats'][$key]['#title'];
    unset($form['date_formats'][$key]['#title']);
    $row[] = array('data' => drupal_render($form['date_formats'][$key]));
    $rows[] = $row;
  }

  $output = drupal_render($form['language']);
  $output .= theme('table', array('header' => $header, 'rows' => $rows));
  $output .= drupal_render_children($form);

  return $output;
}

/**
 * Form constructor for the reset date format form.
 *
 * @param $langcode
 *   Language code, e.g. 'en'.
 *
 * @see locale_menu()
 * @see system_date_format_localize_reset_form_submit()
 * @ingroup forms
 */
function system_date_format_localize_reset_form($form, &$form_state, $langcode) {
  $form['langcode'] = array('#type' => 'value', '#value' => $langcode);
  return confirm_form($form,
    t('Are you sure you want to reset the date formats for %language to the global defaults?', array('%language' => language_load($langcode)->name)),
    'admin/config/regional/date-time/locale',
    t('Resetting will remove all localized date formats for this language. This action cannot be undone.'),
    t('Reset'), t('Cancel'));
}

/**
 * Form submission handler for locale_date_format_reset_form().
 */
function system_date_format_localize_reset_form_submit($form, &$form_state) {
  config('locale.config.' . $form['langcode']['#value'] . '.system.date')
    ->delete();
  $form_state['redirect'] = 'admin/config/regional/date-time/locale';
}

/**
 * Save locale specific date formats to the database.
 *
 * @param $langcode
 *   Language code, can be 2 characters, e.g. 'en' or 5 characters, e.g.
 *   'en-CA'.
 * @param $date_format_id
 *   Date format id, e.g. 'short', 'medium'.
 * @param $format
 *   The date format string.
 */
function system_date_format_localize_form_save($langcode, $date_format_id, $format) {
  config('locale.config.' . $langcode . '.system.date')
    ->set('formats.' . $date_format_id . '.pattern', $format)
    ->save();
}
