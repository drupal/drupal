<?php

/**
 * @file
 * Contains \Drupal\Core\Asset\LibraryDiscovery.
 */

namespace Drupal\Core\Asset;

use Drupal\Component\Serialization\Yaml;
use Drupal\Component\Serialization\Exception\InvalidDataTypeException;
use Drupal\Core\Asset\Exception\IncompleteLibraryDefinitionException;
use Drupal\Core\Asset\Exception\InvalidLibraryFileException;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;

/**
 * Discovers available asset libraries in Drupal.
 */
class LibraryDiscovery implements LibraryDiscoveryInterface {

  /**
   * Stores the library information keyed by extension.
   *
   * @var array
   */
  protected $libraries;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a new LibraryDiscovery instance.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    $this->cache = $cache_backend;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function getLibrariesByExtension($extension) {
    $this->ensureLibraryInformation($extension);
    return $this->libraries[$extension];
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraryByName($extension, $name) {
    $this->ensureLibraryInformation($extension);
    return isset($this->libraries[$extension][$name]) ? $this->libraries[$extension][$name] : FALSE;
  }

  /**
   * Ensures that the libraries property is filled.
   *
   * @param string $extension
   *   The name of the extension that registered a library.
   */
  protected function ensureLibraryInformation($extension) {
    $this->getCache($extension);
    if (!isset($this->libraries[$extension])) {
      if ($information = $this->buildLibrariesByExtension($extension)) {
        $this->libraries[$extension] = $information;
      }
      else {
        $this->libraries[$extension] = FALSE;
      }
      $this->setCache($extension, $this->libraries[$extension]);
    }
  }

  /**
   * Fills up the libraries property from cache, if available.
   *
   * @param string $extension
   *   The name of the extension that registered a library.
   */
  protected function getCache($extension) {
    if (!isset($this->libraries[$extension])) {
      if ($cache = $this->cache->get('library:info:' . $extension)) {
        $this->libraries[$extension] = $cache->data;
      }
    }
  }

  /**
   * Sets the library information into a cache entry.
   *
   * @param string $extension
   *   The name of the extension that registered a library.
   *
   * @param bool|array $information
   *   All library definitions of the passed extension or FALSE if no
   *   information is available.
   */
  protected function setCache($extension, $information) {
    $this->cache->set('library:info:' . $extension, $information, Cache::PERMANENT, array(
      'extension' => array(TRUE, $extension),
      'library_info' => array(TRUE),
    ));
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
  protected function buildLibrariesByExtension($extension) {
    $this->libraries[$extension] = array();
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
    $library_file = $path . '/' . $extension . '.libraries.yml';

    if ($library_file && file_exists(DRUPAL_ROOT . '/' . $library_file)) {
      $this->libraries[$extension] = array();
      $this->parseLibraryInfo($extension, $library_file);
    }

    foreach ($this->libraries[$extension] as $id => &$library) {
      if (!isset($library['js']) && !isset($library['css']) && !isset($library['settings'])) {
        throw new IncompleteLibraryDefinitionException(sprintf("Incomplete library definition for '%s' in %s", $id, $library_file));
      }
      $library += array('dependencies' => array(), 'js' => array(), 'css' => array());

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

          $library[$type][] = $options;
        }
      }

      // @todo Introduce drupal_add_settings().
      if (isset($library['settings'])) {
        $library['js'][] = array(
          'type' => 'setting',
          'data' => $library['settings'],
        );
        unset($library['settings']);
      }
      // @todo Convert all uses of #attached[library][]=array('provider','name')
      //   into #attached[library][]='provider/name' and remove this.
      foreach ($library['dependencies'] as $i => $dependency) {
        $library['dependencies'][$i] = $dependency;
      }
    }
    return $this->libraries[$extension];
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

  /**
   * Parses a given library file and allows module to alter it.
   *
   * This method sets the parsed information onto the library property.
   *
   * @param string $extension
   *   The name of the extension that registered a library.
   * @param string $library_file
   *   The relative filename to the DRUPAL_ROOT of the wanted library file.
   *
   * @throws \Drupal\Core\Asset\Exception\InvalidLibraryFileException
   *   Thrown when a parser exception got thrown.
   */
  protected function parseLibraryInfo($extension, $library_file) {
    try {
      $this->libraries[$extension] = Yaml::decode(file_get_contents(DRUPAL_ROOT . '/' . $library_file));
    }
    catch (InvalidDataTypeException $e) {
      // Rethrow a more helpful exception to provide context.
      throw new InvalidLibraryFileException(sprintf('Invalid library definition in %s: %s', $library_file, $e->getMessage()), 0, $e);
    }
    // Allow modules to alter the module's registered libraries.
    $this->moduleHandler->alter('library_info', $this->libraries[$extension], $extension);
  }

}
