<?php

/**
 * @file
 * Admin page callbacks for the system module.
 */

use Drupal\system\DateFormatInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Menu callback; Provide the administration overview page.
 */
function system_admin_config_page() {
  // Check for status report errors.
  // @todo Use depedancy injection in http://drupal.org/node/1987810.
  if (Drupal::service('system.manager')->checkRequirements() && user_access('administer site configuration')) {
    drupal_set_message(t('One or more problems were detected with your Drupal installation. Check the <a href="@status">status report</a> for more information.', array('@status' => url('admin/reports/status'))), 'error');
  }
  $blocks = array();
  if ($system_link = entity_load_multiple_by_properties('menu_link', array('link_path' => 'admin/config', 'module' => 'system'))) {
    $system_link = reset($system_link);
    $query = Drupal::entityQuery('menu_link')
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
 * Menu callback; displays a listing of all themes.
 */
function system_themes_page() {
  // Get current list of themes.
  $themes = system_rebuild_theme_data();
  uasort($themes, 'system_sort_modules_by_info_name');

  $theme_default = Drupal::config('system.theme')->get('default');
  $theme_groups  = array();
  $admin_theme = Drupal::config('system.theme')->get('admin');

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
    '#default_value' => Drupal::config('system.theme')->get('admin'),
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
  Drupal::config('system.theme')->set('admin', $form_state['values']['admin_theme'])->save();
}

/**
 * Menu callback; Set the default theme.
 */
function system_theme_default() {
  $request = Drupal::request();
  $theme = $request->get('theme');
  $token = $request->get('token');
  if (!empty($theme) && !empty($token) && drupal_valid_token($token, 'system-theme-operation-link')) {
    // Get current list of themes.
    $themes = list_themes();

    // Check if the specified theme is one recognized by the system.
    if (!empty($themes[$theme])) {
      // Enable the theme if it is currently disabled.
      if (empty($themes[$theme]->status)) {
       theme_enable(array($theme));
      }
      // Set the default theme.
      Drupal::config('system.theme')
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
      $admin_theme = Drupal::config('system.theme')->get('admin');
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
    return new RedirectResponse(url('admin/appearance', array('absolute' => TRUE)));
  }
  throw new AccessDeniedHttpException();
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
  $output = '<table class="system-status-report"><thead><tr class="visually-hidden">';
  $output .= '<th>' . t('Status') . '</th><th>' . t('Component') . '</th><th>' . t('Details') . '</th>';
  $output .= '</tr></thead><tbody>';

  foreach ($requirements as $requirement) {
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

    $severity['icon'] = '<div title="' . $severity['title'] . '"><span class="visually-hidden">' . $severity['title'] . '</span></div>';

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
    $description = '';
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
    $details = array(
      '#type' => 'details',
      '#title' => '<span class="text"> ' . drupal_render($module['description']) . '</span>',
      '#attributes' => array('id' => $module['enable']['#id'] . '-description'),
      '#description' => $description,
      '#collapsed' => TRUE,
    );
    $col4 = drupal_render($details);
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
    if (!empty($form['modules'][$module]['#dependents'])) {
      $disabled_message = format_plural(count($form['modules'][$module]['#dependents']),
        'To uninstall @module, the following module must be uninstalled first: @required_modules',
        'To uninstall @module, the following modules must be uninstalled first: @required_modules',
        array('@module' => $form['modules'][$module]['#module_name'], '@required_modules' => implode(', ', $form['modules'][$module]['#dependents'])));
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
      if ($theme->screenshot) {
        $image = array(
          '#theme' => 'image',
          '#uri' => $theme->screenshot['uri'],
          '#alt' => $theme->screenshot['alt'],
          '#title' => $theme->screenshot['title'],
          '#attributes' => $theme->screenshot['attributes'],
        );
        $screenshot = drupal_render($image);
      }
      else {
        $screenshot = '<div class="no-screenshot"><div class="no-screenshot__text">' . t('no screenshot') . '</div></div>';
      }

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
        $links = array(
          '#theme' => 'links',
          '#links' => $theme->operations,
          '#attributes' => array(
            'class' => array('operations', 'clearfix'),
          ),
        );
        $output .= drupal_render($links);
      }
      $output .= '</div></div>';
    }
    $output .= '</div>';
  }
  $output .= '</div>';

  return $output;
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
  $date_service = Drupal::service('date');
  $formats = Drupal::entityManager()
    ->getStorageController('date_format')
    ->loadMultiple();
  $choices = array();
  foreach ($formats as $date_format_id => $format_info) {
    // Ignore values that are localized.
    if (!$format_info->hasLocales()) {
      $choices[$date_format_id] = $date_service->format(REQUEST_TIME, $date_format_id);
    }
  }

  // Get configured formats for each language.
  $config_prefix = 'locale.config.' . $langcode . '.system.date_format.';
  foreach (config_get_storage_names_with_prefix($config_prefix) as $config_id) {
    $date_format_id = substr($config_id, strlen($config_prefix));
    $choices[$date_format_id] = $date_service->format(REQUEST_TIME, $date_format_id);
  }

  // Display a form field for each format type.
  foreach ($formats as $date_format_id => $format_info) {
    // Show date format select list.
    $form['date_formats']['date_format_' . $date_format_id] = array(
      '#type' => 'select',
      '#title' => check_plain($format_info->label()),
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
 * Form submission handler for system_date_format_localize_form().
 */
function system_date_format_localize_form_submit($form, &$form_state) {
  $langcode = $form_state['values']['langcode'];

  $formats = entity_load_multiple('date_format');
  foreach ($formats as $date_format_id => $format_info) {
    if (isset($form_state['values']['date_format_' . $date_format_id])) {
      $format = $form_state['values']['date_format_' . $date_format_id];
      system_date_format_localize_form_save($langcode, $date_format_id, $formats[$format]);
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
 * Save locale specific date formats to the database.
 *
 * @param string $langcode
 *   Language code, can be 2 characters, e.g. 'en' or 5 characters, e.g.
 *   'en-CA'.
 * @param string $date_format_id
 *   Date format id, e.g. 'short', 'medium'.
 * @param \Drupal\system\DateFormatInterface $format
 *   The date format entity.
 */
function system_date_format_localize_form_save($langcode, $date_format_id, DateFormatInterface $format) {
  $format->addLocale($langcode)->save();
  Drupal::config('locale.config.' . $langcode . '.system.date_format.' . $date_format_id)
    ->set('pattern', $format->get('pattern'))
    ->save();
}
