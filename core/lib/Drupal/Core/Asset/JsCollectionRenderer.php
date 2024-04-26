<?php

namespace Drupal\Core\Asset;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Core\File\FileUrlGeneratorInterface;

/**
 * Renders JavaScript assets.
 */
class JsCollectionRenderer implements AssetCollectionRendererInterface {

  /**
   * Constructs a JsCollectionRenderer.
   *
   * @param \Drupal\Core\Asset\AssetQueryStringInterface $assetQueryString
   *   The asset query string.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $fileUrlGenerator
   *   The file URL generator.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(
    protected AssetQueryStringInterface $assetQueryString,
    protected FileUrlGeneratorInterface $fileUrlGenerator,
    protected TimeInterface $time,
  ) {
  }

  /**
   * {@inheritdoc}
   *
   * This class evaluates the aggregation enabled/disabled condition on a group
   * by group basis by testing whether an aggregate file has been made for the
   * group rather than by testing the site-wide aggregation setting. This allows
   * this class to work correctly even if modules have implemented custom
   * logic for grouping and aggregating files.
   */
  public function render(array $js_assets) {
    $elements = [];

    // A dummy query-string is added to filenames, to gain control over
    // browser-caching. The string changes on every update or full cache
    // flush, forcing browsers to load a new copy of the files, as the
    // URL changed. Files that should not be cached get the request time as a
    // query-string instead, to enforce reload on every page request.
    $default_query_string = $this->assetQueryString->get();

    // Defaults for each SCRIPT element.
    $element_defaults = [
      '#type' => 'html_tag',
      '#tag' => 'script',
      '#value' => '',
    ];

    // Loop through all JS assets.
    foreach ($js_assets as $js_asset) {
      $element = $element_defaults;

      // Element properties that depend on item type.
      switch ($js_asset['type']) {
        case 'setting':
          $element['#attributes'] = [
            // This type attribute prevents this from being parsed as an
            // inline script.
            'type' => 'application/json',
            'data-drupal-selector' => 'drupal-settings-json',
          ];
          $element['#value'] = Json::encode($js_asset['data']);
          break;

        case 'file':
          $query_string = $js_asset['version'] == -1 ? $default_query_string : 'v=' . $js_asset['version'];
          $query_string_separator = str_contains($js_asset['data'], '?') ? '&' : '?';
          $element['#attributes']['src'] = $this->fileUrlGenerator->generateString($js_asset['data']);
          // Only add the cache-busting query string if this isn't an aggregate
          // file.
          if (!isset($js_asset['preprocessed'])) {
            $element['#attributes']['src'] .= $query_string_separator . ($js_asset['cache'] ? $query_string : $this->time->getRequestTime());
          }
          break;

        case 'external':
          $element['#attributes']['src'] = $js_asset['data'];
          break;

        default:
          throw new \Exception('Invalid JS asset type.');
      }

      // Attributes may only be set if this script is output independently.
      if (!empty($element['#attributes']['src']) && !empty($js_asset['attributes'])) {
        $element['#attributes'] += $js_asset['attributes'];
      }

      $elements[] = $element;
    }

    return $elements;
  }

}
