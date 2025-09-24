<?php

namespace Drupal\media\Hook;

use Drupal\Core\Template\Attribute;
use Drupal\Core\Render\Element\RenderElementBase;
use Drupal\Core\Render\Element;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for media.
 */
class MediaThemeHooks {

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme() : array {
    return [
      'media' => [
        'render element' => 'elements',
        'initial preprocess' => static::class . ':preprocessMedia',
      ],
      'media_reference_help' => [
        'render element' => 'element',
        'base hook' => 'field_multiple_value_form',
      ],
      'media_oembed_iframe' => [
        'variables' => [
          'resource' => NULL,
          'media' => NULL,
          'placeholder_token' => '',
        ],
      ],
      'media_embed_error' => [
        'variables' => [
          'message' => NULL,
          'attributes' => [],
        ],
      ],
    ];
  }

  /**
   * Prepares variables for media templates.
   *
   * Default template: media.html.twig.
   *
   * @param array $variables
   *   An associative array containing:
   *   - elements: An array of elements to display in view mode.
   *   - media: The media item.
   *   - name: The label for the media item.
   *   - view_mode: View mode; e.g., 'full', 'teaser', etc.
   */
  public function preprocessMedia(array &$variables): void {
    $variables['media'] = $variables['elements']['#media'];
    $variables['view_mode'] = $variables['elements']['#view_mode'];
    $variables['name'] = $variables['media']->label();

    // Helpful $content variable for templates.
    foreach (Element::children($variables['elements']) as $key) {
      $variables['content'][$key] = $variables['elements'][$key];
    }
  }

  /**
   * Implements hook_preprocess_HOOK() for media reference widgets.
   */
  #[Hook('preprocess_media_reference_help')]
  public function preprocessMediaReferenceHelp(&$variables): void {
    // Most of these attribute checks are copied from
    // \Drupal\Core\Form\FormPreprocess::preprocessFieldset(). Our template
    // extends field-multiple-value-form.html.twig to provide our help text, but
    // also groups the information within a semantic fieldset with a legend. So,
    // we incorporate parity for both.
    $element = $variables['element'];
    Element::setAttributes($element, [
      'id',
    ]);
    RenderElementBase::setAttributes($element);
    $variables['attributes'] = $element['#attributes'] ?? [];
    $variables['legend_attributes'] = new Attribute();
    $variables['header_attributes'] = new Attribute();
    $variables['description']['attributes'] = new Attribute();
    $variables['legend_span_attributes'] = new Attribute();
    if (!empty($element['#media_help'])) {
      foreach ($element['#media_help'] as $key => $text) {
        $variables[substr($key, 1)] = $text;
      }
    }
  }

}
