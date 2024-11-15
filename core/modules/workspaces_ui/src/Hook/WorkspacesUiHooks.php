<?php

declare(strict_types=1);

namespace Drupal\workspaces_ui\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;

/**
 * Hook implementations for the workspaces_ui module.
 */
class WorkspacesUiHooks {

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match): ?string {
    switch ($route_name) {
      // Main module help for the Workspaces UI module.
      case 'help.page.workspaces_ui':
        $output = '';
        $output .= '<h2>' . t('About') . '</h2>';
        $output .= '<p>' . t('The Workspaces UI module provides an interface for managing workspaces for the <a href=":workspaces_module">Workspaces module</a>. For more information, see the <a href=":workspaces">online documentation for the Workspaces UI module</a>.', [':workspaces_module' => Url::fromRoute('help.page', ['name' => 'workspaces'])->toString(), ':workspaces' => 'https://www.drupal.org/docs/8/core/modules/workspace/overview']) . '</p>';
        return $output;
    }
    return NULL;
  }

  /**
   * Implements hook_toolbar().
   */
  #[Hook('toolbar')]
  public function toolbar(): array {
    $items['workspace'] = [
      '#cache' => [
        'contexts' => [
          'user.permissions',
        ],
      ],
    ];
    $current_user = \Drupal::currentUser();
    if (!$current_user->hasPermission('administer workspaces')
      && !$current_user->hasPermission('view own workspace')
      && !$current_user->hasPermission('view any workspace')) {
      return $items;
    }

    /** @var \Drupal\workspaces\WorkspaceInterface $active_workspace */
    $active_workspace = \Drupal::service('workspaces.manager')->getActiveWorkspace();

    $items['workspace'] += [
      '#type' => 'toolbar_item',
      'tab' => [
        '#lazy_builder' => ['workspaces.lazy_builders:renderToolbarTab', []],
        '#create_placeholder' => TRUE,
        '#lazy_builder_preview' => [
          '#type' => 'link',
          '#title' => $active_workspace ? $active_workspace->label() : t('Live'),
          '#url' => Url::fromRoute('entity.workspace.collection'),
          '#attributes' => [
            'class' => ['toolbar-tray-lazy-placeholder-link'],
          ],
        ],
      ],
      '#wrapper_attributes' => [
        'class' => ['workspaces-toolbar-tab'],
      ],
      '#weight' => 500,
    ];

    // Add a special class to the wrapper if we don't have an active workspace so
    // we can highlight it with a different color.
    if (!$active_workspace) {
      $items['workspace']['#wrapper_attributes']['class'][] = 'workspaces-toolbar-tab--is-default';
    }

    // \Drupal\toolbar\Element\ToolbarItem::preRenderToolbarItem adds an
    // #attributes property to each toolbar item's tab child automatically.
    // Lazy builders don't support an #attributes property so we need to
    // add another render callback to remove the #attributes property. We start by
    // adding the defaults, and then we append our own pre render callback.
    $items['workspace'] += \Drupal::service('plugin.manager.element_info')->getInfo('toolbar_item');
    $items['workspace']['#pre_render'][] = 'workspaces.lazy_builders:removeTabAttributes';

    return $items;
  }

}
