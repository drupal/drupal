<?php

namespace Drupal\layout_discovery\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Layout\LayoutPluginManagerInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Template\Attribute;

/**
 * Theme hooks for layout_discovery.
 */
class LayoutDiscoveryThemeHooks {

  public function __construct(
    protected LayoutPluginManagerInterface $layoutPluginManager,
  ) {

  }

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme() : array {
    return $this->layoutPluginManager->getThemeImplementations();
  }

  /**
   * Prepares variables for layout templates.
   *
   * @param array &$variables
   *   An associative array containing:
   *   - content: An associative array containing the properties of the element.
   *     Properties used: #settings, #layout, #in_preview.
   */
  public function preprocessLayout(array &$variables): void {
    $variables['settings'] = $variables['content']['#settings'] ?? [];
    $variables['layout'] = $variables['content']['#layout'] ?? [];
    $variables['in_preview'] = $variables['content']['#in_preview'] ?? FALSE;

    // Create an attributes variable for each region.
    foreach (Element::children($variables['content']) as $name) {
      if (!isset($variables['content'][$name]['#attributes'])) {
        $variables['content'][$name]['#attributes'] = [];
      }
      $variables['region_attributes'][$name] = new Attribute($variables['content'][$name]['#attributes']);
    }
  }

}
