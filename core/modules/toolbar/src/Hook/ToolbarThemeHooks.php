<?php

namespace Drupal\toolbar\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Template\Attribute;

/**
 * Hook implementations for toolbar.
 */
class ToolbarThemeHooks {

  public function __construct(
    protected RendererInterface $renderer,
  ) {

  }

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme($existing, $type, $theme, $path) : array {
    $items['toolbar'] = [
      'render element' => 'element',
      'initial preprocess' => static::class . ':preprocessToolbar',
    ];
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
   * Prepares variables for administration toolbar templates.
   *
   * Default template: toolbar.html.twig.
   *
   * @param array $variables
   *   An associative array containing:
   *   - element: An associative array containing the properties and children of
   *     the tray. Properties used: #children, #attributes and #bar.
   */
  public function preprocessToolbar(array &$variables): void {
    $element = $variables['element'];

    // Prepare the toolbar attributes.
    $variables['attributes'] = $element['#attributes'];
    $variables['toolbar_attributes'] = new Attribute($element['#bar']['#attributes']);
    $variables['toolbar_heading'] = $element['#bar']['#heading'];

    // Prepare the trays and tabs for each toolbar item as well as the remainder
    // variable that will hold any non-tray, non-tab elements.
    $variables['trays'] = [];
    $variables['tabs'] = [];
    $variables['remainder'] = [];
    foreach (Element::children($element) as $key) {
      // Early rendering to collect the wrapper attributes from
      // ToolbarItem elements.
      if (!empty($element[$key])) {
        $this->renderer->render($element[$key]);
      }
      // Add the tray.
      if (isset($element[$key]['tray'])) {
        $attributes = [];
        if (!empty($element[$key]['tray']['#wrapper_attributes'])) {
          $attributes = $element[$key]['tray']['#wrapper_attributes'];
        }
        $variables['trays'][$key] = [
          'links' => $element[$key]['tray'],
          'attributes' => new Attribute($attributes),
        ];
        if (array_key_exists('#heading', $element[$key]['tray'])) {
          $variables['trays'][$key]['label'] = $element[$key]['tray']['#heading'];
        }
      }

      // Add the tab.
      if (isset($element[$key]['tab'])) {
        $attributes = [];
        // Pass the wrapper attributes along.
        if (!empty($element[$key]['#wrapper_attributes'])) {
          $attributes = $element[$key]['#wrapper_attributes'];
        }

        $variables['tabs'][$key] = [
          'link' => $element[$key]['tab'],
          'attributes' => new Attribute($attributes),
        ];
      }

      // Add other non-tray, non-tab child elements to the remainder variable
      // for later rendering.
      foreach (Element::children($element[$key]) as $child_key) {
        if (!in_array($child_key, ['tray', 'tab'])) {
          $variables['remainder'][$key][$child_key] = $element[$key][$child_key];
        }
      }
    }
  }

  /**
   * Implements hook_preprocess_HOOK() for HTML document templates.
   */
  #[Hook('preprocess_html')]
  public function preprocessHtml(&$variables): void {
    if (!\Drupal::currentUser()->hasPermission('access toolbar')) {
      return;
    }
    $variables['attributes']['class'][] = 'toolbar-loading';
  }

}
