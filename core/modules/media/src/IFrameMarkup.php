<?php

namespace Drupal\media;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Component\Render\MarkupTrait;
use Drupal\Component\Utility\Html;

/**
 * Defines an object that wraps oEmbed markup for use in an iFrame.
 *
 * This object is not constructed with a known safe string as the strings come
 * from an external site. It must not be used outside the Media module's oEmbed
 * iframe rendering.
 *
 * @internal
 *   This object is an internal part of the oEmbed system and should only be
 *   used in \Drupal\media\Controller\OEmbedIframeController.
 *
 * @see \Drupal\media\Controller\OEmbedIframeController
 */
class IFrameMarkup implements MarkupInterface {
  use MarkupTrait;

  /**
   * Creates a Markup object if necessary.
   *
   * If $string is equal to a blank string then it is not necessary to create a
   * Markup object. If $string is an object that implements MarkupInterface it
   * is returned unchanged.
   *
   * @param mixed $string
   *   The string to mark as safe. This value will be cast to a string.
   * @param mixed $resource_title
   *   The provider title attribute, if available.
   *
   * @return string|\Drupal\Component\Render\MarkupInterface
   *   A safe string.
   */
  public static function create($string, $resource_title) {
    if ($string instanceof MarkupInterface) {
      return $string;
    }
    $string = (string) $string;
    if ($string === '') {
      return '';
    }
    $safe_string = new static();
    $safe_string->string = $string;
    // Set title attribute for iFrame if it doesn't exist.
    $html_dom = Html::load($safe_string);
    $iframes = $html_dom->getElementsByTagName('iframe');
    foreach ($iframes as $iframe) {
      if (!$iframe->hasAttribute('title')) {
        $url = $iframe->getAttribute('src');
        $url_pieces = parse_url($url);
        $host = $url_pieces['host'];
        $title = $resource_title ?? "Embedded content from " . $host;
        $iframe->setAttribute('title', $title);
      }
    }
    $safe_string = Html::serialize($html_dom);
    return $safe_string;
  }

}
