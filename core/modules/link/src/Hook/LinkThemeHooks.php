<?php

namespace Drupal\link\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Link;

/**
 * Theme hooks for link.
 */
class LinkThemeHooks {

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme() : array {
    return [
      'link_formatter_link_separate' => [
        'variables' => [
          'title' => NULL,
          'url_title' => NULL,
          'url' => NULL,
        ],
        'initial preprocess' => static::class . ':preprocessLinkFormatterLinkSeparate',
      ],
    ];
  }

  /**
   * Prepares variables for separated link field templates.
   *
   * This template outputs a separate title and link.
   *
   * Default template: link-formatter-link-separate.html.twig.
   *
   * @param array $variables
   *   An associative array containing:
   *   - title: (optional) A descriptive or alternate title for the link, which
   *     may be different than the actual link text.
   *   - url_title: The anchor text for the link.
   *   - url: A \Drupal\Core\Url object.
   */
  public function preprocessLinkFormatterLinkSeparate(array &$variables): void {
    $variables['link'] = Link::fromTextAndUrl($variables['url_title'], $variables['url'])->toString();
  }

}
