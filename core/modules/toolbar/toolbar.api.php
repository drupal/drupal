<?php

/**
 * @file
 * Hooks provided by the toolbar module.
 */

use Drupal\Core\Url;

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Add items to the toolbar menu.
 *
 * The toolbar is a container for administrative and site-global interactive
 * components.
 *
 * The toolbar provides a common styling for items denoted by the
 * .toolbar-tab class.
 *
 * The toolbar provides a construct called a 'tray'. The tray is a container
 * for content. The tray may be associated with a toggle in the administration
 * bar. The toggle shows or hides the tray and is optimized for small and
 * large screens. To create this association, hook_toolbar() returns one or
 * more render elements of type 'toolbar_item', containing the toggle and tray
 * elements in its 'tab' and 'tray' properties.
 *
 * The following properties are available:
 *   - 'tab': A renderable array.
 *   - 'tray': Optional. A renderable array.
 *   - '#weight': Optional. Integer weight used for sorting toolbar items in
 *     administration bar area.
 *
 * This hook is invoked in Toolbar::preRenderToolbar().
 *
 * @return array
 *   An array of toolbar items, keyed by unique identifiers such as 'home' or
 *   'administration', or the short name of the module implementing the hook.
 *   The corresponding value is a render element of type 'toolbar_item'.
 *
 * @see \Drupal\toolbar\Element\Toolbar::preRenderToolbar()
 * @ingroup toolbar_tabs
 */
function hook_toolbar() {
  $items = [];

  // Add a search field to the toolbar. The search field employs no toolbar
  // module theming functions.
  $items['global_search'] = [
    '#type' => 'toolbar_item',
    'tab' => [
      '#type' => 'search',
      '#attributes' => [
        'placeholder' => t('Search the site'),
        'class' => ['search-global'],
      ],
    ],
    '#weight' => 200,
    // Custom CSS, JS or a library can be associated with the toolbar item.
    '#attached' => [
      'library' => [
        'search/global',
      ],
    ],
  ];

  // The 'Home' tab is a simple link, which is wrapped in markup associated
  // with a visual tab styling.
  $items['home'] = [
    '#type' => 'toolbar_item',
    'tab' => [
      '#type' => 'link',
      '#title' => t('Home'),
      '#url' => Url::fromRoute('<front>'),
      '#options' => [
        'attributes' => [
          'title' => t('Home page'),
          'class' => ['toolbar-icon', 'toolbar-icon-home'],
        ],
      ],
    ],
    '#weight' => -20,
  ];

  // A tray may be associated with a tab.
  //
  // When the tab is activated, the tray will become visible, either in a
  // horizontal or vertical orientation on the screen.
  //
  // The tray should contain a renderable array. An optional #heading property
  // can be passed. This text is written to a heading tag in the tray as a
  // landmark for accessibility.
  $items['commerce'] = [
    '#type' => 'toolbar_item',
    'tab' => [
      '#type' => 'link',
      '#title' => t('Shopping cart'),
      '#url' => Url::fromRoute('cart'),
      '#options' => [
        'attributes' => [
          'title' => t('Shopping cart'),
        ],
      ],
    ],
    'tray' => [
      '#heading' => t('Shopping cart actions'),
      'shopping_cart' => [
        '#theme' => 'item_list',
        '#items' => [/* An item list renderable array */],
      ],
    ],
    '#weight' => 150,
  ];

  // The tray can be used to render arbitrary content.
  //
  // A renderable array passed to the 'tray' property will be rendered outside
  // the administration bar but within the containing toolbar element.
  //
  // If the default behavior and styling of a toolbar tray is not desired, one
  // can render content to the toolbar element and apply custom theming and
  // behaviors.
  $items['user_messages'] = [
    // Include the toolbar_tab_wrapper to style the link like a toolbar tab.
    // Exclude the theme wrapper if custom styling is desired.
    '#type' => 'toolbar_item',
    'tab' => [
      '#type' => 'link',
      '#theme' => 'user_message_toolbar_tab',
      '#theme_wrappers' => [],
      '#title' => t('Messages'),
      '#url' => Url::fromRoute('user.message'),
      '#options' => [
        'attributes' => [
          'title' => t('Messages'),
        ],
      ],
    ],
    'tray' => [
      '#heading' => t('User messages'),
      'messages' => [/* renderable content */],
    ],
    '#weight' => 125,
  ];

  return $items;
}

/**
 * Alter the toolbar menu after hook_toolbar() is invoked.
 *
 * This hook is invoked by Toolbar::preRenderToolbar() immediately after
 * hook_toolbar(). The toolbar definitions are passed in by reference. Each
 * element of the $items array is one item returned by a module from
 * hook_toolbar(). Additional items may be added, or existing items altered.
 *
 * @param $items
 *   Associative array of toolbar menu definitions returned from hook_toolbar().
 */
function hook_toolbar_alter(&$items) {
  // Move the User tab to the right.
  $items['commerce']['#weight'] = 5;
}

/**
 * @} End of "addtogroup hooks".
 */
