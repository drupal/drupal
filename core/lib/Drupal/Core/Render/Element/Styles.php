<?php

/**
 * @file
 * Contains \Drupal\Core\Render\Element\Styles.
 */

namespace Drupal\Core\Render\Element;

/**
 * Provides a render element for adding CSS to the HTML output.
 *
 * @see \Drupal\Core\Render\Element\Scripts
 *
 * @RenderElement("styles")
 */
class Styles extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return array(
      '#items' => array(),
      '#pre_render' => array(
        array($class, 'preRenderStyles'),
      ),
    );
  }

  /**
   * Pre-render callback: Adds the elements needed for CSS tags to be rendered.
   *
   * For production websites, LINK tags are preferable to STYLE tags with @import
   * statements, because:
   * - They are the standard tag intended for linking to a resource.
   * - On Firefox 2 and perhaps other browsers, CSS files included with @import
   *   statements don't get saved when saving the complete web page for offline
   *   use: http://drupal.org/node/145218.
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
   * (http://drupal.org/node/228818) and LINK tags are limited to one file per
   * tag, whereas STYLE tags can contain multiple @import statements allowing
   * multiple files to be loaded per tag. When CSS aggregation is disabled, a
   * Drupal site can easily have more than 31 CSS files that need to be loaded, so
   * using LINK tags exclusively would result in a site that would display
   * incorrectly in IE. Depending on different needs, different strategies can be
   * employed to decide when to use LINK tags and when to use STYLE tags.
   *
   * The strategy employed by this function is to use LINK tags for all aggregate
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
   * This function evaluates the aggregation enabled/disabled condition on a group
   * by group basis by testing whether an aggregate file has been made for the
   * group rather than by testing the site-wide aggregation setting. This allows
   * this function to work correctly even if modules have implemented custom
   * logic for grouping and aggregating files.
   *
   * @param array $element
   *   A render array containing:
   *   - '#items': The CSS items as returned by _drupal_add_css() and altered by
   *     drupal_get_css().
   *
   * @return array
   *   A render array that will render to a string of XHTML CSS tags.
   *
   * @see drupal_get_css()
   */
  public static function preRenderStyles($element) {
    $css_assets = $element['#items'];

    // Aggregate the CSS if necessary, but only during normal site operation.
    if (!defined('MAINTENANCE_MODE') && \Drupal::config('system.performance')->get('css.preprocess')) {
      $css_assets = \Drupal::service('asset.css.collection_optimizer')->optimize($css_assets);
    }
    return \Drupal::service('asset.css.collection_renderer')->render($css_assets);
  }

}
