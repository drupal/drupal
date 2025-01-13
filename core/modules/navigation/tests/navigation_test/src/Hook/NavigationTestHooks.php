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

}
