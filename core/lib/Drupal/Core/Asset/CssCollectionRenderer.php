<?php

/**
 * Contains \Drupal\Core\Asset\CssCollectionRenderer.
 */

namespace Drupal\Core\Asset;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\State\StateInterface;

/**
 * Renders CSS assets.
 *
 * For production websites, LINK tags are preferable to STYLE tags with @import
 * statements, because:
 * - They are the standard tag intended for linking to a resource.
 * - On Firefox 2 and perhaps other browsers, CSS files included with @import
 *   statements don't get saved when saving the complete web page for offline
 *   use: https://www.drupal.org/node/145218.
 * - On IE, if only LINK tags and no @import statements are used, all the CSS
 *   files are downloaded in parallel, resulting in faster page load, but if
 *   @import statements are used and span across multiple STYLE tags, all the
 *   ones from one STYLE tag must be downloaded before downloading begins for
 *   the next STYLE tag. Furthermore, IE7 does not support media declaration on
 *   the @import statement, so multiple STYLE tags must be used when different
 *   files are for different media types. Non-IE browsers always download in
 *   parallel, so this is an IE-specific performance quirk:
 *   http://www.stevesouders.com/blog/2009/04/09/dont-use-import/.
 *
 * However, IE has an annoying limit of 31 total CSS inclusion tags
 * (https://www.drupal.org/node/228818) and LINK tags are limited to one file
 * per tag, whereas STYLE tags can contain multiple @import statements allowing
 * multiple files to be loaded per tag. When CSS aggregation is disabled, a
 * Drupal site can easily have more than 31 CSS files that need to be loaded, so
 * using LINK tags exclusively would result in a site that would display
 * incorrectly in IE. Depending on different needs, different strategies can be
 * employed to decide when to use LINK tags and when to use STYLE tags.
 *
 * The strategy employed by this class is to use LINK tags for all aggregate
 * files and for all files that cannot be aggregated (e.g., if 'preprocess' is
 * set to FALSE or the type is 'external'), and to use STYLE tags for groups
 * of files that could be aggregated together but aren't (e.g., if the site-wide
 * aggregation setting is disabled). This results in all LINK tags when
 * aggregation is enabled, a guarantee that as many or only slightly more tags
 * are used with aggregation disabled than enabled (so that if the limit were to
 * be crossed with aggregation enabled, the site developer would also notice the
 * problem while aggregation is disabled), and an easy way for a developer to
 * view HTML source while aggregation is disabled and know what files will be
 * aggregated together when aggregation becomes enabled.
 *
 * This class evaluates the aggregation enabled/disabled condition on a group
 * by group basis by testing whether an aggregate file has been made for the
 * group rather than by testing the site-wide aggregation setting. This allows
 * this class to work correctly even if modules have implemented custom
 * logic for grouping and aggregating files.
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
                $import[] = '@import url("' . SafeMarkup::checkPlain(file_create_url($next_css_asset['data']) . '?' . $query_string) . '");';
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
