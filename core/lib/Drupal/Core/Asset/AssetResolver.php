<?php

namespace Drupal\Core\Asset;

use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Language\LanguageInterface;
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
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

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
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend.
   */
  public function __construct(LibraryDiscoveryInterface $library_discovery, LibraryDependencyResolverInterface $library_dependency_resolver, ModuleHandlerInterface $module_handler, ThemeManagerInterface $theme_manager, LanguageManagerInterface $language_manager, CacheBackendInterface $cache) {
    $this->libraryDiscovery = $library_discovery;
    $this->libraryDependencyResolver = $library_dependency_resolver;
    $this->moduleHandler = $module_handler;
    $this->themeManager = $theme_manager;
    $this->languageManager = $language_manager;
    $this->cache = $cache;
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
  public function getCssAssets(AttachedAssetsInterface $assets, $optimize, LanguageInterface $language = NULL) {
    if (!isset($language)) {
      $language = $this->languageManager->getCurrentLanguage();
    }
    $theme_info = $this->themeManager->getActiveTheme();
    // Add the theme name to the cache key since themes may implement
    // hook_library_info_alter().
    $libraries_to_load = $this->getLibrariesToLoad($assets);
    $cid = 'css:' . $theme_info->getName() . ':' . $language->getId() . Crypt::hashBase64(serialize($libraries_to_load)) . (int) $optimize;
    if ($cached = $this->cache->get($cid)) {
      return $cached->data;
    }

    $css = [];
    $default_options = [
      'type' => 'file',
      'group' => CSS_AGGREGATE_DEFAULT,
      'weight' => 0,
      'media' => 'all',
      'preprocess' => TRUE,
    ];

    foreach ($libraries_to_load as $library) {
      [$extension, $name] = explode('/', $library, 2);
      $definition = $this->libraryDiscovery->getLibraryByName($extension, $name);
      if (isset($definition['css'])) {
        foreach ($definition['css'] as $options) {
          $options += $default_options;
          // Copy the asset library license information to each file.
          $options['license'] = $definition['license'];

          // Files with a query string cannot be preprocessed.
          if ($options['type'] === 'file' && $options['preprocess'] && str_contains($options['data'], '?')) {
            $options['preprocess'] = FALSE;
          }

          // Always add a tiny value to the weight, to conserve the insertion
          // order.
          $options['weight'] += count($css) / 30000;

          // CSS files are being keyed by the full path.
          $css[$options['data']] = $options;
        }
      }
    }

    // Allow modules and themes to alter the CSS assets.
    $this->moduleHandler->alter('css', $css, $assets, $language);
    $this->themeManager->alter('css', $css, $assets, $language);

    // Sort CSS items, so that they appear in the correct order.
    uasort($css, [static::class, 'sort']);

    if ($optimize) {
      $css = \Drupal::service('asset.css.collection_optimizer')->optimize($css, $libraries_to_load, $language);
    }
    $this->cache->set($cid, $css, CacheBackendInterface::CACHE_PERMANENT, ['library_info']);

    return $css;
  }

  /**
   * Returns the JavaScript settings assets for this response's libraries.
   *
   * Gathers all drupalSettings from all libraries in the attached assets
   * collection and merges them.
   *
   * @param \Drupal\Core\Asset\AttachedAssetsInterface $assets
   *   The assets attached to the current response.
   *
   * @return array
   *   A (possibly optimized) collection of JavaScript assets.
   */
  protected function getJsSettingsAssets(AttachedAssetsInterface $assets) {
    $settings = [];

    foreach ($this->getLibrariesToLoad($assets) as $library) {
      [$extension, $name] = explode('/', $library, 2);
      $definition = $this->libraryDiscovery->getLibraryByName($extension, $name);
      if (isset($definition['drupalSettings'])) {
        $settings = NestedArray::mergeDeepArray([$settings, $definition['drupalSettings']], TRUE);
      }
    }

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function getJsAssets(AttachedAssetsInterface $assets, $optimize, LanguageInterface $language = NULL) {
    if (!isset($language)) {
      $language = $this->languageManager->getCurrentLanguage();
    }
    $theme_info = $this->themeManager->getActiveTheme();
    // Add the theme name to the cache key since themes may implement
    // hook_library_info_alter(). Additionally add the current language to
    // support translation of JavaScript files via hook_js_alter().
    $libraries_to_load = $this->getLibrariesToLoad($assets);
    $cid = 'js:' . $theme_info->getName() . ':' . $language->getId() . ':' . Crypt::hashBase64(serialize($libraries_to_load)) . (int) (count($assets->getSettings()) > 0) . (int) $optimize;

    if ($cached = $this->cache->get($cid)) {
      [$js_assets_header, $js_assets_footer, $settings, $settings_in_header] = $cached->data;
    }
    else {
      $javascript = [];
      $default_options = [
        'type' => 'file',
        'group' => JS_DEFAULT,
        'weight' => 0,
        'cache' => TRUE,
        'preprocess' => TRUE,
        'attributes' => [],
        'version' => NULL,
      ];

      // Collect all libraries that contain JS assets and are in the header.
      $header_js_libraries = [];
      foreach ($libraries_to_load as $library) {
        [$extension, $name] = explode('/', $library, 2);
        $definition = $this->libraryDiscovery->getLibraryByName($extension, $name);
        if (isset($definition['js']) && !empty($definition['header'])) {
          $header_js_libraries[] = $library;
        }
      }
      // The current list of header JS libraries are only those libraries that
      // are in the header, but their dependencies must also be loaded for them
      // to function correctly, so update the list with those.
      $header_js_libraries = $this->libraryDependencyResolver->getLibrariesWithDependencies($header_js_libraries);

      foreach ($libraries_to_load as $library) {
        [$extension, $name] = explode('/', $library, 2);
        $definition = $this->libraryDiscovery->getLibraryByName($extension, $name);
        if (isset($definition['js'])) {
          foreach ($definition['js'] as $options) {
            $options += $default_options;
            // Copy the asset library license information to each file.
            $options['license'] = $definition['license'];

            // 'scope' is a calculated option, based on which libraries are
            // marked to be loaded from the header (see above).
            $options['scope'] = in_array($library, $header_js_libraries) ? 'header' : 'footer';

            // Preprocess can only be set if caching is enabled and no
            // attributes are set.
            $options['preprocess'] = $options['cache'] && empty($options['attributes']) ? $options['preprocess'] : FALSE;

            // Always add a tiny value to the weight, to conserve the insertion
            // order.
            $options['weight'] += count($javascript) / 30000;

            // Local and external files must keep their name as the associative
            // key so the same JavaScript file is not added twice.
            $javascript[$options['data']] = $options;
          }
        }
      }

      // Allow modules and themes to alter the JavaScript assets.
      $this->moduleHandler->alter('js', $javascript, $assets, $language);
      $this->themeManager->alter('js', $javascript, $assets, $language);

      // Sort JavaScript assets, so that they appear in the correct order.
      uasort($javascript, [static::class, 'sort']);

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
        $js_assets_header = $collection_optimizer->optimize($js_assets_header, $libraries_to_load);
        $js_assets_footer = $collection_optimizer->optimize($js_assets_footer, $libraries_to_load);
      }

      // If the core/drupalSettings library is being loaded or is already
      // loaded, get the JavaScript settings assets, and convert them into a
      // single "regular" JavaScript asset.
      $libraries_to_load = $this->getLibrariesToLoad($assets);
      $settings_required = in_array('core/drupalSettings', $libraries_to_load) || in_array('core/drupalSettings', $this->libraryDependencyResolver->getLibrariesWithDependencies($assets->getAlreadyLoadedLibraries()));
      $settings_have_changed = count($libraries_to_load) > 0 || count($assets->getSettings()) > 0;

      // Initialize settings to FALSE since they are not needed by default. This
      // distinguishes between an empty array which must still allow
      // hook_js_settings_alter() to be run.
      $settings = FALSE;
      if ($settings_required && $settings_have_changed) {
        $settings = $this->getJsSettingsAssets($assets);
        // Allow modules to add cached JavaScript settings.
        $this->moduleHandler->invokeAllWith('js_settings_build', function (callable $hook, string $module) use (&$settings, $assets) {
          $hook($settings, $assets);
        });
      }
      $settings_in_header = in_array('core/drupalSettings', $header_js_libraries);
      $this->cache->set($cid, [$js_assets_header, $js_assets_footer, $settings, $settings_in_header], CacheBackendInterface::CACHE_PERMANENT, ['library_info']);
    }

    if ($settings !== FALSE) {
      // Attached settings override both library definitions and
      // hook_js_settings_build().
      $settings = NestedArray::mergeDeepArray([$settings, $assets->getSettings()], TRUE);
      // Allow modules and themes to alter the JavaScript settings.
      $this->moduleHandler->alter('js_settings', $settings, $assets);
      $this->themeManager->alter('js_settings', $settings, $assets);
      // Update the $assets object accordingly, so that it reflects the final
      // settings.
      $assets->setSettings($settings);
      $settings_as_inline_javascript = [
        'type' => 'setting',
        'group' => JS_SETTING,
        'weight' => 0,
        'data' => $settings,
      ];
      $settings_js_asset = ['drupalSettings' => $settings_as_inline_javascript];
      // Prepend to the list of JS assets, to render it first. Preferably in
      // the footer, but in the header if necessary.
      if ($settings_in_header) {
        $js_assets_header = $settings_js_asset + $js_assets_header;
      }
      else {
        $js_assets_footer = $settings_js_asset + $js_assets_footer;
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
   * Callback for uasort().
   *
   * This sort order helps optimize front-end performance while providing
   * modules and themes with the necessary control for ordering the CSS and
   * JavaScript appearing on a page.
   *
   * @param array $a
   *   First item for comparison. The compared items should be associative
   *   arrays of member items.
   * @param array $b
   *   Second item for comparison.
   *
   * @return int
   *   The comparison result for uasort().
   */
  public static function sort(array $a, array $b) {
    // First order by group, so that all items in the CSS_AGGREGATE_DEFAULT
    // group appear before items in the CSS_AGGREGATE_THEME group. Modules may
    // create additional groups by defining their own constants.
    if ($a['group'] < $b['group']) {
      return -1;
    }
    elseif ($a['group'] > $b['group']) {
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
