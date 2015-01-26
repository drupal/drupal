<?php
/**
 * @file
 * Contains \Drupal\Core\Asset\AssetResolver.
 */

namespace Drupal\Core\Asset;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Theme\ThemeManagerInterface;

/**
 * The default asset resolver.
 */
class AssetResolver implements AssetResolverInterface {

  /**
   * The library discovery service.
   *
   * @var \Drupal\Core\Asset\LibraryDiscoveryInterface
   */
  protected $libraryDiscovery;

  /**
   * The library dependency resolver.
   *
   * @var \Drupal\Core\Asset\LibraryDependencyResolverInterface
   */
  protected $libraryDependencyResolver;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * Constructs a new AssetResolver instance.
   *
   * @param \Drupal\Core\Asset\LibraryDiscoveryInterface $library_discovery
   *   The library discovery service.
   * @param \Drupal\Core\Asset\LibraryDependencyResolverInterface $library_dependency_resolver
   *   The library dependency resolver.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $theme_manager
   *   The theme manager.
   */
  public function __construct(LibraryDiscoveryInterface $library_discovery, LibraryDependencyResolverInterface $library_dependency_resolver, ModuleHandlerInterface $module_handler, ThemeManagerInterface $theme_manager) {
    $this->libraryDiscovery = $library_discovery;
    $this->libraryDependencyResolver = $library_dependency_resolver;
    $this->moduleHandler = $module_handler;
    $this->themeManager = $theme_manager;
  }

  /**
   * Returns the libraries that need to be loaded.
   *
   * For example, with core/a depending on core/c and core/b on core/d:
   * @code
   * $assets = new AttachedAssets();
   * $assets->setLibraries(['core/a', 'core/b', 'core/c']);
   * $assets->setAlreadyLoadedLibraries(['core/c']);
   * $resolver->getLibrariesToLoad($assets) === ['core/a', 'core/b', 'core/d']
   * @endcode
   *
   * @param \Drupal\Core\Asset\AttachedAssetsInterface $assets
   *   The assets attached to the current response.
   *
   * @return string[]
   *   A list of libraries and their dependencies, in the order they should be
   *   loaded, excluding any libraries that have already been loaded.
   */
  protected function getLibrariesToLoad(AttachedAssetsInterface $assets) {
    return array_diff(
      $this->libraryDependencyResolver->getLibrariesWithDependencies($assets->getLibraries()),
      $this->libraryDependencyResolver->getLibrariesWithDependencies($assets->getAlreadyLoadedLibraries())
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCssAssets(AttachedAssetsInterface $assets, $optimize) {
    $theme_info = $this->themeManager->getActiveTheme();

    $css = [];

    foreach ($this->getLibrariesToLoad($assets) as $library) {
      list($extension, $name) = explode('/', $library, 2);
      $definition = $this->libraryDiscovery->getLibraryByName($extension, $name);
      if (isset($definition['css'])) {
        foreach ($definition['css'] as $options) {
          $options += array(
            'type' => 'file',
            'group' => CSS_AGGREGATE_DEFAULT,
            'weight' => 0,
            'every_page' => FALSE,
            'media' => 'all',
            'preprocess' => TRUE,
            'browsers' => array(),
          );
          $options['browsers'] += array(
            'IE' => TRUE,
            '!IE' => TRUE,
          );

          // Files with a query string cannot be preprocessed.
          if ($options['type'] === 'file' && $options['preprocess'] && strpos($options['data'], '?') !== FALSE) {
            $options['preprocess'] = FALSE;
          }

          // Always add a tiny value to the weight, to conserve the insertion
          // order.
          $options['weight'] += count($css) / 1000;

          // Add the data to the CSS array depending on the type.
          switch ($options['type']) {
            case 'file':
              // Local CSS files are keyed by basename; if a file with the same
              // basename is added more than once, it gets overridden.
              // By default, take over the filename as basename.
              if (!isset($options['basename'])) {
                $options['basename'] = drupal_basename($options['data']);
              }
              $css[$options['basename']] = $options;
              break;

            default:
              // External files are keyed by their full URI, so the same CSS
              // file is not added twice.
              $css[$options['data']] = $options;
          }
        }
      }
    }

    // Allow modules and themes to alter the CSS assets.
    $this->moduleHandler->alter('css', $css, $assets);
    $this->themeManager->alter('css', $css, $assets);

    // Sort CSS items, so that they appear in the correct order.
    uasort($css, 'static::sort');

    // Allow themes to remove CSS files by basename.
    if ($stylesheet_remove = $theme_info->getStyleSheetsRemove()) {
      foreach ($css as $key => $options) {
        if (isset($options['basename']) && isset($stylesheet_remove[$options['basename']])) {
          unset($css[$key]);
        }
      }
    }
    // Allow themes to conditionally override CSS files by basename.
    if ($stylesheet_override = $theme_info->getStyleSheetsOverride()) {
      foreach ($css as $key => $options) {
        if (isset($options['basename']) && isset($stylesheet_override[$options['basename']])) {
          $css[$key]['data'] = $stylesheet_override[$options['basename']];
        }
      }
    }

    if ($optimize) {
      $css = \Drupal::service('asset.css.collection_optimizer')->optimize($css);
    }

    return $css;
  }

  /**
   * Returns the JavaScript settings assets for this response's libraries.
   *
   * Gathers all drupalSettings from all libraries in the attached assets
   * collection and merges them, then it merges individual attached settings,
   * and finally invokes hook_js_settings_alter() to allow alterations of
   * JavaScript settings by modules and themes.
   *
   * @param \Drupal\Core\Asset\AttachedAssetsInterface $assets
   *   The assets attached to the current response.
   * @return array
   *   A (possibly optimized) collection of JavaScript assets.
   */
  protected function getJsSettingsAssets(AttachedAssetsInterface $assets) {
    $settings = [];

    foreach ($this->getLibrariesToLoad($assets) as $library) {
      list($extension, $name) = explode('/', $library, 2);
      $definition = $this->libraryDiscovery->getLibraryByName($extension, $name);
      if (isset($definition['drupalSettings'])) {
        $settings = NestedArray::mergeDeepArray([$settings, $definition['drupalSettings']], TRUE);
      }
    }

    // Attached settings win over settings in libraries.
    $settings = NestedArray::mergeDeepArray([$settings, $assets->getSettings()], TRUE);

    // Allow modules and themes to alter the JavaScript settings.
    $this->moduleHandler->alter('js_settings', $settings, $assets);
    $this->themeManager->alter('js_settings', $settings, $assets);

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function getJsAssets(AttachedAssetsInterface $assets, $optimize) {
    $javascript = [];
    $libraries_to_load = $this->getLibrariesToLoad($assets);

    // Collect all libraries that contain JS assets and are in the header.
    $header_js_libraries = [];
    foreach ($libraries_to_load as $library) {
      list($extension, $name) = explode('/', $library, 2);
      $definition = $this->libraryDiscovery->getLibraryByName($extension, $name);
      if (isset($definition['js']) && !empty($definition['header'])) {
        $header_js_libraries[] = $library;
      }
    }
    // The current list of header JS libraries are only those libraries that are
    // in the header, but their dependencies must also be loaded for them to
    // function correctly, so update the list with those.
    $header_js_libraries = $this->libraryDependencyResolver->getLibrariesWithDependencies($header_js_libraries);

    foreach ($libraries_to_load as $library) {
      list($extension, $name) = explode('/', $library, 2);
      $definition = $this->libraryDiscovery->getLibraryByName($extension, $name);
      if (isset($definition['js'])) {
        foreach ($definition['js'] as $options) {
          $options += array(
            'type' => 'file',
            'group' => JS_DEFAULT,
            'every_page' => FALSE,
            'weight' => 0,
            'cache' => TRUE,
            'preprocess' => TRUE,
            'attributes' => array(),
            'version' => NULL,
            'browsers' => array(),
          );

          // 'scope' is a calculated option, based on which libraries are marked
          // to be loaded from the header (see above).
          $options['scope'] = in_array($library, $header_js_libraries) ? 'header' : 'footer';

          // Preprocess can only be set if caching is enabled and no attributes
          // are set.
          $options['preprocess'] = $options['cache'] && empty($options['attributes']) ? $options['preprocess'] : FALSE;

          // Always add a tiny value to the weight, to conserve the insertion
          // order.
          $options['weight'] += count($javascript) / 1000;

          // Local and external files must keep their name as the associative
          // key so the same JavaScript file is not added twice.
          $javascript[$options['data']] = $options;
        }
      }
    }

    // Allow modules and themes to alter the JavaScript assets.
    $this->moduleHandler->alter('js', $javascript, $assets);
    $this->themeManager->alter('js', $javascript, $assets);

    // Sort JavaScript assets, so that they appear in the correct order.
    uasort($javascript, 'static::sort');

    // Prepare the return value: filter JavaScript assets per scope.
    $js_assets_header = [];
    $js_assets_footer = [];
    foreach ($javascript as $key => $item) {
      if ($item['scope'] == 'header') {
        $js_assets_header[$key] = $item;
      }
      elseif ($item['scope'] == 'footer') {
        $js_assets_footer[$key] = $item;
      }
    }

    if ($optimize) {
      $collection_optimizer = \Drupal::service('asset.js.collection_optimizer');
      $js_assets_header = $collection_optimizer->optimize($js_assets_header);
      $js_assets_footer = $collection_optimizer->optimize($js_assets_footer);
    }

    // If the core/drupalSettings library is being loaded or is already loaded,
    // get the JavaScript settings assets, and convert them into a single
    // "regular" JavaScript asset.
    $libraries_to_load = $this->getLibrariesToLoad($assets);
    $settings_needed = in_array('core/drupalSettings', $libraries_to_load) || in_array('core/drupalSettings', $this->libraryDependencyResolver->getLibrariesWithDependencies($assets->getAlreadyLoadedLibraries()));
    $settings_have_changed = count($libraries_to_load) > 0 || count($assets->getSettings()) > 0;
    if ($settings_needed && $settings_have_changed) {
      $settings = $this->getJsSettingsAssets($assets);
      if (!empty($settings)) {
        $settings_as_inline_javascript = [
          'type' => 'setting',
          'group' => JS_SETTING,
          'every_page' => TRUE,
          'weight' => 0,
          'browsers' => array(),
          'data' => $settings,
        ];
        $settings_js_asset = ['drupalSettings' => $settings_as_inline_javascript];
        // Prepend to the list of JS assets, to render it first. Preferably in
        // the footer, but in the header if necessary.
        if (in_array('core/drupalSettings', $header_js_libraries)) {
          $js_assets_header = $settings_js_asset + $js_assets_header;
        }
        else {
          $js_assets_footer = $settings_js_asset + $js_assets_footer;
        }
      }
    }

    return [
      $js_assets_header,
      $js_assets_footer,
    ];
  }

  /**
   * Sorts CSS and JavaScript resources.
   *
   * This sort order helps optimize front-end performance while providing
   * modules and themes with the necessary control for ordering the CSS and
   * JavaScript appearing on a page.
   *
   * @param $a
   *   First item for comparison. The compared items should be associative
   *   arrays of member items.
   * @param $b
   *   Second item for comparison.
   *
   * @return int
   */
  public static function sort($a, $b) {
    // First order by group, so that all items in the CSS_AGGREGATE_DEFAULT
    // group appear before items in the CSS_AGGREGATE_THEME group. Modules may
    // create additional groups by defining their own constants.
    if ($a['group'] < $b['group']) {
      return -1;
    }
    elseif ($a['group'] > $b['group']) {
      return 1;
    }
    // Within a group, order all infrequently needed, page-specific files after
    // common files needed throughout the website. Separating this way allows
    // for the aggregate file generated for all of the common files to be reused
    // across a site visit without being cut by a page using a less common file.
    elseif ($a['every_page'] && !$b['every_page']) {
      return -1;
    }
    elseif (!$a['every_page'] && $b['every_page']) {
      return 1;
    }
    // Finally, order by weight.
    elseif ($a['weight'] < $b['weight']) {
      return -1;
    }
    elseif ($a['weight'] > $b['weight']) {
      return 1;
    }
    else {
      return 0;
    }
  }

}
