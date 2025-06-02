<?php

namespace Drupal\navigation\Hook;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Config\Action\ConfigActionManager;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Hook\Attribute\RemoveHook;
use Drupal\Core\Hook\Order\Order;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\layout_builder\Hook\LayoutBuilderHooks;
use Drupal\navigation\NavigationContentLinks;
use Drupal\navigation\NavigationRenderer;
use Drupal\navigation\Plugin\SectionStorage\NavigationSectionStorage;
use Drupal\navigation\RenderCallbacks;
use Drupal\navigation\TopBarItemManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Hook implementations for navigation.
 */
class NavigationHooks {

  use StringTranslationTrait;

  /**
   * NavigationHooks constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current user.
   * @param \Drupal\navigation\NavigationRenderer $navigationRenderer
   *   The navigation renderer.
   * @param \Drupal\Core\Config\Action\ConfigActionManager $configActionManager
   *   The config action manager.
   * @param \Drupal\navigation\TopBarItemManagerInterface $topBarItemManager
   *   The Top Bar Item manager.
   */
  public function __construct(
    protected ModuleHandlerInterface $moduleHandler,
    protected AccountInterface $currentUser,
    protected NavigationRenderer $navigationRenderer,
    #[Autowire('@plugin.manager.config_action')]
    protected ConfigActionManager $configActionManager,
    protected TopBarItemManagerInterface $topBarItemManager,
  ) {
  }

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  #[RemoveHook('help', class: LayoutBuilderHooks::class, method: 'help')]
  public function help($route_name, RouteMatchInterface $route_match): ?string {
    switch ($route_name) {
      case 'help.page.navigation':
        $output = '';
        $output .= '<h3>' . $this->t('About') . '</h3>';
        $output .= '<p>' . $this->t('The Navigation module provides a left-aligned, collapsible, vertical sidebar navigation.') . '</p>';
        $output .= '<p>' . $this->t('For more information, see the <a href=":docs">online documentation for the Navigation module</a>.', [':docs' => 'https://www.drupal.org/docs/develop/core-modules-and-themes/core-modules/navigation-module']) . '</p>';
        return $output;
    }
    $configuration_route = 'layout_builder.navigation.';
    if (!$route_match->getRouteObject()->getOption('_layout_builder') || !str_starts_with($route_name, $configuration_route)) {
      return $this->moduleHandler->invoke('layout_builder', 'help', [$route_name, $route_match]);
    }
    if (str_starts_with($route_name, $configuration_route)) {
      $output = '<p>' . $this->t('This layout builder tool allows you to configure the blocks in the navigation toolbar.') . '</p>';
      $output .= '<p>' . $this->t('Forms and links inside the content of the layout builder tool are disabled in Edit mode.') . '</p>';
      return $output;
    }
    return NULL;
  }

  /**
   * Implements hook_page_top().
   */
  #[Hook('page_top', order: Order::Last)]
  public function pageTop(array &$page_top): void {
    if (!$this->currentUser->hasPermission('access navigation')) {
      return;
    }
    $this->navigationRenderer->removeToolbar($page_top);
    $this->navigationRenderer->buildNavigation($page_top);
    $this->navigationRenderer->buildTopBar($page_top);
  }

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme($existing, $type, $theme, $path) : array {
    $items['top_bar'] = ['render element' => 'element'];
    $items['top_bar_page_actions'] = ['variables' => ['page_actions' => [], 'featured_page_actions' => []]];
    $items['top_bar_page_action'] = ['variables' => ['link' => []]];
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
    $items['navigation_content_top'] = [
      'variables' => [
        'items' => [],
      ],
    ];
    $items['navigation__messages'] = [
      'variables' => [
        'message_list' => NULL,
      ],
    ];
    $items['navigation__message'] = [
      'variables' => [
        'attributes' => [],
        'url' => NULL,
        'content' => NULL,
        'type' => 'status',
      ],
    ];
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
  }

  /**
   * Implements hook_block_build_BASE_BLOCK_ID_alter().
   */
  #[Hook('block_build_local_tasks_block_alter')]
  public function blockBuildLocalTasksBlockAlter(array &$build, BlockPluginInterface $block): void {
    if ($this->currentUser->hasPermission('access navigation') &&
      array_key_exists('page_actions', $this->topBarItemManager->getDefinitions())
    ) {
      $this->navigationRenderer->removeLocalTasks($build, $block);
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

      // Add the allow_in_navigation attribute to those blocks valid for
      // Navigation.
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

  /**
   * Implements hook_navigation_content_top().
   */
  #[Hook('navigation_content_top')]
  public function navigationWorkspaces(): array {
    // This navigation item requires the Workspaces UI module.
    if (!\Drupal::moduleHandler()->moduleExists('workspaces_ui')) {
      return [];
    }

    $current_user = \Drupal::currentUser();
    if (!$current_user->hasPermission('administer workspaces')
      && !$current_user->hasPermission('view own workspace')
      && !$current_user->hasPermission('view any workspace')
    ) {
      return [];
    }

    return [
      'workspace' => [
        // @phpstan-ignore-next-line
        '#lazy_builder' => ['navigation.workspaces_lazy_builders:renderNavigationLinks', []],
        '#create_placeholder' => TRUE,
        '#lazy_builder_preview' => [
          '#type' => 'component',
          '#component' => 'navigation:toolbar-button',
          '#props' => [
            'html_tag' => 'a',
            'text' => $this->t('Workspace'),
          ],
        ],
        '#weight' => -1000,
      ],
    ];
  }

  /**
   * Implements hook_modules_installed().
   */
  #[Hook('modules_installed')]
  public function modulesInstalled(array $modules, bool $is_syncing): void {
    // Do not modify config during sync. Config should be already consolidated.
    if ($is_syncing) {
      return;
    }
    foreach ($modules as $module) {
      $blocks = $this->moduleHandler->invoke($module, 'navigation_defaults');

      if (!is_array($blocks)) {
        return;
      }

      foreach ($blocks as $block) {
        $this->configActionManager->applyAction('addNavigationBlock', 'navigation.block_layout', $block);
      }
    }
  }

  /**
   * Implements hook_navigation_menu_link_tree_alter().
   */
  #[Hook('navigation_menu_link_tree_alter')]
  public function navigationMenuLinkTreeAlter(array &$tree): void {
    foreach ($tree as $key => $item) {
      // Skip elements where menu is not the 'admin' one.
      $menu_name = $item->link->getMenuName();
      if ($menu_name != 'admin') {
        continue;
      }

      // Remove unwanted Help and Content menu links.
      $plugin_id = $item->link->getPluginId();
      if ($plugin_id == 'help.main' || $plugin_id == 'system.admin_content') {
        unset($tree[$key]);
      }
    }
  }

}
