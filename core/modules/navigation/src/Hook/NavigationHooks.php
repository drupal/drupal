<?php

namespace Drupal\navigation\Hook;

use Drupal\navigation\RenderCallbacks;
use Drupal\Component\Plugin\PluginBase;
use Drupal\navigation\Plugin\SectionStorage\NavigationSectionStorage;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\navigation\NavigationContentLinks;
use Drupal\navigation\NavigationRenderer;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\navigation\TopBarItemManagerInterface;

/**
 * Hook implementations for navigation.
 */
class NavigationHooks {

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match) {
    switch ($route_name) {
      case 'help.page.navigation':
        $output = '';
        $output .= '<h3>' . t('About') . '</h3>';
        $output .= '<p>' . t('The Navigation module provides a left-aligned, collapsible, vertical sidebar navigation.') . '</p>';
        $output .= '<p>' . t('For more information, see the <a href=":docs">online documentation for the Navigation module</a>.', [':docs' => 'https://www.drupal.org/project/navigation']) . '</p>';
        return $output;
    }
    $configuration_route = 'layout_builder.navigation.';
    if (!$route_match->getRouteObject()->getOption('_layout_builder') || !str_starts_with($route_name, $configuration_route)) {
      return \Drupal::moduleHandler()->invoke('layout_builder', 'help', [$route_name, $route_match]);
    }
    if (str_starts_with($route_name, $configuration_route)) {
      $output = '<p>' . t('This layout builder tool allows you to configure the blocks in the navigation toolbar.') . '</p>';
      $output .= '<p>' . t('Forms and links inside the content of the layout builder tool have been disabled.') . '</p>';
      return $output;
    }
  }

  /**
   * Implements hook_page_top().
   */
  #[Hook('page_top')]
  public function pageTop(array &$page_top): void {
    if (!\Drupal::currentUser()->hasPermission('access navigation')) {
      return;
    }
    $navigation_renderer = \Drupal::service('navigation.renderer');
    assert($navigation_renderer instanceof NavigationRenderer);
    $navigation_renderer->removeToolbar($page_top);
    if (\Drupal::routeMatch()->getRouteName() !== 'layout_builder.navigation.view') {
      // Don't render the admin toolbar if in layout edit mode.
      $navigation_renderer->buildNavigation($page_top);
      $navigation_renderer->buildTopBar($page_top);
      return;
    }
    // But if in layout mode, add an empty element to leave space. We need to use
    // an empty .admin-toolbar element because the css uses the adjacent sibling
    // selector. The actual rendering of the navigation blocks/layout occurs in
    // the layout form.
    $page_top['navigation'] = [
      '#type' => 'html_tag',
      '#tag' => 'aside',
      '#attributes' => [
        'class' => 'admin-toolbar',
      ],
    ];
    $navigation_renderer->buildTopBar($page_top);
  }

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme($existing, $type, $theme, $path) : array {
    $items['top_bar'] = ['render element' => 'element'];
    $items['top_bar_local_tasks'] = ['variables' => ['local_tasks' => []]];
    $items['top_bar_local_task'] = ['variables' => ['link' => []]];
    $items['big_pipe_interface_preview__navigation_shortcut_lazy_builder_lazyLinks__Shortcuts'] = [
      'variables' => [
        'callback' => NULL,
        'arguments' => NULL,
        'preview' => NULL,
      ],
      'base hook' => 'big_pipe_interface_preview',
    ];
    $items['block__navigation'] = ['render element' => 'elements', 'base hook' => 'block'];
    $items['navigation_menu'] = [
      'base hook' => 'menu',
      'variables' => [
        'menu_name' => NULL,
        'title' => NULL,
        'items' => [],
        'attributes' => [],
      ],
    ];
    $items['menu_region__footer'] = ['variables' => ['items' => [], 'title' => NULL, 'menu_name' => NULL]];
    return $items;
  }

  /**
   * Implements hook_menu_links_discovered_alter().
   */
  #[Hook('menu_links_discovered_alter')]
  public function menuLinksDiscoveredAlter(&$links): void {
    $navigation_links = \Drupal::classResolver(NavigationContentLinks::class);
    assert($navigation_links instanceof NavigationContentLinks);
    $navigation_links->addMenuLinks($links);
    $navigation_links->removeAdminContentLink($links);
    $navigation_links->removeHelpLink($links);
  }

  /**
   * Implements hook_block_build_BASE_BLOCK_ID_alter().
   */
  #[Hook('block_build_local_tasks_block_alter')]
  public function blockBuildLocalTasksBlockAlter(array &$build, BlockPluginInterface $block): void {
    $navigation_renderer = \Drupal::service('navigation.renderer');
    assert($navigation_renderer instanceof NavigationRenderer);
    if (\Drupal::currentUser()->hasPermission('access navigation') &&
      array_key_exists('page_actions', \Drupal::service(TopBarItemManagerInterface::class)->getDefinitions())
    ) {
      $navigation_renderer->removeLocalTasks($build, $block);
    }
  }

  /**
   * Implements hook_plugin_filter_TYPE__CONSUMER_alter().
   *
   * Curate the blocks available in the Layout Builder "Add Block" UI.
   */
  #[Hook('plugin_filter_block__layout_builder_alter')]
  public function pluginFilterBlockLayoutBuilderAlter(array &$definitions, array $extra): void {
    if (($extra['section_storage'] ?? NULL) instanceof NavigationSectionStorage) {
      // Include only blocks explicitly indicated as Navigation allowed.
      $definitions = array_filter($definitions,
        fn (array $definition): bool => ($definition['allow_in_navigation'] ?? FALSE) === TRUE
      );
    }
  }

  /**
   * Implements hook_plugin_filter_TYPE__CONSUMER_alter().
   */
  #[Hook('plugin_filter_layout__layout_builder_alter')]
  public function pluginFilterLayoutLayoutBuilderAlter(array &$definitions, array $extra): void {
    if (($extra['section_storage'] ?? NULL) instanceof NavigationSectionStorage) {
      // We don't allow adding a new section.
      $definitions = [];
    }
  }

  /**
   * Implements hook_block_alter().
   */
  #[Hook('block_alter')]
  public function blockAlter(&$definitions) : void {
    array_walk($definitions, function (&$definition, $block_id) {
      [$base_plugin_id] = explode(PluginBase::DERIVATIVE_SEPARATOR, $block_id);

      // Add the allow_in_navigation attribute to those blocks valid for Navigation.
      // @todo Refactor to use actual block Attribute once
      //   https://www.drupal.org/project/drupal/issues/3443882 is merged.
      $allow_in_navigation = [
        'navigation_user',
        'navigation_shortcuts',
        'navigation_menu',
      ];
      if (in_array($base_plugin_id, $allow_in_navigation, TRUE)) {
        $definition['allow_in_navigation'] = TRUE;
      }

      // Hide Navigation specific blocks from the generic UI.
      $hidden = ['navigation_user', 'navigation_shortcuts', 'navigation_menu', 'navigation_link'];
      if (in_array($base_plugin_id, $hidden, TRUE)) {
        $definition['_block_ui_hidden'] = TRUE;
      }
    });
  }

  /**
   * Implements hook_element_info_alter().
   */
  #[Hook('element_info_alter')]
  public function elementInfoAlter(array &$info): void {
    if (array_key_exists('layout_builder', $info)) {
      $info['layout_builder']['#pre_render'][] = [RenderCallbacks::class, 'alterLayoutBuilder'];
    }
  }

}
