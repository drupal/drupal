<?php

namespace Drupal\announcements_feed\Hook;

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
