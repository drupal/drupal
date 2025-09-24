<?php

namespace Drupal\navigation\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\navigation\TopBarRegion;

/**
 * Theme hooks for navigation.
 */
class NavigationThemeHooks {

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme($existing, $type, $theme, $path) : array {
    $items['top_bar'] = [
      'render element' => 'element',
      'initial preprocess' => static::class . ':preprocessTopBar',
    ];
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
   * Prepares variables for navigation top bar template.
   *
   * Default template: top-bar.html.twig
   *
   * @param array $variables
   *   An associative array containing:
   *    - element: An associative array containing the properties and children
   *      of the top bar.
   */
  public function preprocessTopBar(array &$variables): void {
    $element = $variables['element'];

    foreach (TopBarRegion::cases() as $region) {
      $variables[$region->value] = $element[$region->value] ?? NULL;
    }
  }

}
