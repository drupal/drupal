<?php

/**
 * Contains \Drupal\Core\Asset\CssCollectionRenderer.
 */

namespace Drupal\Core\Asset;

use Drupal\Component\Utility\String;
use Drupal\Core\State\StateInterface;

/**
 * Renders CSS assets.
 */
class CssCollectionRenderer implements AssetCollectionRendererInterface {

  /**
   * The state key/value store.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs a CssCollectionRenderer.
   *
   * @param \Drupal\Core\State\StateInterface
   *   The state key/value store.
   */
  public function __construct(StateInterface $state) {
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public function render(array $css_assets) {
    $elements = array();

    // A dummy query-string is added to filenames, to gain control over
    // browser-caching. The string changes on every update or full cache
    // flush, forcing browsers to load a new copy of the files, as the
    // URL changed.
    $query_string = $this->state->get('system.css_js_query_string') ?: '0';

    // Defaults for LINK and STYLE elements.
    $link_element_defaults = array(
      '#type' => 'html_tag',
      '#tag' => 'link',
      '#attributes' => array(
        'rel' => 'stylesheet',
      ),
    );
    $style_element_defaults = array(
      '#type' => 'html_tag',
      '#tag' => 'style',
    );

    // For filthy IE hack.
    $current_ie_group_keys = NULL;
    $get_ie_group_key = function ($css_asset) {
      return array($css_asset['type'], $css_asset['preprocess'], $css_asset['group'], $css_asset['every_page'], $css_asset['media'], $css_asset['browsers']);
    };

    // Loop through all CSS assets, by key, to allow for the special IE
    // workaround.
    $css_assets_keys = array_keys($css_assets);
    for ($i = 0; $i < count($css_assets_keys); $i++) {
      $css_asset = $css_assets[$css_assets_keys[$i]];
      switch ($css_asset['type']) {
        // For file items, there are three possibilities.
        // - There are up to 31 CSS assets on the page (some of which may be
        //   aggregated). In this case, output a LINK tag for file CSS assets.
        // - There are more than 31 CSS assets on the page, yet we must stay
        //   below IE<10's limit of 31 total CSS inclusion tags, we handle this
        //   in two ways:
        //    - file CSS assets that are not eligible for aggregation (their
        //      'preprocess' flag has been set to FALSE): in this case, output a
        //      LINK tag.
        //    - file CSS assets that can be aggregated (and possibly have been):
        //      in this case, figure out which subsequent file CSS assets share
        //      the same key properties ('group', 'every_page', 'media' and
        //      'browsers') and output this group into as few STYLE tags as
        //      possible (a STYLE tag may contain only 31 @import statements).
        case 'file':
          // The dummy query string needs to be added to the URL to control
          // browser-caching.
          $query_string_separator = (strpos($css_asset['data'], '?') !== FALSE) ? '&' : '?';

          // As long as the current page will not run into IE's limit for CSS
          // assets: output a LINK tag for a file CSS asset.
          if (count($css_assets) <= 31) {
            $element = $link_element_defaults;
            $element['#attributes']['href'] = file_create_url($css_asset['data']) . $query_string_separator . $query_string;
            $element['#attributes']['media'] = $css_asset['media'];
            $element['#browsers'] = $css_asset['browsers'];
            $elements[] = $element;
          }
          // The current page will run into IE's limits for CSS assets: work
          // around these limits by performing a light form of grouping.
          // Once Drupal only needs to support IE10 and later, we can drop this.
          else {
            // The file CSS asset is ineligible for aggregation: output it in a
            // LINK tag.
            if (!$css_asset['preprocess']) {
              $element = $link_element_defaults;
              $element['#attributes']['href'] = file_create_url($css_asset['data']) . $query_string_separator . $query_string;
              $element['#attributes']['media'] = $css_asset['media'];
              $element['#browsers'] = $css_asset['browsers'];
              $elements[] = $element;
            }
            // The file CSS asset can be aggregated, but hasn't been: combine
            // multiple items into as few STYLE tags as possible.
            else {
              $import = array();
              // Start with the current CSS asset, iterate over subsequent CSS
              // assets and find which ones have the same 'type', 'group',
              // 'every_page', 'preprocess', 'media' and 'browsers' properties.
              $j = $i;
              $next_css_asset = $css_asset;
              $current_ie_group_key = $get_ie_group_key($css_asset);
              do {
                // The dummy query string needs to be added to the URL to
                // control browser-caching. IE7 does not support a media type on
                // the @import statement, so we instead specify the media for
                // the group on the STYLE tag.
                $import[] = '@import url("' . String::checkPlain(file_create_url($next_css_asset['data']) . '?' . $query_string) . '");';
                // Move the outer for loop skip the next item, since we
                // processed it here.
                $i = $j;
                // Retrieve next CSS asset, unless there is none: then break.
                if ($j + 1 < count($css_assets_keys)) {
                  $j++;
                  $next_css_asset = $css_assets[$css_assets_keys[$j]];
                }
                else {
                  break;
                }
              } while ($get_ie_group_key($next_css_asset) == $current_ie_group_key);

              // In addition to IE's limit of 31 total CSS inclusion tags, it
              // also has a limit of 31 @import statements per STYLE tag.
              while (!empty($import)) {
                $import_batch = array_slice($import, 0, 31);
                $import = array_slice($import, 31);
                $element = $style_element_defaults;
                // This simplifies the JavaScript regex, allowing each line
                // (separated by \n) to be treated as a completely different
                // string. This means that we can use ^ and $ on one line at a
                // time, and not worry about style tags since they'll never
                // match the regex.
                $element['#value'] = "\n" . implode("\n", $import_batch) . "\n";
                $element['#attributes']['media'] = $css_asset['media'];
                $element['#browsers'] = $css_asset['browsers'];
                $elements[] = $element;
              }
            }
          }
          break;

        // Output a STYLE tag for an inline CSS asset. The asset's 'data'
        // property contains the CSS content.
        case 'inline':
          $element = $style_element_defaults;
          $element['#value'] = $css_asset['data'];
          $element['#attributes']['media'] = $css_asset['media'];
          $element['#browsers'] = $css_asset['browsers'];
          // For inline CSS to validate as XHTML, all CSS containing XHTML needs
          // to be wrapped in CDATA. To make that backwards compatible with HTML
          // 4, we need to comment out the CDATA-tag.
          $element['#value_prefix'] = "\n/* <![CDATA[ */\n";
          $element['#value_suffix'] = "\n/* ]]> */\n";
          $elements[] = $element;
          break;

        // Output a LINK tag for an external CSS asset. The asset's 'data'
        // property contains the full URL.
        case 'external':
          $element = $link_element_defaults;
          $element['#attributes']['href'] = $css_asset['data'];
          $element['#attributes']['media'] = $css_asset['media'];
          $element['#browsers'] = $css_asset['browsers'];
          $elements[] = $element;
          break;

        default:
          throw new \Exception('Invalid CSS asset type.');
      }
    }

    return $elements;
  }

}
