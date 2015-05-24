<?php

/**
 * @file
 * Contains \Drupal\Core\Asset\LibraryDiscoveryParser.
 */

namespace Drupal\Core\Asset;

use Drupal\Core\Asset\Exception\IncompleteLibraryDefinitionException;
use Drupal\Core\Asset\Exception\InvalidLibraryFileException;
use Drupal\Core\Asset\Exception\LibraryDefinitionMissingLicenseException;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\Component\Serialization\Exception\InvalidDataTypeException;
use Drupal\Component\Serialization\Yaml;
use Drupal\Component\Utility\NestedArray;

/**
 * Parses library files to get extension data.
 */
class LibraryDiscoveryParser {

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
   * The app root.
   *
   * @var string
   */
  protected $root;

  /**
   * Constructs a new LibraryDiscoveryParser instance.
   *
   * @param string $root
   *   The app root.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct($root, ModuleHandlerInterface $module_handler, ThemeManagerInterface $theme_manager) {
    $this->root = $root;
    $this->moduleHandler = $module_handler;
    $this->themeManager = $theme_manager;
  }

  /**
   * Parses and builds up all the libraries information of an extension.
   *
   * @param string $extension
   *   The name of the extension that registered a library.
   *
   * @return array
   *   All library definitions of the passed extension.
   *
   * @throws \Drupal\Core\Asset\Exception\IncompleteLibraryDefinitionException
   *   Thrown when a library has no js/css/setting.
   * @throws \UnexpectedValueException
   *   Thrown when a js file defines a positive weight.
   */
  public function buildByExtension($extension) {
    $libraries = array();

    if ($extension === 'core') {
      $path = 'core';
      $extension_type = 'core';
    }
    else {
      if ($this->moduleHandler->moduleExists($extension)) {
        $extension_type = 'module';
      }
      else {
        $extension_type = 'theme';
      }
      $path = $this->drupalGetPath($extension_type, $extension);
    }

    $libraries = $this->parseLibraryInfo($extension, $path);

    foreach ($libraries as $id => &$library) {
      if (!isset($library['js']) && !isset($library['css']) && !isset($library['drupalSettings'])) {
        throw new IncompleteLibraryDefinitionException(sprintf("Incomplete library definition for definition '%s' in extension '%s'", $id, $extension));
      }
      $library += array('dependencies' => array(), 'js' => array(), 'css' => array());

      if (isset($library['header']) && !is_bool($library['header'])) {
        throw new \LogicException(sprintf("The 'header' key in the library definition '%s' in extension '%s' is invalid: it must be a boolean.", $id, $extension));
      }

      if (isset($library['version'])) {
        // @todo Retrieve version of a non-core extension.
        if ($library['version'] === 'VERSION') {
          $library['version'] = \Drupal::VERSION;
        }
        // Remove 'v' prefix from external library versions.
        elseif ($library['version'][0] === 'v') {
          $library['version'] = substr($library['version'], 1);
        }
      }

      // If this is a 3rd party library, the license info is required.
      if (isset($library['remote']) && !isset($library['license'])) {
        throw new LibraryDefinitionMissingLicenseException(sprintf("Missing license information in library definition for definition '%s' extension '%s': it has a remote, but no license.", $id, $extension));
      }

      // Assign Drupal's license to libraries that don't have license info.
      if (!isset($library['license'])) {
        $library['license'] = array(
          'name' => 'GNU-GPL-2.0-or-later',
          'url' => 'https://www.drupal.org/licensing/faq',
          'gpl-compatible' => TRUE,
        );
      }

      foreach (array('js', 'css') as $type) {
        // Prepare (flatten) the SMACSS-categorized definitions.
        // @todo After Asset(ic) changes, retain the definitions as-is and
        //   properly resolve dependencies for all (css) libraries per category,
        //   and only once prior to rendering out an HTML page.
        if ($type == 'css' && !empty($library[$type])) {
          foreach ($library[$type] as $category => $files) {
            foreach ($files as $source => $options) {
              if (!isset($options['weight'])) {
                $options['weight'] = 0;
              }
              // Apply the corresponding weight defined by CSS_* constants.
              $options['weight'] += constant('CSS_' . strtoupper($category));
              $library[$type][$source] = $options;
            }
            unset($library[$type][$category]);
          }
        }
        foreach ($library[$type] as $source => $options) {
          unset($library[$type][$source]);
          // Allow to omit the options hashmap in YAML declarations.
          if (!is_array($options)) {
            $options = array();
          }
          if ($type == 'js' && isset($options['weight']) && $options['weight'] > 0) {
            throw new \UnexpectedValueException("The $extension/$id library defines a positive weight for '$source'. Only negative weights are allowed (but should be avoided). Instead of a positive weight, specify accurate dependencies for this library.");
          }
          // Unconditionally apply default groups for the defined asset files.
          // The library system is a dependency management system. Each library
          // properly specifies its dependencies instead of relying on a custom
          // processing order.
          if ($type == 'js') {
            $options['group'] = JS_LIBRARY;
          }
          elseif ($type == 'css') {
            $options['group'] = $extension_type == 'theme' ? CSS_AGGREGATE_THEME : CSS_AGGREGATE_DEFAULT;
          }
          // By default, all library assets are files.
          if (!isset($options['type'])) {
            $options['type'] = 'file';
          }
          if ($options['type'] == 'external') {
            $options['data'] = $source;
          }
          // Determine the file asset URI.
          else {
            if ($source[0] === '/') {
              // An absolute path maps to DRUPAL_ROOT / base_path().
              if ($source[1] !== '/') {
                $options['data'] = substr($source, 1);
              }
              // A protocol-free URI (e.g., //cdn.com/example.js) is external.
              else {
                $options['type'] = 'external';
                $options['data'] = $source;
              }
            }
            // A stream wrapper URI (e.g., public://generated_js/example.js).
            elseif ($this->fileValidUri($source)) {
              $options['data'] = $source;
            }
            // By default, file paths are relative to the registering extension.
            else {
              $options['data'] = $path . '/' . $source;
            }
          }

          if (!isset($library['version'])) {
            // @todo Get the information from the extension.
            $options['version'] = -1;
          }
          else {
            $options['version'] = $library['version'];
          }

          // Set the 'minified' flag on JS file assets, default to FALSE.
          if ($type == 'js' && $options['type'] == 'file') {
            $options['minified'] = isset($options['minified']) ? $options['minified'] : FALSE;
          }

          $library[$type][] = $options;
        }
      }
    }

    return $libraries;
  }

  /**
   * Parses a given library file and allows module to alter it.
   *
   * This method sets the parsed information onto the library property.
   *
   * Library information is parsed from *.libraries.yml files; see
   * editor.library.yml for an example. Every library must have at least one js
   * or css entry. Each entry starts with a machine name and defines the
   * following elements:
   * - js: A list of JavaScript files to include. Each file is keyed by the file
   *   path. An item can have several attributes (like HTML
   *   attributes). For example:
   *   @code
   *   js:
   *     path/js/file.js: { attributes: { defer: true } }
   *   @endcode
   *   If the file has no special attributes, just use an empty object:
   *   @code
   *   js:
   *     path/js/file.js: {}
   *   @endcode
   *   The path of the file is relative to the module or theme directory, unless
   *   it starts with a /, in which case it is relative to the Drupal root. If
   *   the file path starts with //, it will be treated as a protocol-free,
   *   external resource (e.g., //cdn.com/library.js). Full URLs
   *   (e.g., http://cdn.com/library.js) as well as URLs that use a valid
   *   stream wrapper (e.g., public://path/to/file.js) are also supported.
   * - css: A list of categories for which the library provides CSS files. The
   *   available categories are:
   *   - base
   *   - layout
   *   - component
   *   - state
   *   - theme
   *   Each category is itself a key for a sub-list of CSS files to include:
   *   @code
   *   css:
   *     component:
   *       css/file.css: {}
   *   @endcode
   *   Just like with JavaScript files, each CSS file is the key of an object
   *   that can define specific attributes. The format of the file path is the
   *   same as for the JavaScript files.
   * - dependencies: A list of libraries this library depends on.
   * - version: The library version. The string "VERSION" can be used to mean
   *   the current Drupal core version.
   * - header: By default, JavaScript files are included in the footer. If the
   *   script must be included in the header (along with all its dependencies),
   *   set this to true. Defaults to false.
   * - minified: If the file is already minified, set this to true to avoid
   *   minifying it again. Defaults to false.
   * - remote: If the library is a third-party script, this provides the
   *   repository URL for reference.
   * - license: If the remote property is set, the license information is
   *   required. It has 3 properties:
   *   - name: The human-readable name of the license.
   *   - url: The URL of the license file/information for the version of the
   *     library used.
   *   - gpl-compatible: A Boolean for whether this library is GPL compatible.
   *
   * See https://www.drupal.org/node/2274843#define-library for more
   * information.
   *
   * @param string $extension
   *   The name of the extension that registered a library.
   * @param string $path
   *   The relative path to the extension.
   *
   * @return array
   *   An array of parsed library data.
   *
   * @throws \Drupal\Core\Asset\Exception\InvalidLibraryFileException
   *   Thrown when a parser exception got thrown.
   */
  protected function parseLibraryInfo($extension, $path) {
    $libraries = [];

    $library_file = $path . '/' . $extension . '.libraries.yml';
    if (file_exists($this->root . '/' . $library_file)) {
      try {
        $libraries = Yaml::decode(file_get_contents($this->root . '/' . $library_file));
      }
      catch (InvalidDataTypeException $e) {
        // Rethrow a more helpful exception to provide context.
        throw new InvalidLibraryFileException(sprintf('Invalid library definition in %s: %s', $library_file, $e->getMessage()), 0, $e);
      }
    }

    // Allow modules to add dynamic library definitions.
    $hook = 'library_info_build';
    if ($this->moduleHandler->implementsHook($extension, $hook)) {
      $libraries = NestedArray::mergeDeep($libraries, $this->moduleHandler->invoke($extension, $hook));
    }

    // Allow modules to alter the module's registered libraries.
    $this->moduleHandler->alter('library_info', $libraries, $extension);
    $this->themeManager->alter('library_info', $libraries, $extension);

    return $libraries;
  }

  /**
   * Wraps drupal_get_path().
   */
  protected function drupalGetPath($type, $name) {
    return drupal_get_path($type, $name);
  }

  /**
   * Wraps file_valid_uri().
   */
  protected function fileValidUri($source) {
    return file_valid_uri($source);
  }

}
