<?php
/**
 * @file
 * Contains \Drupal\Core\Asset\AssetResolverInterface.
 */

namespace Drupal\Core\Asset;

/**
 * Resolves asset libraries into concrete CSS and JavaScript assets.
 *
 * Given an attached assets collection (to be loaded for the current response),
 * the asset resolver can resolve those asset libraries into a list of concrete
 * CSS and JavaScript assets.
 *
 * In other words: this allows developers to translate Drupal's asset
 * abstraction (asset libraries) into concrete assets.
 *
 * @see \Drupal\Core\Asset\AttachedAssetsInterface
 * @see \Drupal\Core\Asset\LibraryDependencyResolverInterface
 */
interface AssetResolverInterface {

  /**
   * Returns the CSS assets for the current response's libraries.
   *
   * It returns the CSS assets in order, according to the SMACSS categories
   * specified in the assets' weights:
   * 1. CSS_BASE
   * 2. CSS_LAYOUT
   * 3. CSS_COMPONENT
   * 4. CSS_STATE
   * 5. CSS_THEME
   * @see https://www.drupal.org/node/1887918#separate-concerns
   * This ensures proper cascading of styles so themes can easily override
   * module styles through CSS selectors.
   *
   * Themes may replace module-defined CSS files by adding a stylesheet with the
   * same filename. For example, themes/bartik/system-menus.css would replace
   * modules/system/system-menus.css. This allows themes to override complete
   * CSS files, rather than specific selectors, when necessary.
   *
   * Also invokes hook_css_alter(), to allow CSS assets to be altered.
   *
   * @param \Drupal\Core\Asset\AttachedAssetsInterface $assets
   *   The assets attached to the current response.
   * @param bool $optimize
   *   Whether to apply the CSS asset collection optimizer, to return an
   *   optimized CSS asset collection rather than an unoptimized one.
   *
   * @return array
   *   A (possibly optimized) collection of CSS assets.
   */
  public function getCssAssets(AttachedAssetsInterface $assets, $optimize);

  /**
   * Returns the JavaScript assets for the current response's libraries.
   *
   * References to JavaScript files are placed in a certain order: first, all
   * 'core' files, then all 'module' and finally all 'theme' JavaScript files
   * are added to the page. Then, all settings are output, followed by 'inline'
   * JavaScript code. If running update.php, all preprocessing is disabled.
   *
   * Note that hook_js_alter(&$javascript) is called during this function call
   * to allow alterations of the JavaScript during its presentation. The correct
   * way to add JavaScript during hook_js_alter() is to add another element to
   * the $javascript array, deriving from drupal_js_defaults(). See
   * locale_js_alter() for an example of this.
   *
   * @param \Drupal\Core\Asset\AttachedAssetsInterface $assets
   *   The assets attached to the current response.
   * @param bool $optimize
   *   Whether to apply the JavaScript asset collection optimizer, to return
   *   optimized JavaScript asset collections rather than an unoptimized ones.
   *
   * @return array
   *   A nested array containing 2 values:
   *   - at index zero: the (possibly optimized) collection of JavaScript assets
   *     for the top of the page
   *   - at index one: the (possibly optimized) collection of JavaScript assets
   *     for the bottom of the page
   */
  public function getJsAssets(AttachedAssetsInterface $assets, $optimize);

}
