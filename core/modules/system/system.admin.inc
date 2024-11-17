<?php

/**
 * @file
 */

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Link;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Markup;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;

/**
 * Prepares variables for administrative content block templates.
 *
 * Default template: admin-block-content.html.twig.
 *
 * @param $variables
 *   An associative array containing:
 *   - content: List of administrative menu items. Each menu item contains:
 *     - url: Path to the admin section.
 *     - title: Short name of the section.
 *     - description: Description of the administrative menu item.
 *     - options: URL options. See \Drupal\Core\Url::fromUri() for details.
 */
function template_preprocess_admin_block_content(&$variables): void {
  if (!empty($variables['content'])) {
    $variables['compact'] = system_admin_compact_mode();
    foreach ($variables['content'] as $key => $item) {
      $variables['content'][$key]['link'] = Link::fromTextAndUrl($item['title'], $item['url'])->toString();
      if (!$variables['compact'] && !empty($item['description'])) {
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
function template_preprocess_admin_page(&$variables): void {
  $variables['system_compact_link'] = [
    '#type' => 'system_compact_link',
  ];
  $variables['containers'] = [];
  $stripe = 0;
  foreach ($variables['blocks'] as $block) {
    if (!empty($block['content']['#content'])) {
      if (empty($block['position'])) {
        // Perform automatic striping.
        $block['position'] = ++$stripe % 2 ? 'left' : 'right';
      }
      $variables['containers'][$block['position']]['blocks'][] = [
        '#theme' => 'admin_block',
        '#block' => $block,
      ];
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
function template_preprocess_system_admin_index(&$variables): void {
  $variables['system_compact_link'] = [
    '#type' => 'system_compact_link',
  ];
  $variables['containers'] = [];
  $stripe = 0;
  // Iterate over all modules.
  foreach ($variables['menu_items'] as $module => $block) {
    [$description, $items] = $block;
    $position = ++$stripe % 2 ? 'left' : 'right';
    // Output links.
    if (count($items)) {
      $variables['containers'][$position][] = [
        '#theme' => 'admin_block',
        '#block' => [
          'position' => $position,
          'title' => $module,
          'content' => [
            '#theme' => 'admin_block_content',
            '#content' => $items,
          ],
          'description' => t($description),
        ],
      ];
    }
  }
}

/**
 * Prepares variables for the module details templates.
 *
 * Default template: system-modules-details.html.twig.
 *
 * @param $variables
 *   An associative array containing:
 *   - form: A render element representing the form. The main form element
 *     represents a package, and child elements of the form are individual
 *     projects. Each project (or module) is an associative array containing the
 *     following elements:
 *     - name: The name of the module.
 *     - enable: A checkbox for enabling the module.
 *     - description: A description of the module.
 *     - version: The version of the module.
 *     - links: Administration links provided by the module.
 *     - #requires: A list of modules that the project requires.
 *     - #required_by: A list of modules and themes that require the project.
 *     - #attributes: A list of attributes for the module wrapper.
 *
 * @see \Drupal\system\Form\ModulesListForm
 */
function template_preprocess_system_modules_details(&$variables): void {
  $form = $variables['form'];

  // Identify modules that are depended on by themes.
  // Added here instead of ModuleHandler to avoid recursion.
  $themes = \Drupal::service('extension.list.theme')->getList();
  foreach ($themes as $theme) {
    foreach ($theme->info['dependencies'] as $dependency) {
      if (isset($form[$dependency])) {
        // Add themes to the module's required by list.
        $form[$dependency]['#required_by'][] = $theme->status ? t('@theme', ['@theme (theme)' => $theme->info['name']]) : t('@theme (theme) (<span class="admin-disabled">disabled</span>)', ['@theme' => $theme->info['name']]);
      }
    }
  }

  $variables['modules'] = [];
  // Iterate through all the modules, which are children of this element.
  foreach (Element::children($form) as $key) {
    // Stick the key into $module for easier access.
    $module = $form[$key];
    unset($module['enable']['#title']);
    $module['#requires'] = array_filter($module['#requires']);
    $module['#required_by'] = array_filter($module['#required_by']);
    // Add the checkbox to allow installing new modules and to show the
    // installation status of the module.
    $module['checkbox'] = $module['enable'];

    // Add the module label and expand/collapse functionality.
    $id = Html::getUniqueId('module-' . $key);
    $module['id'] = $id;
    $module['enable_id'] = $module['enable']['#id'];

    // @todo Remove early rendering and use safe_join in the Twig template once
    //   https://www.drupal.org/node/2579091 is fixed.
    $renderer = \Drupal::service('renderer');
    $machine_name_render = [
      '#prefix' => '<span dir="ltr" class="table-filter-text-source">',
      '#plain_text' => $key,
      '#suffix' => '</span>',
    ];
    $module['machine_name'] = $renderer->render($machine_name_render);

    if (!empty($module['#requires'])) {
      $requires = [
        '#theme' => 'item_list',
        '#items' => $module['#requires'],
        '#context' => ['list_style' => 'comma-list'],
      ];
      $module['requires'] = $renderer->render($requires);
    }
    if (!empty($module['#required_by'])) {
      $required_by = [
        '#theme' => 'item_list',
        '#items' => $module['#required_by'],
        '#context' => ['list_style' => 'comma-list'],
      ];
      $module['required_by'] = $renderer->render($required_by);
    }

    if (!empty($module['version'])) {
      $module['version'] = $renderer->render($module['version']);
    }

    $module['attributes'] = new Attribute($module['#attributes']);
    $variables['modules'][] = $module;
  }
}

/**
 * Prepares variables for module uninstall templates.
 *
 * Default template: system-modules-uninstall.html.twig.
 *
 * @param $variables
 *   An associative array containing:
 *   - form: A render element representing the form. Child elements of the form
 *     are individual modules. Each module is an associative array containing
 *     the following elements:
 *     - #module_name: The name of the module as a string.
 *     - name: The name of the module in a renderable array.
 *     - description: A description of the module.
 *     - #required_by: (optional) A list of modules that require the module.
 *     - #validation_reasons: (optional) Additional reasons why the module
 *       cannot be uninstalled.
 *     - #attributes: A list of attributes for the module wrapper.
 *
 * @ingroup themeable
 */
function template_preprocess_system_modules_uninstall(&$variables): void {
  $form = $variables['form'];
  $variables['modules'] = [];

  // Iterate through all the modules, which are children of this element.
  foreach (Element::children($form['modules']) as $key) {
    $module = $form['modules'][$key];
    $module['module_name'] = $module['#module_name'];
    $module['checkbox'] = $form['uninstall'][$key];
    $module['checkbox_id'] = $form['uninstall'][$key]['#id'];

    if (!empty($module['#validation_reasons'])) {
      $module['validation_reasons'] = $module['#validation_reasons'];
      $module['reasons_count'] = count($module['validation_reasons']);
    }
    else {
      $module['reasons_count'] = 0;
    }
    if (!empty($module['#required_by'])) {
      $module['required_by'] = $module['#required_by'];
      $module['reasons_count'] = $module['reasons_count'] + 1;
    }
    $module['attributes'] = new Attribute($module['#attributes']);
    $variables['modules'][] = $module;
  }
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
function template_preprocess_system_themes_page(&$variables): void {
  $groups = [];
  $theme_groups = $variables['theme_groups'];
  $variables['attributes']['id'] = 'system-themes-page';

  foreach ($variables['theme_group_titles'] as $state => $title) {
    if (!count($theme_groups[$state])) {
      // Skip this group of themes if no theme is there.
      continue;
    }
    // Start new theme group.
    $theme_group = [];
    $theme_group['state'] = $state;
    $theme_group['title'] = $title;
    $theme_group['themes'] = [];
    $theme_group['attributes'] = new Attribute();

    foreach ($theme_groups[$state] as $theme) {
      $current_theme = [];

      // Screenshot depicting the theme.
      if ($theme->screenshot) {
        $current_theme['screenshot'] = [
          '#theme' => 'image',
          '#uri' => $theme->screenshot['uri'],
          '#alt' => $theme->screenshot['alt'],
          '#title' => $theme->screenshot['title'],
          '#attributes' => $theme->screenshot['attributes'],
        ];
      }
      else {
        $current_theme['screenshot'] = [
          '#theme' => 'image',
          '#uri' => \Drupal::service('extension.list.module')->getPath('system') . '/images/no_screenshot.png',
          '#alt' => t('No screenshot'),
          '#title' => t('No screenshot'),
          '#attributes' => new Attribute(['class' => ['no-screenshot']]),
        ];
      }

      // Localize the theme description.
      $current_theme['description'] = Markup::create(Xss::filterAdmin(t($theme->info['description'])));

      $current_theme['attributes'] = new Attribute();
      $current_theme['name'] = $theme->info['name'];
      $current_theme['version'] = $theme->info['version'] ?? '';
      $current_theme['notes'] = $theme->notes;
      $current_theme['is_default'] = $theme->is_default;
      $current_theme['is_admin'] = $theme->is_admin;

      $current_theme['module_dependencies'] = !empty($theme->module_dependencies_list) ? [
        '#theme' => 'item_list',
        '#items' => $theme->module_dependencies_list,
        '#context' => ['list_style' => 'comma-list'],
      ] : [];

      // Make sure to provide feedback on compatibility.
      $current_theme['incompatible'] = '';
      if (!empty($theme->info['core_incompatible'])) {
        $current_theme['incompatible'] = t("This theme is not compatible with Drupal @core_version. Check that the .info.yml file contains a compatible 'core' or 'core_version_requirement' value.", ['@core_version' => \Drupal::VERSION]);
      }
      elseif (!empty($theme->incompatible_region)) {
        $current_theme['incompatible'] = t("This theme is missing a 'content' region.");
      }
      elseif (!empty($theme->incompatible_php)) {
        if (substr_count($theme->info['php'], '.') < 2) {
          $theme->info['php'] .= '.*';
        }
        $current_theme['incompatible'] = t('This theme requires PHP version @php_required and is incompatible with PHP version @php_version.', ['@php_required' => $theme->info['php'], '@php_version' => phpversion()]);
      }
      elseif (!empty($theme->incompatible_base)) {
        $current_theme['incompatible'] = t('This theme requires the base theme @base_theme to operate correctly.', ['@base_theme' => $theme->info['base theme']]);
      }
      elseif (!empty($theme->incompatible_engine)) {
        $current_theme['incompatible'] = t('This theme requires the theme engine @theme_engine to operate correctly.', ['@theme_engine' => $theme->info['engine']]);
      }
      elseif (!empty($theme->incompatible_module)) {
        $current_theme['incompatible'] = t('This theme requires the listed modules to operate correctly.');
      }
      elseif (!empty($theme->module_dependencies_disabled)) {
        if (!empty($theme->insufficient_module_permissions)) {
          $current_theme['incompatible'] = t('This theme requires the listed modules to operate correctly. They must first be installed by a user with permissions to do so.');
        }
        else {
          $modules_url = (string) Url::fromRoute('system.modules_list')->toString();
          $current_theme['incompatible'] = t('This theme requires the listed modules to operate correctly. They must first be installed via the <a href=":modules_url">Extend page</a>.', [
            ':modules_url' => $modules_url,
          ]);
        }
      }

      // Build operation links.
      $current_theme['operations'] = [
        '#theme' => 'links',
        '#links' => $theme->operations,
        '#attributes' => [
          'class' => ['operations', 'clearfix'],
        ],
      ];
      $theme_group['themes'][] = $current_theme;
    }
    $groups[] = $theme_group;
  }
  $variables['theme_groups'] = $groups;
}
