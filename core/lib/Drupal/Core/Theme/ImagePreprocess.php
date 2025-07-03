<?php

namespace Drupal\Core\Theme;

use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Template\AttributeHelper;

/**
 * Image theme preprocess.
 *
 * @internal
 */
class ImagePreprocess {

  public function __construct(protected FileUrlGeneratorInterface $fileUrlGenerator) {
  }

  /**
   * Prepares variables for image templates.
   *
   * Default template: image.html.twig.
   *
   * @param array $variables
   *   An associative array containing:
   *   - uri: Either the path of the image file (relative to base_path()) or a
   *     full URL.
   *   - width: The width of the image (if known).
   *   - height: The height of the image (if known).
   *   - alt: The alternative text for text-based browsers. HTML 4 and XHTML 1.0
   *     always require an alt attribute. The HTML 5 draft allows the alt
   *     attribute to be omitted in some cases. Therefore, this variable
   *     defaults to an empty string, but can be set to NULL for the attribute
   *     to be omitted. Usually, neither omission nor an empty string satisfies
   *     accessibility requirements, so it is strongly encouraged for code
   *     building variables for image.html.twig templates to pass a meaningful
   *     value for this variable.
   *     - https://www.w3.org/TR/REC-html40/struct/objects.html#h-13.8
   *     - https://www.w3.org/TR/xhtml1/dtds.html
   *     - http://dev.w3.org/html5/spec/Overview.html#alt
   *   - title: The title text is displayed when the image is hovered in some
   *     popular browsers.
   *   - attributes: Associative array of attributes to be placed in the img
   *     tag.
   *   - srcset: Array of multiple URIs and sizes/multipliers.
   *   - sizes: The sizes attribute for viewport-based selection of images.
   *     phpcs:ignore
   *     - http://www.whatwg.org/specs/web-apps/current-work/multipage/embedded-content.html#introduction-3:viewport-based-selection-2
   */
  public function preprocessImage(array &$variables): void {
    if (!empty($variables['uri'])) {
      $variables['attributes']['src'] = $this->fileUrlGenerator->generateString($variables['uri']);
    }
    // Generate a srcset attribute conforming to the spec at
    // https://www.w3.org/html/wg/drafts/html/master/embedded-content.html#attr-img-srcset
    if (!empty($variables['srcset'])) {
      $srcset = [];
      foreach ($variables['srcset'] as $src) {
        // URI is mandatory.
        $source = $this->fileUrlGenerator->generateString($src['uri']);
        if (isset($src['width']) && !empty($src['width'])) {
          $source .= ' ' . $src['width'];
        }
        elseif (isset($src['multiplier']) && !empty($src['multiplier'])) {
          $source .= ' ' . $src['multiplier'];
        }
        $srcset[] = $source;
      }
      $variables['attributes']['srcset'] = implode(', ', $srcset);
    }

    foreach (['width', 'height', 'alt', 'title', 'sizes'] as $key) {
      if (isset($variables[$key])) {
        // If the property has already been defined in the attributes,
        // do not override, including NULL.
        if (AttributeHelper::attributeExists($key, $variables['attributes'])) {
          continue;
        }
        $variables['attributes'][$key] = $variables[$key];
      }
    }

    // Without dimensions specified, layout shifts can occur,
    // which are more noticeable on pages that take some time to load.
    // As a result, only mark images as lazy load that have dimensions.
    if (isset($variables['width'], $variables['height']) && !isset($variables['attributes']['loading'])) {
      $variables['attributes']['loading'] = 'lazy';
    }
  }

}
