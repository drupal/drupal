<?php

namespace Drupal\contextual\Element;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Site\Settings;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Render\Element\RenderElement;
use Drupal\Component\Render\FormattableMarkup;

/**
 * Provides a contextual_links_placeholder element.
 *
 * @RenderElement("contextual_links_placeholder")
 */
class ContextualLinksPlaceholder extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = static::class;
    return [
      '#pre_render' => [
        [$class, 'preRenderPlaceholder'],
      ],
      '#id' => NULL,
    ];
  }

  /**
   * Pre-render callback: Renders a contextual links placeholder into #markup.
   *
   * Renders an empty (hence invisible) placeholder div with a data-attribute
   * that contains an identifier ("contextual id"), which allows the JavaScript
   * of the drupal.contextual-links library to dynamically render contextual
   * links.
   *
   * @param array $element
   *   A structured array with #id containing a "contextual id".
   *
   * @return array
   *   The passed-in element with a contextual link placeholder in '#markup'.
   *
   * @see _contextual_links_to_id()
   */
  public static function preRenderPlaceholder(array $element) {
    $token = Crypt::hmacBase64($element['#id'], Settings::getHashSalt() . \Drupal::service('private_key')->get());
    $attribute = new Attribute([
      'data-contextual-id' => $element['#id'],
      'data-contextual-token' => $token,
    ]);
    $element['#markup'] = new FormattableMarkup('<div@attributes></div>', ['@attributes' => $attribute]);

    return $element;
  }

}
