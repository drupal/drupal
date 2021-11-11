<?php

declare(strict_types=1);

namespace Drupal\ckeditor5\Plugin\CKEditor5Plugin;

use Drupal\Core\Url;

/**
 * Provides a trait for CKEditor 5 with dynamically generated CSRF token URLs.
 *
 * The Text Editor module's APIs predate the concept of bubbleable metadata. To
 * prevent URLs with CSRF tokens from breaking cacheability, placeholders are
 * used for those CSRF tokens since https://drupal.org/i/2512132. Placeholders
 * are designed to be attached to the data in which they exist, so they can be
 * replaced at the last possible moment, without interfering with cacheability.
 * Unfortunately, because it is not possible to associate bubbleable metadata
 * with a Text Editor's JS settings, we have to manually process these. This is
 * acceptable only because a text editor's JS settings are not cacheable anyway
 * (just like forms are not cacheable).
 *
 * @see \Drupal\Core\Access\CsrfAccessCheck
 * @see \Drupal\Core\Access\RouteProcessorCsrf::processOutbound()
 * @see \Drupal\Core\Render\BubbleableMetadata
 * @see \Drupal\editor\Plugin\EditorPluginInterface::getJSSettings()
 * @see \Drupal\ckeditor5\Plugin\CKEditor5Plugin\ImageUpload::getDynamicPluginConfig()
 * @see \Drupal\ckeditor5\Plugin\CKEditor5Plugin\Media::getDynamicPluginConfig()
 * @see https://www.drupal.org/project/drupal/issues/2512132
 *
 * @internal
 */
trait DynamicPluginConfigWithCsrfTokenUrlTrait {

  /**
   * Gets the given URL with all placeholders replaced.
   *
   * @param \Drupal\Core\Url $url
   *   A URL which generates CSRF token placeholders.
   *
   * @return string
   *   The URL string, with all placeholders replaced.
   */
  private static function getUrlWithReplacedCsrfTokenPlaceholder(Url $url): string {
    $generated_url = $url->toString(TRUE);
    $url_with_csrf_token_placeholder = [
      '#plain_text' => $generated_url->getGeneratedUrl(),
    ];
    $generated_url->applyTo($url_with_csrf_token_placeholder);
    return (string) \Drupal::service('renderer')->renderPlain($url_with_csrf_token_placeholder);
  }

}
