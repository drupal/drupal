<?php

namespace Drupal\toolbar\Hook;

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
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme($existing, $type, $theme, $path) : array {
    $items['toolbar'] = ['render element' => 'element'];
    $items['menu__toolbar'] = [
      'base hook' => 'menu',
      'variables' => [
        'menu_name' => NULL,
        'items' => [],
        'attributes' => [],
      ],
    ];
    return $items;
  }

  /**
   * Implements hook_page_top().
   *
   * Add admin toolbar to the top of the page automatically.
   */
  #[Hook('page_top')]
  public function pageTop(array &$page_top): void {
    $page_top['toolbar'] = [
      '#type' => 'toolbar',
      '#access' => \Drupal::currentUser()->hasPermission('access toolbar'),
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
    return $items;
  }

}
