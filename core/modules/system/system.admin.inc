<?php

/**
 * @file
 * Admin page callbacks for the system module.
 */

use Drupal\Core\Cache\Cache;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Template\Attribute;
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

      \Drupal::service('router.builder')->setRebuildNeeded();

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
 * @param \Drupal\Core\Extension\Extension $file
 *   The file at which the check starts.
 * @return
 *   Returns TRUE if an incompatible file is found, NULL (no return value)
 *   otherwise.
 */
function _system_is_incompatible(&$incompatible, $files, Extension $file) {
  if (isset($incompatible[$file->getName()])) {
    return TRUE;
  }
  // Recursively traverse required modules, looking for incompatible modules.
  foreach ($file->requires as $requires) {
    if (isset($files[$requires]) && _system_is_incompatible($incompatible, $files, $files[$requires])) {
      $incompatible[$file->getName()] = TRUE;
      return TRUE;
    }
  }
}

/**
 * Prepares variables for administrative content block templates.
 *
 * Default template: admin-block-content.html.twig.
 *
 * @param $variables
 *   An associative array containing:
 *   - content: An array containing information about the block. Each element
 *     of the array represents an administrative menu item, and must at least
 *     contain the keys 'title', 'link_path', and 'localized_options', which are
 *     passed to l(). A 'description' key may also be provided.
 *
 * @ingroup themeable
 */
function template_preprocess_admin_block_content(&$variables) {
  if (!empty($variables['content'])) {
    $compact = system_admin_compact_mode();
    $variables['attributes'] = array('class' => array('admin-list'));
    if ($compact) {
      $variables['attributes']['class'][] = 'compact';
    }
    foreach ($variables['content'] as $key => $item) {
      $variables['content'][$key]['link'] = l($item['title'], $item['link_path'], $item['localized_options']);
      if (!$compact && isset($item['description'])) {
        $variables['content'][$key]['description'] = filter_xss_admin($item['description']);
      }
      else {
        $variables['content'][$key]['description'] = FALSE;
      }
    }
  }
}

/**
 * Prepares variables for administrative index page templates.
 *
 * Default template: admin-page.html.twig.
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
function template_preprocess_admin_page(&$variables) {
  $variables['system_compact_link'] = array(
    '#theme' => 'system_compact_link',
  );
  $variables['containers'] = array();
  $stripe = 0;
  foreach ($variables['blocks'] as $block) {
    if (!empty($block['content']['#content'])) {
      if (empty($block['position'])) {
        // Perform automatic striping.
        $block['position'] = ++$stripe % 2 ? 'left' : 'right';
      }
      $variables['containers'][$block['position']]['blocks'][] = array(
        '#theme' => 'admin_block',
        '#block' => $block,
      );
    }
  }
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
 * Prepares variables for status report template.
 *
 * Default template: status-report.html.twig.
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
function template_preprocess_status_report(&$variables) {
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

  foreach ($variables['requirements'] as $i => $requirement) {
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
    $variables['requirements'][$i]['severity_class'] = $severity['class'];
    $variables['requirements'][$i]['severity_title'] = $severity['title'];
  }
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
    );
    $col4 = drupal_render($details);
    $row[] = array('class' => array('description', 'expand'), 'data' => $col4);

    $rows[] = $row;
  }

  $table = array(
    '#type' => 'table',
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
    '#type' => 'table',
    '#header' => $header,
    '#rows' => $rows,
    '#empty' => t('No modules are available to uninstall.'),
  );
  $output  = drupal_render($table);
  $output .= drupal_render_children($form);

  return $output;
}

/**
 * Prepares variables for appearance page templates.
 *
 * Default template: system-themes-page.html.twig.
 *
 * @param $variables
 *   An associative array containing:
 *   - theme_groups: An associative array containing groups of themes.
 *   - theme_group_titles: An associative array containing titles of themes.
 *
 * @ingroup themeable
 */
function template_preprocess_system_themes_page(&$variables) {
  $groups = array();
  $theme_groups = $variables['theme_groups'];
  $variables['attributes']['id'] = 'system-themes-page';

  foreach ($variables['theme_group_titles'] as $state => $title) {
    if (!count($theme_groups[$state])) {
      // Skip this group of themes if no theme is there.
      continue;
    }
    // Start new theme group.
    $theme_group = array();
    $theme_group['state'] = $state;
    $theme_group['title'] = $title;
    $theme_group['themes'] = array();
    $theme_group['attributes'] = new Attribute(array('class' => array('system-themes-list', 'system-themes-list-' . $state, 'clearfix')));

    foreach ($theme_groups[$state] as $theme) {
      $current_theme = array();

      // Screenshot depicting the theme.
      if ($theme->screenshot) {
        $current_theme['screenshot'] = array(
          '#theme' => 'image',
          '#uri' => $theme->screenshot['uri'],
          '#alt' => $theme->screenshot['alt'],
          '#title' => $theme->screenshot['title'],
          '#attributes' => $theme->screenshot['attributes'],
        );
      }
      else {
        $current_theme['screenshot'] = array();
      }

      // Localize the theme description.
      $current_theme['description'] = t($theme->info['description']);

      // Style theme info.
      $theme->classes[] = 'theme-selector';
      $theme->classes[] = 'clearfix';
      $current_theme['attributes'] = new Attribute(array('class' => $theme->classes));
      $current_theme['name'] = $theme->info['name'];
      $current_theme['version'] = isset($theme->info['version']) ? $theme->info['version'] : '';
      $current_theme['notes'] = $theme->notes;

      // Make sure to provide feedback on compatibility.
      $current_theme['incompatible'] = '';
      if (!empty($theme->incompatible_core)) {
        $current_theme['incompatible'] = t('This version is not compatible with Drupal !core_version and should be replaced.', array('!core_version' => \Drupal::CORE_COMPATIBILITY));
      }
      elseif (!empty($theme->incompatible_php)) {
        if (substr_count($theme->info['php'], '.') < 2) {
          $theme->info['php'] .= '.*';
        }
        $current_theme['incompatible'] = t('This theme requires PHP version @php_required and is incompatible with PHP version !php_version.', array('@php_required' => $theme->info['php'], '!php_version' => phpversion()));
      }
      elseif (!empty($theme->incompatible_base)) {
        $current_theme['incompatible'] = t('This theme requires the base theme @base_theme to operate correctly.', array('@base_theme' => $theme->info['base theme']));
      }
      elseif (!empty($theme->incompatible_engine)) {
        $current_theme['incompatible'] = t('This theme requires the theme engine @theme_engine to operate correctly.', array('@theme_engine' => $theme->info['engine']));
      }

      // Build operation links.
      $current_theme['operations'] = array(
        '#theme' => 'links',
        '#links' => $theme->operations,
        '#attributes' => array(
          'class' => array('operations', 'clearfix'),
        ),
      );
      $theme_group['themes'][] = $current_theme;
    }
    $groups[] = $theme_group;
  }
  $variables['theme_groups'] = $groups;
}

