<?php

namespace Drupal\announcements_feed\Hook;

use Drupal\announcements_feed\RenderCallbacks;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for announcements_feed.
 */
class AnnouncementsFeedHooks {

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match) {
    switch ($route_name) {
      case 'help.page.announcements_feed':
        $output = '';
        $output .= '<h2>' . t('About') . '</h2>';
        $output .= '<p>' . t('The Announcements module displays announcements from the Drupal community. For more information, see the <a href=":documentation">online documentation for the Announcements module</a>.', [
          ':documentation' => 'https://www.drupal.org/docs/core-modules-and-themes/core-modules/announcements-feed',
        ]) . '</p>';
        $output .= '<h2>' . t('Uses') . '</h2>';
        $output .= '<dl><dt>' . t('Accessing announcements') . '</dt>';
        $output .= '<dd>' . t('Users with the "View drupal.org announcements" permission may click on the "Announcements" item in the administration toolbar, or access @link, to see all announcements relevant to the Drupal version of your site.', [
          '@link' => Link::createFromRoute(t('Announcements'), 'announcements_feed.announcement')->toString(),
        ]) . '</dd>';
        $output .= '</dl>';
        return $output;
    }
  }

  /**
   * Implements hook_toolbar().
   */
  #[Hook('toolbar')]
  public function toolbar() {
    if (!\Drupal::currentUser()->hasPermission('access announcements')) {
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
    // #attributes property to each toolbar item's tab child automatically.
    // Lazy builders don't support an #attributes property so we need to
    // add another render callback to remove the #attributes property. We start by
    // adding the defaults, and then we append our own pre render callback.
    $items['announcement'] += \Drupal::service('plugin.manager.element_info')->getInfo('toolbar_item');
    $items['announcement']['#pre_render'][] = [RenderCallbacks::class, 'removeTabAttributes'];
    return $items;
  }

  /**
   * Implements hook_toolbar_alter().
   */
  #[Hook('toolbar_alter')]
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

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme($existing, $type, $theme, $path) : array {
    return [
      'announcements_feed' => [
        'variables' => [
          'featured' => NULL,
          'standard' => NULL,
          'count' => 0,
          'feed_link' => '',
        ],
      ],
      'announcements_feed_admin' => [
        'variables' => [
          'featured' => NULL,
          'standard' => NULL,
          'count' => 0,
          'feed_link' => '',
        ],
      ],
    ];
  }

  /**
   * Implements hook_cron().
   */
  #[Hook('cron')]
  public function cron(): void {
    $config = \Drupal::config('announcements_feed.settings');
    $interval = $config->get('cron_interval');
    $last_check = \Drupal::state()->get('announcements_feed.last_fetch', 0);
    $time = \Drupal::time()->getRequestTime();
    if ($time - $last_check > $interval) {
      \Drupal::service('announcements_feed.fetcher')->fetch(TRUE);
      \Drupal::state()->set('announcements_feed.last_fetch', $time);
    }
  }

}
