<?php

namespace Drupal\toolbar\Hook;

use Drupal\announcements_feed\RenderCallbacks;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Hook\Attribute\HookDependsOnModule;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\Core\Routing\AdminContext;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\toolbar\Controller\ToolbarController;
use Drupal\Core\Url;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for toolbar.
 */
class ToolbarHooks {

  use StringTranslationTrait;

  public function __construct(
    protected ModuleHandlerInterface $moduleHandler,
    protected AccountInterface $currentUser,
    protected readonly ElementInfoManagerInterface $elementInfoManager,
    protected AdminContext $adminContext,
  ) {
  }

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match): ?string {
    switch ($route_name) {
      case 'help.page.toolbar':
        $output = '<h2>' . $this->t('About') . '</h2>';
        $output .= '<p>' . $this->t('The Toolbar module provides a toolbar for site administrators, which displays tabs and trays provided by the Toolbar module itself and other modules. For more information, see the <a href=":toolbar_docs">online documentation for the Toolbar module</a>.', [':toolbar_docs' => 'https://www.drupal.org/docs/8/core/modules/toolbar']) . '</p>';
        $output .= '<h4>' . $this->t('Terminology') . '</h4>';
        $output .= '<dl>';
        $output .= '<dt>' . $this->t('Tabs') . '</dt>';
        $output .= '<dd>' . $this->t('Tabs are buttons, displayed in a bar across the top of the screen. Some tabs execute an action (such as starting Edit mode), while other tabs toggle which tray is open.') . '</dd>';
        $output .= '<dt>' . $this->t('Trays') . '</dt>';
        $output .= '<dd>' . $this->t('Trays are usually lists of links, which can be hierarchical like a menu. If a tray has been toggled open, it is displayed either vertically or horizontally below the tab bar, depending on the browser width. Only one tray may be open at a time. If you click another tab, that tray will replace the tray being displayed. In wide browser widths, the user has the ability to toggle from vertical to horizontal, using a link at the bottom or right of the tray. Hierarchical menus only have open/close behavior in vertical mode; if you display a tray containing a hierarchical menu horizontally, only the top-level links will be available.') . '</dd>';
        $output .= '</dl>';
        return $output;
    }
    return NULL;
  }

  /**
   * Implements hook_page_top().
   *
   * Add admin toolbar to the top of the page automatically.
   */
  #[Hook('page_top')]
  public function pageTop(array &$page_top): void {
    // If navigation is enabled and the user has access to it, don't add the
    // toolbar.
    if ($this->currentUser->hasPermission('access navigation') && $this->moduleHandler->moduleExists('navigation')) {
      return;
    }
    $page_top['toolbar'] = [
      '#type' => 'toolbar',
      '#access' => $this->currentUser->hasPermission('access toolbar'),
      '#cache' => [
        'keys' => [
          'toolbar',
        ],
        'contexts' => [
          'user.permissions',
        ],
      ],
    ];
  }

  /**
   * Implements hook_toolbar().
   */
  #[Hook('toolbar')]
  public function toolbar(): array {
    // The 'Home' tab is a simple link, with no corresponding tray.
    $items['home'] = [
      '#type' => 'toolbar_item',
      'tab' => [
        '#type' => 'link',
        '#title' => $this->t('Back to site'),
        '#url' => Url::fromRoute('<front>'),
        '#attributes' => [
          'title' => $this->t('Return to site content'),
          'class' => [
            'toolbar-icon',
            'toolbar-icon-escape-admin',
          ],
          'data-toolbar-escape-admin' => TRUE,
        ],
      ],
      '#wrapper_attributes' => [
        'class' => [
          'home-toolbar-tab',
        ],
      ],
      '#attached' => [
        'library' => [
          'toolbar/toolbar.escapeAdmin',
        ],
      ],
      '#weight' => -20,
    ];
    // To conserve bandwidth, we only include the top-level links in the HTML.
    // The subtrees are fetched through a JSONP script that is generated at the
    // toolbar_subtrees route. We provide the JavaScript requesting that JSONP
    // script here with the hash parameter that is needed for that route.
    // @see toolbar_subtrees_jsonp()
    [$hash, $hash_cacheability] = _toolbar_get_subtrees_hash();
    $subtrees_attached['drupalSettings']['toolbar'] = ['subtreesHash' => $hash];
    // The administration element has a link that is themed to correspond to a
    // toolbar tray. The tray contains the full administrative menu of the site.
    $items['administration'] = [
      '#type' => 'toolbar_item',
      'tab' => [
        '#type' => 'link',
        '#title' => $this->t('Manage'),
        '#url' => Url::fromRoute('system.admin'),
        '#attributes' => [
          'title' => $this->t('Admin menu'),
          'class' => [
            'toolbar-icon',
            'toolbar-icon-menu',
          ],
                  // A data attribute that indicates to the client to defer
                  // loading of the admin menu subtrees until this tab is
                  // activated. Admin menu subtrees will not render to the DOM
                  // if this attribute is removed. The value of the attribute is
                  // intentionally left blank. Only the presence of the
                  // attribute is necessary.
          'data-drupal-subtrees' => '',
        ],
      ],
      'tray' => [
        '#heading' => $this->t('Administration menu'),
        '#attached' => $subtrees_attached,
        'toolbar_administration' => [
          '#pre_render' => [
            [
              ToolbarController::class,
              'preRenderAdministrationTray',
            ],
          ],
          '#type' => 'container',
          '#attributes' => [
            'class' => [
              'toolbar-menu-administration',
            ],
          ],
        ],
      ],
      '#weight' => -15,
    ];
    $hash_cacheability->applyTo($items['administration']);

    if ($this->moduleHandler->moduleExists('announcements_feed')) {
      $items += $this->announcementsFeedToolbar();
    }
    if ($this->moduleHandler->moduleExists('contextual')) {
      $items += $this->contextualToolbar();
    }
    if ($this->moduleHandler->moduleExists('demo_umami')) {
      $items += $this->demoUmamiToolbar();
    }
    if ($this->moduleHandler->moduleExists('user')) {
      $items += $this->userToolbar();
    }
    if ($this->moduleHandler->moduleExists('workspaces_ui')) {
      $items += $this->workspacesUiToolbar();
    }

    return $items;
  }

  protected function announcementsFeedToolbar(): array {
    if (!$this->currentUser->hasPermission('access announcements')) {
      return ['#cache' => ['contexts' => ['user.permissions']]];
    }
    $items['announcement'] = [
      '#type' => 'toolbar_item',
      'tab' => [
        '#lazy_builder' => [
          'announcements_feed.lazy_builders:renderAnnouncements',
          [],
        ],
        '#create_placeholder' => TRUE,
        '#cache' => [
          'tags' => [
            'announcements_feed:feed',
          ],
        ],
      ],
      '#wrapper_attributes' => [
        'class' => [
          'announce-toolbar-tab',
        ],
      ],
      '#cache' => [
        'contexts' => [
          'user.permissions',
        ],
      ],
      '#weight' => 3399,
    ];
    // \Drupal\toolbar\Element\ToolbarItem::preRenderToolbarItem adds an
    // #attributes property to each toolbar item's tab child automatically. Lazy
    // builders don't support an #attributes property so we need to add another
    // render callback to remove the #attributes property. We start by adding
    // the defaults, and then we append our own pre render callback.
    $items['announcement'] += $this->elementInfoManager->getInfo('toolbar_item');
    $items['announcement']['#pre_render'][] = [RenderCallbacks::class, 'removeTabAttributes'];
    return $items;
  }

  protected function contextualToolbar(): array {
    $items = [];
    $items['contextual'] = ['#cache' => ['contexts' => ['user.permissions']]];
    if (!\Drupal::currentUser()->hasPermission('access contextual links')) {
      return $items;
    }
    $items['contextual'] += [
      '#type' => 'toolbar_item',
      'tab' => [
        '#type' => 'html_tag',
        '#tag' => 'button',
        '#value' => $this->t('Edit'),
        '#attributes' => [
          'class' => [
            'toolbar-icon',
            'toolbar-icon-edit',
          ],
          'aria-pressed' => 'false',
          'type' => 'button',
        ],
      ],
      '#wrapper_attributes' => [
        'class' => [
          'hidden',
          'contextual-toolbar-tab',
        ],
      ],
      '#attached' => [
        'library' => [
          'contextual/drupal.contextual-toolbar',
        ],
      ],
    ];
    return $items;
  }

  protected function demoUmamiToolbar(): array {
    // Add a warning about using an experimental profile.
    // @todo This can be removed once a generic warning for experimental
    //   profiles has been introduced in
    //   https://www.drupal.org/project/drupal/issues/2934374
    $items['experimental-profile-warning'] = [
      '#weight' => 3400,
      '#cache' => [
        'contexts' => ['route'],
      ],
    ];

    // Show warning only on administration pages.
    if ($this->adminContext->isAdminRoute()) {
      $link_to_help_page = $this->moduleHandler->moduleExists('help') && $this->currentUser->hasPermission('access help pages');
      $items['experimental-profile-warning']['#type'] = 'toolbar_item';
      $items['experimental-profile-warning']['tab'] = [
        '#type' => 'inline_template',
        '#template' => '<a class="toolbar-warning" href="{{ more_info_link }}">This site is intended for demonstration purposes.</a>',
        '#context' => [
          // Link directly to the drupal.org documentation if the help pages
          // aren't available.
          'more_info_link' => $link_to_help_page ? Url::fromRoute('help.page', ['name' => 'demo_umami'])
            : 'https://www.drupal.org/node/2941833',
        ],
        '#attached' => [
          'library' => ['demo_umami/toolbar-warning'],
        ],
      ];
    }
    return $items;
  }

  protected function userToolbar(): array {
    $user = \Drupal::currentUser();
    $items['user'] = [
      '#type' => 'toolbar_item',
      'tab' => [
        '#type' => 'link',
        '#title' => $user->getDisplayName(),
        '#url' => Url::fromRoute('user.page'),
        '#attributes' => [
          'title' => $this->t('My account'),
          'class' => [
            'toolbar-icon',
            'toolbar-icon-user',
          ],
        ],
        '#cache' => [
          // Vary cache for anonymous and authenticated users.
          'contexts' => [
            'user.roles:anonymous',
          ],
        ],
      ],
      'tray' => [
        '#heading' => $this->t('User account actions'),
      ],
      '#weight' => 100,
      '#attached' => [
        'library' => [
          'user/drupal.user.icons',
        ],
      ],
    ];
    if ($user->isAnonymous()) {
      $links = [
        'login' => [
          'title' => $this->t('Log in'),
          'url' => Url::fromRoute('user.page'),
        ],
      ];
      $items['user']['tray']['user_links'] = [
        '#theme' => 'links__toolbar_user',
        '#links' => $links,
        '#attributes' => [
          'class' => [
            'toolbar-menu',
          ],
        ],
      ];
    }
    else {
      $items['user']['tab']['#title'] = [
        '#lazy_builder' => [
          'user.toolbar_link_builder:renderDisplayName',
          [],
        ],
        '#create_placeholder' => TRUE,
        '#lazy_builder_preview' => [
          // Add a line of whitespace to the placeholder to ensure the icon is
          // positioned in the same place it will be when the lazy loaded
          // content appears.
          '#markup' => '&nbsp;',
        ],
      ];
      $items['user']['tray']['user_links'] = [
        '#lazy_builder' => [
          'user.toolbar_link_builder:renderToolbarLinks',
          [],
        ],
        '#create_placeholder' => TRUE,
        '#lazy_builder_preview' => [
          '#markup' => '<a href="#" class="toolbar-tray-lazy-placeholder-link">&nbsp;</a>',
        ],
      ];
    }
    return $items;
  }

  protected function workspacesUiToolbar(): array {
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
        '#lazy_builder' => ['workspaces_ui.lazy_builders:renderToolbarTab', []],
        '#create_placeholder' => TRUE,
        '#lazy_builder_preview' => [
          '#type' => 'link',
          '#title' => $active_workspace ? $active_workspace->label() : $this->t('Live'),
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

    // Add a special class to the wrapper if we don't have an active workspace
    // so we can highlight it with a different color.
    if (!$active_workspace) {
      $items['workspace']['#wrapper_attributes']['class'][] = 'workspaces-toolbar-tab--is-default';
    }

    // \Drupal\toolbar\Element\ToolbarItem::preRenderToolbarItem adds an
    // #attributes property to each toolbar item's tab child automatically. Lazy
    // builders don't support an #attributes property so we need to add another
    // render callback to remove the #attributes property. We start by adding
    // the defaults, and then we append our own pre render callback.
    $items['workspace'] += \Drupal::service('plugin.manager.element_info')->getInfo('toolbar_item');
    $items['workspace']['#pre_render'][] = 'workspaces_ui.lazy_builders:removeTabAttributes';

    return $items;
  }

  /**
   * Implements hook_toolbar_alter().
   */
  #[Hook('toolbar_alter')]
  #[HookDependsOnModule('announcements_feed')]
  public function toolbarAlter(&$items): void {
    // As the "Announcements" link is shown already in the top toolbar bar, we
    // don't need it again in the administration menu tray, so hide it.
    if (!empty($items['administration']['tray'])) {
      $callable = function (array $element) {
        unset($element['administration_menu']['#items']['announcements_feed.announcement']);
        return $element;
      };
      $items['administration']['tray']['toolbar_administration']['#pre_render'][] = $callable;
    }
  }

}
