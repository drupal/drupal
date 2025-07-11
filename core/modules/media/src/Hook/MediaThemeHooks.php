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
   * Implements hook_preprocess_HOOK() for media reference widgets.
   */
  #[Hook('preprocess_media_reference_help')]
  public function preprocessMediaReferenceHelp(&$variables): void {
    // Most of these attribute checks are copied from
    // template_preprocess_fieldset(). Our template extends
    // field-multiple-value-form.html.twig to provide our help text, but also
    // groups the information within a semantic fieldset with a legend. So, we
    // incorporate parity for both.
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
