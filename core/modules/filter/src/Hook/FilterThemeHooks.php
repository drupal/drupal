<?php

namespace Drupal\filter\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Theme hooks for filter.
 */
class FilterThemeHooks {

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme() : array {
    return [
      'filter_tips' => [
        'variables' => [
          'tips' => NULL,
          'long' => FALSE,
        ],
      ],
      'text_format_wrapper' => [
        'variables' => [
          'children' => NULL,
          'description' => NULL,
          'attributes' => [],
        ],
        'initial preprocess' => static::class . ':preprocessTextFormatWrapper',
      ],
      'filter_guidelines' => [
        'variables' => [
          'format' => NULL,
        ],
        'initial preprocess' => static::class . ':preprocessFilterGuidelines',
      ],
      'filter_caption' => [
        'variables' => [
          'node' => NULL,
          'tag' => NULL,
          'caption' => NULL,
          'classes' => NULL,
        ],
      ],
    ];
  }

  /**
   * Prepares variables for text format guideline templates.
   *
   * Default template: filter-guidelines.html.twig.
   *
   * @param array $variables
   *   An associative array containing:
   *   - format: An object representing a text format.
   */
  public function preprocessFilterGuidelines(array &$variables): void {
    $format = $variables['format'];
    $variables['tips'] = [
      '#theme' => 'filter_tips',
      '#tips' => _filter_tips($format->id()),
    ];

    // Add format id for filter.js.
    $variables['attributes']['data-drupal-format-id'] = $format->id();
  }

  /**
   * Prepares variables for text format wrapper templates.
   *
   * Default template: text-format-wrapper.html.twig.
   *
   * @param array $variables
   *   An associative array containing:
   *   - attributes: An associative array containing properties of the element.
   */
  public function preprocessTextFormatWrapper(array &$variables): void {
    $variables['aria_description'] = FALSE;
    // Add element class and id for screen readers.
    if (isset($variables['attributes']['aria-describedby'])) {
      $variables['aria_description'] = TRUE;
      $variables['attributes']['id'] = $variables['attributes']['aria-describedby'];
      // Remove aria-describedby attribute as it shouldn't be visible here.
      unset($variables['attributes']['aria-describedby']);
    }
  }

}
