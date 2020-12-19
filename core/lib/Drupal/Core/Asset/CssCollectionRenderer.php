<?php

namespace Drupal\Core\Asset;

use Drupal\Core\Cache\QueryStringInterface;
use Drupal\Core\State\StateInterface;

/**
 * Renders CSS assets.
 */
class CssCollectionRenderer implements AssetCollectionRendererInterface {

  /**
   * The state key/value store.
   *
   * @var \Drupal\Core\State\StateInterface
   * @deprecated in drupal:9.2.0 and is removed from drupal:10.0.0.
   */
  protected $state;

  /**
   * The cache query string interface.
   *
   * @var \Drupal\Core\Cache\QueryStringInterface|mixed|null
   */
  protected $queryString;

  /**
   * Constructs a CssCollectionRenderer.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state key/value store.
   * @param \Drupal\Core\Cache\QueryStringInterface $query_string
   *   The cache query string interface.
   */
  public function __construct(StateInterface $state, QueryStringInterface $query_string = NULL) {
    $this->state = $state;
    if ($query_string === NULL) {
      @trigger_error('$query_string parameter is added since drupal:9.2.0 and is required from drupal:10.0.0.', \E_USER_DEPRECATED);
      $query_string = \Drupal::service('cache.query_string');
    }
    $this->queryString = $query_string;
  }

  /**
   * {@inheritdoc}
   */
  public function render(array $css_assets) {
    $elements = [];

    // A dummy query-string is added to filenames, to gain control over
    // browser-caching. The string changes on every update or full cache
    // flush, forcing browsers to load a new copy of the files, as the
    // URL changed.
    $query_string = $this->queryString->get();

    // Defaults for LINK and STYLE elements.
    $link_element_defaults = [
      '#type' => 'html_tag',
      '#tag' => 'link',
      '#attributes' => [
        'rel' => 'stylesheet',
      ],
    ];

    foreach ($css_assets as $css_asset) {
      $element = $link_element_defaults;
      $element['#attributes']['media'] = $css_asset['media'];
      $element['#browsers'] = $css_asset['browsers'];

      switch ($css_asset['type']) {
        // For file items, output a LINK tag for file CSS assets.
        case 'file':
          $element['#attributes']['href'] = file_url_transform_relative(file_create_url($css_asset['data']));
          // Only add the cache-busting query string if this isn't an aggregate
          // file.
          if (!isset($css_asset['preprocessed'])) {
            $query_string_separator = (strpos($css_asset['data'], '?') !== FALSE) ? '&' : '?';
            $element['#attributes']['href'] .= $query_string_separator . $query_string;
          }
          break;

        case 'external':
          $element['#attributes']['href'] = $css_asset['data'];
          break;

        default:
          throw new \Exception('Invalid CSS asset type.');
      }

      // Merge any additional attributes.
      if (!empty($css_asset['attributes'])) {
        $element['#attributes'] += $css_asset['attributes'];
      }

      $elements[] = $element;
    }

    return $elements;
  }

}
