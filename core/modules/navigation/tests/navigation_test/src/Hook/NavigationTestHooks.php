<?php

declare(strict_types=1);

namespace Drupal\navigation_test\Hook;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\State\StateInterface;

/**
 * Hooks implementations for navigation_test module.
 */
class NavigationTestHooks {

  /**
   * NavigationTestHooks constructor.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   */
  public function __construct(
    protected StateInterface $state,
  ) {
  }

  /**
   * Implements hook_block_alter().
   */
  #[Hook('block_alter')]
  public function blockAlter(&$definitions): void {
    if ($this->state->get('navigation_safe_alter')) {
      $definitions['navigation_link']['allow_in_navigation'] = TRUE;
      $definitions['navigation_shortcuts']['allow_in_navigation'] = FALSE;
    }
  }

  /**
   * Implements hook_navigation_content_top().
   */
  #[Hook('navigation_content_top')]
  public function navigationContentTop(): array {
    if (\Drupal::keyValue('navigation_test')->get('content_top')) {
      $items = [
        'navigation_foo' => [
          '#markup' => 'foo',
        ],
        'navigation_bar' => [
          '#markup' => 'bar',
        ],
        'navigation_baz' => [
          '#markup' => 'baz',
        ],
      ];
    }
    else {
      $items = [
        'navigation_foo' => [],
        'navigation_bar' => [],
        'navigation_baz' => [],
      ];
    }
    // Add cache tags to our items to express a made up dependency to test
    // cacheability. Note that as we're always returning the same items,
    // sometimes only with cacheability metadata. By doing this we're testing
    // conditional rendering of content_top items.
    foreach ($items as &$element) {
      CacheableMetadata::createFromRenderArray($element)
        ->addCacheTags(['navigation_test'])
        ->applyTo($element);
    }
    return $items;
  }

  /**
   * Implements hook_navigation_content_top_alter().
   */
  #[Hook('navigation_content_top_alter')]
  public function navigationContentTopAlter(&$content_top): void {
    if (\Drupal::keyValue('navigation_test')->get('content_top_alter')) {
      unset($content_top['navigation_foo']);
      $content_top['navigation_bar']['#markup'] = 'new bar';
      $content_top['navigation_baz']['#weight'] = '-100';
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
      // Removes all items from menu1.
      if ($menu_name == 'menu1') {
        unset($tree[$key]);
      }

      // Updates title for items in menu2
      if ($menu_name == 'menu2') {
        $item->link->updateLink(['title' => 'New Link Title'], FALSE);
      }
    }
  }

  /**
   * Implements hook_menu_links_discovered_alter().
   */
  #[Hook('menu_links_discovered_alter')]
  public function menuLinksDiscoveredAlter(array &$links): void {
    if (\Drupal::keyValue('navigation_test')->get('menu_links_discovered_alter')) {
      $links['navigation_test.navigation__no_icon']['options']['icon'] = [
        'icon_id' => 'radioactive',
        'pack_id' => 'navigation_test',
      ];
      $links['navigation_test.navigation__default_item']['options']['icon'] = [
        'icon_id' => 'foo',
        'pack_id' => 'bar',
      ];
    }
  }

}
