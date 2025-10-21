<?php

namespace Drupal\announcements_feed\Hook;

use Drupal\announcements_feed\RenderCallbacks;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * Toolbar hook implementations for announcements_feed.
 */
class AnnouncementsFeedToolbarHooks {

  public function __construct(
    protected readonly ElementInfoManagerInterface $elementInfoManager,
    protected readonly AccountProxyInterface $account,
  ) {}

  /**
   * Implements hook_toolbar().
   */
  #[Hook('toolbar')]
  public function toolbar(): array {
    if (!$this->account->hasPermission('access announcements')) {
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

}
