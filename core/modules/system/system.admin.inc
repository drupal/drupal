<?php

/**
 * @file
 * Admin page callbacks for the system module.
 */

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Utility\Xss;
use Drupal\Component\Utility\Html;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Render\Element;
use Drupal\Core\Template\Attribute;

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
 */
function template_preprocess_admin_block_content(&$variables) {
  if (!empty($variables['content'])) {
    $variables['compact'] = system_admin_compact_mode();
    foreach ($variables['content'] as $key => $item) {
      $variables['content'][$key]['link'] = \Drupal::l($item['title'], $item['url']);
      if (!$variables['compact'] && isset($item['description'])) {
        $variables['content'][$key]['description'] = ['#markup' => $item['description']];
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
 */
function template_preprocess_admin_page(&$variables) {
  $variables['system_compact_link'] = array(
    '#type' => 'system_compact_link',
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
 * Prepares variables for admin index templates.
 *
 * Default template: system-admin-index.html.twig.
 *
 * @param $variables
 *   An associative array containing:
 *   - menu_items: An array of modules to be displayed.
 */
function template_preprocess_system_admin_index(&$variables) {
  $variables['system_compact_link'] = array(
    '#type' => 'system_compact_link',
  );
  $variables['containers'] = array();
  $stripe = 0;
  // Iterate over all modules.
  foreach ($variables['menu_items'] as $module => $block) {
    list($description, $items) = $block;
    $position = ++$stripe % 2 ? 'left' : 'right';
    // Output links.
    if (count($items)) {
      $variables['containers'][$position][] = array(
        '#theme' => 'admin_block',
        '#block' => array(
          'position' => $position,
          'title' => $module,
          'content' => array(
            '#theme' => 'admin_block_content',
            '#content' => $items,
          ),
          'description' => t($description),
        ),
      );
    }
  }
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
 */
function template_preprocess_status_report(&$variables) {
  $severities = array(
    REQUIREMENT_INFO => array(
      'title' => t('Info'),
      'status' => 'info',
    ),
    REQUIREMENT_OK => array(
      'title' => t('OK'),
      'status' => 'ok',
    ),
    REQUIREMENT_WARNING => array(
      'title' => t('Warning'),
      'status' => 'warning',
    ),
    REQUIREMENT_ERROR => array(
      'title' => t('Error'),
      'status' => 'error',
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
    $variables['requirements'][$i]['severity_title'] = $severity['title'];
    $variables['requirements'][$i]['severity_status'] = $severity['status'];
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
  foreach (Element::children($form) as $key) {
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

    // Add the module label and expand/collapse functionality.
    $id = Html::getUniqueId('module-' . $key);
    $col2 = '<label id="' . $id . '" for="' . $module['enable']['#id'] . '" class="module-name table-filter-text-source">' . drupal_render($module['name']) . '</label>';
    $row[] = array('class' => array('module'), 'data' => SafeMarkup::set($col2));

    // Add the description, along with any modules it requires.
    $description = '';
    $description .= '<div class="requirements">';
    $description .= '<div class="admin-requirements">' . t('Machine name: !machine-name', array('!machine-name' => '<span dir="ltr" class="table-filter-text-source">' . $key . '</span>')) . '</div>';
    if ($version || $requires || $required_by) {
      if ($version) {
        $description .= '<div class="admin-requirements">' . t('Version: !module-version', array('!module-version' => drupal_render($module['version']))) . '</div>';
      }
      if ($requires) {
        $description .= '<div class="admin-requirements">' . t('Requires: !module-list', array('!module-list' => implode(', ', $module['#requires']))) . '</div>';
      }
      if ($required_by) {
        $description .= '<div class="admin-requirements">' . t('Required by: !module-list', array('!module-list' => implode(', ', $module['#required_by']))) . '</div>';
      }
    }
    $description .= '</div>';
    $links = '';
    foreach (array('help', 'permissions', 'configure') as $link_type) {
      $links .= drupal_render($module['links'][$link_type]);
    }
    if ($links) {
      $description .= '  <div class="links">';
      $description .= $links;
      $description .= '</div>';
    }
    $details = array(
      '#type' => 'details',
      '#title' => SafeMarkup::set('<span class="text module-description">' . drupal_render($module['description']) . '</span>'),
      '#attributes' => array('id' => $module['enable']['#id'] . '-description'),
      '#description' => $description,
    );
    $col4 = drupal_render($details);
    $row[] = array('class' => array('description', 'expand'), 'data' => $col4);

    $rows[] = $module['#attributes'] + array('data' => $row);
  }

  $table = array(
    '#type' => 'table',
    '#header' => $form['#header'],
    '#rows' => $rows,
  );
  return drupal_render($table);
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
  foreach (Element::children($form['modules']) as $module) {
    $disabled_header = '';
    $disabled_reasons = '';
    // Add the modules requiring the module in question as a validation reason.
    if (!empty($form['modules'][$module]['#required_by'])) {
      $form['modules'][$module]['#validation_reasons'][] = \Drupal::translation()->translate('Required by: @modules', array('@modules' => implode(', ',$form['modules'][$module]['#required_by'])));
    }
    if (!empty($form['modules'][$module]['#validation_reasons'])) {
      $disabled_reasons = [
        '#theme' => 'item_list',
        '#items' => $form['modules'][$module]['#validation_reasons'],
      ];
      $disabled_reasons = drupal_render($disabled_reasons);
      $disabled_header = \Drupal::translation()->formatPlural(count($form['modules'][$module]['#validation_reasons']),
        'The following reason prevents @module from being uninstalled:',
        'The following reasons prevents @module from being uninstalled:',
        array('@module' => $form['modules'][$module]['#module_name']));
    }
    $rows[] = array(
      array('data' => drupal_render($form['uninstall'][$module]), 'align' => 'center'),
      array(
        'data' => array(
          '#type' => 'inline_template',
          '#template' => '<label for="{{ module_id }}" class="module-name table-filter-text-source">{{ module_name }}</label>',
          '#context' => array('module_id' => $form['uninstall'][$module]['#id'], 'module_name' => drupal_render($form['modules'][$module]['name'])),
        )
      ),
      array(
        'data' => array(
          '#type' => 'inline_template',
          '#template' => '<span class="text module-description">{{ module_description }}</span>{% if disabled_header is not empty %}<div class="admin-requirements">{{ disabled_header }}{{ disabled_reasons }}</div>{% endif %}',
          '#context' => array(
            'module_description' => drupal_render($form['modules'][$module]['description']),
            'disabled_header' => $disabled_header,
            'disabled_reasons' => $disabled_reasons,
          ),
        ),
        'class' => array('description'),
      ),
    );
  }

  $table = array(
    '#type' => 'table',
    '#header' => $header,
    '#rows' => $rows,
    '#empty' => t('No modules are available to uninstall.'),
  );
  $output = drupal_render($form['filters']);
  $output .= drupal_render($table);
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
    $theme_group['attributes'] = new Attribute();

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
        $current_theme['screenshot'] = array(
          '#theme' => 'image',
          '#uri' => drupal_get_path('module', 'system') . '/images/no_screenshot.png',
          '#alt' => t('No screenshot'),
          '#title' => t('No screenshot'),
          '#attributes' => new Attribute(array('class' => array('no-screenshot'))),
        );
      }

      // Localize the theme description.
      $current_theme['description'] = t($theme->info['description']);

      $current_theme['attributes'] = new Attribute();
      $current_theme['name'] = $theme->info['name'];
      $current_theme['version'] = isset($theme->info['version']) ? $theme->info['version'] : '';
      $current_theme['notes'] = $theme->notes;
      $current_theme['is_default'] = $theme->is_default;
      $current_theme['is_admin'] = $theme->is_admin;

      // Make sure to provide feedback on compatibility.
      $current_theme['incompatible'] = '';
      if (!empty($theme->incompatible_core)) {
        $current_theme['incompatible'] = t("This theme is not compatible with Drupal @core_version. Check that the .info.yml file contains the correct 'core' value.", ['@core_version' => \Drupal::CORE_COMPATIBILITY]);
      }
      elseif (!empty($theme->incompatible_region)) {
        $current_theme['incompatible'] = t("This theme is missing a 'content' region.");
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

