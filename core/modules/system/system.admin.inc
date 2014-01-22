<?php

/**
 * @file
 * Admin page callbacks for the system module.
 */

use Drupal\Core\Cache\Cache;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Menu callback; Set the default theme.
 */
function system_theme_default() {
  $request = \Drupal::request();
  $theme = $request->get('theme');
  if (!empty($theme)) {
    // Get current list of themes.
    $themes = list_themes();

    // Check if the specified theme is one recognized by the system.
    if (!empty($themes[$theme])) {
      // Enable the theme if it is currently disabled.
      if (empty($themes[$theme]->status)) {
       theme_enable(array($theme));
      }
      // Set the default theme.
      \Drupal::config('system.theme')
        ->set('default', $theme)
        ->save();

      // Rebuild the menu. This duplicates the menu_router_rebuild() in
      // theme_enable(). However, modules must know the current default theme in
      // order to use this information in hook_menu() or hook_menu_alter()
      // implementations, and doing the variable_set() before the theme_enable()
      // could result in a race condition where the theme is default but not
      // enabled.
      \Drupal::service('router.builder')->rebuild();
      menu_router_rebuild();
      Cache::deleteTags(array('local_task' => TRUE));

      // The status message depends on whether an admin theme is currently in use:
      // a value of 0 means the admin theme is set to be the default theme.
      $admin_theme = \Drupal::config('system.theme')->get('admin');
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
    $output .= '<div class="body">' . render($block['content']) . '</div>';
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
    $admin_block = array(
      '#theme' => 'admin_block',
      '#block' => $block,
    );
    if ($block_output = drupal_render($admin_block)) {
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

  $system_compact_link = array('#theme' => 'system_compact_link');
  $output = '<div class="admin clearfix">';
  $output .= drupal_render($system_compact_link);

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

  $container = array('left' => '', 'right' => '');
  $flip = array('left' => 'right', 'right' => 'left');
  $position = 'left';

  // Iterate over all modules.
  foreach ($menu_items as $module => $block) {
    list($description, $items) = $block;

    // Output links.
    if (count($items)) {
      $admin_block_content = array(
        '#theme' => 'admin_block_content',
        '#content' => $items,
      );
      $block = array();
      $block['title'] = $module;
      $block['content'] = drupal_render($admin_block_content);
      $block['description'] = t($description);
      $block['show'] = TRUE;

      $admin_block = array(
        '#theme' => 'admin_block',
        '#block' => $block,
      );
      if ($block_output = drupal_render($admin_block)) {
        if (!isset($block['position'])) {
          // Perform automatic striping.
          $block['position'] = $position;
          $position = $flip[$position];
        }
        $container[$block['position']] .= $block_output;
      }
    }
  }

  $system_compact_link = array('#theme' => 'system_compact_link');
  $output = '<div class="admin clearfix">';
  $output .= drupal_render($system_compact_link);
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
 * This theme function is dependent on install.inc being loaded, because
 * that's where the constants are defined.
 *
 * @param $variables
 *   An associative array containing:
 *   - requirements: An array of requirements/status items. Each requirement
 *     is an associative array containing the following elements:
 *     - title: The name of the requirement.
 *     - value: (optional) The current value (version, time, level, etc).
 *     - description: (optional) The description of the requirement.
 *     - severity: (optional) The requirement's result/severity level, one of:
 *       - REQUIREMENT_INFO: Status information.
 *       - REQUIREMENT_OK: The requirement is satisfied.
 *       - REQUIREMENT_WARNING: The requirement failed with a warning.
 *       - REQUIREMENT_ERROR: The requirement failed with an error.
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
    // hook_requirements does not require value to be set. Set to null if not:
    if (isset($requirement['value'])) {
      $value = $requirement['value'];
    }
    else {
      $value = NULL;
    }

    $severity['icon'] = '<div title="' . $severity['title'] . '"><span class="visually-hidden">' . $severity['title'] . '</span></div>';

    // Output table rows.
    $output .= '<tr class="' . $severity['class'] . '">';
    $output .= '<td class="status-icon">' . $severity['icon'] . '</td>';
    $output .= '<td class="status-title">' . $requirement['title'] . '</td>';
    $output .= '<td class="status-value">' . $value;
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

  $table = array(
    '#theme' => 'table',
    '#header' => $form['#header'],
    '#rows' => $rows,
  );
  return drupal_render($table);
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

  $table = array(
    '#theme' => 'table',
    '#header' => $header,
    '#rows' => $rows,
    '#empty' => t('No modules are available to uninstall.'),
  );
  $output  = drupal_render($table);
  $output .= drupal_render_children($form);

  return $output;
}

/**
 * Returns HTML for the Appearance page.
 *
 * @param $variables
 *   An associative array containing:
 *   - theme_groups: An associative array containing groups of themes.
 *   - theme_group_titles: An associative array containing titles of themes.
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
        $output .= '<div class="incompatible">' . t('This version is not compatible with Drupal !core_version and should be replaced.', array('!core_version' => \Drupal::CORE_COMPATIBILITY)) . '</div>';
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
