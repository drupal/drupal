<?php

namespace Drupal\Core\Extension;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\Core\Extension\Exception\UnknownExtensionException;
use Drupal\Core\State\StateInterface;

/**
 * Provides available extensions.
 *
 * The extension list is per extension type, like module, theme and profile.
 *
 * @internal
 *   This class is not yet stable and therefore there are no guarantees that the
 *   internal implementations including constructor signature and protected
 *   properties / methods will not change over time. This will be reviewed after
 *   https://www.drupal.org/project/drupal/issues/2940481
 */
abstract class ExtensionList {

  /**
   * The type of the extension.
   *
   * Possible values: "module", "theme", "profile" or "database_driver".
   *
   * @var string
   */
  protected $type;

  /**
   * The app root.
   *
   * @var string
   */
  protected $root;

  /**
   * The cache.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Default values to be merged into *.info.yml file arrays.
   *
   * @var mixed[]
   */
  protected $defaults = [];

  /**
   * The info parser.
   *
   * @var \Drupal\Core\Extension\InfoParserInterface
   */
  protected $infoParser;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The cached extensions.
   *
   * @var \Drupal\Core\Extension\Extension[]|null
   */
  protected $extensions;

  /**
   * Static caching for extension info.
   *
   * Access this property's value through static::getAllInfo().
   *
   * @var array[]|null
   *   Keys are extension names, and values their info arrays (mixed[]).
   *
   * @see \Drupal\Core\Extension\ExtensionList::getAllAvailableInfo
   */
  protected $extensionInfo;

  /**
   * A list of extension folder names keyed by extension name.
   *
   * @var string[]|null
   */
  protected $pathNames;

  /**
   * A list of extension folder names directly added in code (not discovered).
   *
   * It is important to keep a separate list to ensure that it takes priority
   * over the discovered extension folders.
   *
   * @var string[]
   *
   * @internal
   */
  protected $addedPathNames = [];

  /**
   * The state store.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The install profile used by the site.
   *
   * @var string|false|null
   */
  protected $installProfile;

  /**
   * Constructs a new instance.
   *
   * @param string $root
   *   The app root.
   * @param string $type
   *   The extension type.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache.
   * @param \Drupal\Core\Extension\InfoParserInterface $info_parser
   *   The info parser.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state.
   * @param string $install_profile
   *   The install profile used by the site.
   */
  public function __construct($root, $type, CacheBackendInterface $cache, InfoParserInterface $info_parser, ModuleHandlerInterface $module_handler, StateInterface $state, $install_profile) {
    $this->root = $root;
    $this->type = $type;
    $this->cache = $cache;
    $this->infoParser = $info_parser;
    $this->moduleHandler = $module_handler;
    $this->state = $state;
    $this->installProfile = $install_profile;
  }

  /**
   * Returns the extension discovery.
   *
   * @return \Drupal\Core\Extension\ExtensionDiscovery
   *   The extension discovery.
   */
  protected function getExtensionDiscovery() {
    return new ExtensionDiscovery($this->root);
  }

  /**
   * Resets the stored extension list.
   *
   * We don't reset statically added filenames, as it is a static cache which
   * logically can't change. This is done for performance reasons of the
   * installer.
   *
   * @return $this
   */
  public function reset() {
    $this->extensions = NULL;
    $this->cache->delete($this->getListCacheId());
    $this->extensionInfo = NULL;
    $this->cache->delete($this->getInfoCacheId());
    $this->pathNames = NULL;

    try {
      $this->state->delete($this->getPathNamesCacheId());
    }
    catch (DatabaseExceptionWrapper) {
      // Ignore exceptions caused by a non existing {key_value} table in the
      // early installer.
    }

    // @todo In the long run it would be great to add the reset, but the early
    //   installer fails due to that. https://www.drupal.org/node/2719315 could
    //   help to resolve with that.
    return $this;
  }

  /**
   * Returns the extension list cache ID.
   *
   * @return string
   *   The list cache ID.
   */
  protected function getListCacheId() {
    return 'core.extension.list.' . $this->type;
  }

  /**
   * Returns the extension info cache ID.
   *
   * @return string
   *   The info cache ID.
   */
  protected function getInfoCacheId() {
    return "system.{$this->type}.info";
  }

  /**
   * Returns the extension filenames cache ID.
   *
   * @return string
   *   The filename cache ID.
   */
  protected function getPathNamesCacheId() {
    return "system.{$this->type}.files";
  }

  /**
   * Determines if an extension exists in the filesystem.
   *
   * @param string $extension_name
   *   The machine name of the extension.
   *
   * @return bool
   *   TRUE if the extension exists (regardless installed or not) and FALSE if
   *   not.
   */
  public function exists($extension_name) {
    $extensions = $this->getList();
    return isset($extensions[$extension_name]);
  }

  /**
   * Returns the human-readable name of the extension.
   *
   * @param string $extension_name
   *   The machine name of the extension.
   *
   * @return string
   *   The human-readable name of the extension.
   *
   * @throws \Drupal\Core\Extension\Exception\UnknownExtensionException
   *   If there is no extension with the supplied machine name.
   */
  public function getName($extension_name) {
    return $this->get($extension_name)->info['name'];
  }

  /**
   * Returns a single extension.
   *
   * @param string $extension_name
   *   The machine name of the extension.
   *
   * @return \Drupal\Core\Extension\Extension
   *   A processed extension object for the extension with the specified machine
   *   name.
   *
   * @throws \Drupal\Core\Extension\Exception\UnknownExtensionException
   *   If there is no extension with the supplied name.
   */
  public function get($extension_name) {
    $extensions = $this->getList();
    if (isset($extensions[$extension_name])) {
      return $extensions[$extension_name];
    }

    throw new UnknownExtensionException("The {$this->type} $extension_name does not exist.");
  }

  /**
   * Returns all available extensions.
   *
   * @return \Drupal\Core\Extension\Extension[]
   *   Processed extension objects, keyed by machine name.
   */
  public function getList() {
    if ($this->extensions !== NULL) {
      return $this->extensions;
    }
    if ($cache = $this->cache->get($this->getListCacheId())) {
      $this->extensions = $cache->data;
      return $this->extensions;
    }
    $extensions = $this->doList();
    $this->cache->set($this->getListCacheId(), $extensions);
    $this->extensions = $extensions;
    return $this->extensions;
  }

  /**
   * Scans the available extensions.
   *
   * Overriding this method gives other code the chance to add additional
   * extensions to this raw listing.
   *
   * @return \Drupal\Core\Extension\Extension[]
   *   Unprocessed extension objects, keyed by machine name.
   */
  protected function doScanExtensions() {
    return $this->getExtensionDiscovery()->scan($this->type);
  }

  /**
   * Builds the list of extensions.
   *
   * @return \Drupal\Core\Extension\Extension[]
   *   Processed extension objects, keyed by machine name.
   *
   * @throws \Drupal\Core\Extension\InfoParserException
   *   If one of the .info.yml files is incomplete, or causes a parsing error.
   */
  protected function doList() {
    // Find extensions.
    $extensions = $this->doScanExtensions();

    // Read info files for each extension.
    foreach ($extensions as $extension) {
      $extension->info = $this->createExtensionInfo($extension);

      // Invoke hook_system_info_alter() to give installed modules a chance to
      // modify the data in the .info.yml files if necessary.
      $this->moduleHandler->alter('system_info', $extension->info, $extension, $this->type);
    }

    return $extensions;
  }

  /**
   * Returns information about a specified extension.
   *
   * This function returns the contents of the .info.yml file for the specified
   * extension.
   *
   * @param string $extension_name
   *   The name of an extension whose information shall be returned.
   *
   * @return mixed[]
   *   An associative array of extension information.
   *
   * @throws \Drupal\Core\Extension\Exception\UnknownExtensionException
   *   If there is no extension with the supplied name.
   */
  public function getExtensionInfo($extension_name) {
    $all_info = $this->getAllInstalledInfo();
    if (isset($all_info[$extension_name])) {
      return $all_info[$extension_name];
    }
    throw new UnknownExtensionException("The {$this->type} $extension_name does not exist or is not installed.");
  }

  /**
   * Returns an array of info files information of available extensions.
   *
   * This function returns the processed contents (with added defaults) of the
   * .info.yml files.
   *
   * @return array[]
   *   An associative array of extension information arrays, keyed by extension
   *   name.
   */
  public function getAllAvailableInfo() {
    if ($this->extensionInfo === NULL) {
      $cache_id = $this->getInfoCacheId();
      if ($cache = $this->cache->get($cache_id)) {
        $info = $cache->data;
      }
      else {
        $info = $this->recalculateInfo();
        $this->cache->set($cache_id, $info);
      }
      $this->extensionInfo = $info;
    }

    return $this->extensionInfo;
  }

  /**
   * Returns a list of machine names of installed extensions.
   *
   * @return string[]
   *   The machine names of all installed extensions of this type.
   */
  abstract protected function getInstalledExtensionNames();

  /**
   * Returns an array of info files information of installed extensions.
   *
   * This function returns the processed contents (with added defaults) of the
   * .info.yml files.
   *
   * @return array[]
   *   An associative array of extension information arrays, keyed by extension
   *   name.
   */
  public function getAllInstalledInfo() {
    return array_intersect_key($this->getAllAvailableInfo(), array_flip($this->getInstalledExtensionNames()));
  }

  /**
   * Generates the information from .info.yml files for extensions of this type.
   *
   * @return array[]
   *   An array of arrays of .info.yml entries keyed by the machine name.
   */
  protected function recalculateInfo() {
    return array_map(function (Extension $extension) {
      return $extension->info;
    }, $this->getList());
  }

  /**
   * Returns a list of extension file paths keyed by machine name.
   *
   * @return string[]
   *   An associative array of extension file paths, keyed by the extension
   *   machine name.
   */
  public function getPathNames() {
    if ($this->pathNames === NULL) {
      $cache_id = $this->getPathNamesCacheId();
      $this->pathNames = $this->state->get($cache_id);

      if ($this->pathNames === NULL) {
        $this->pathNames = $this->recalculatePathNames();
        // Store filenames to allow static::getPathname() to retrieve them
        // without having to rebuild or scan the filesystem.
        $this->state->set($cache_id, $this->pathNames);
      }
    }
    return $this->pathNames;
  }

  /**
   * Generates a sorted list of .info.yml file locations for all extensions.
   *
   * @return string[]
   *   An array of .info.yml file locations keyed by the extension machine name.
   */
  protected function recalculatePathNames() {
    $extensions = $this->getList();
    ksort($extensions);

    return array_map(function (Extension $extension) {
      return $extension->getPathname();
    }, $extensions);
  }

  /**
   * Sets the pathname for an extension.
   *
   * This method is used in the Drupal bootstrapping phase, when the extension
   * system is not fully initialized, to manually set locations of modules and
   * profiles needed to complete bootstrapping.
   *
   * It is not recommended to call this method except in those rare cases.
   *
   * @param string $extension_name
   *   The machine name of the extension.
   * @param string $pathname
   *   The pathname of the extension which is to be set explicitly rather
   *   than by consulting the dynamic extension listing.
   *
   * @internal
   *
   * @see ::getPathname
   */
  public function setPathname($extension_name, $pathname) {
    $this->addedPathNames[$extension_name] = $pathname;
  }

  /**
   * Gets the info file path for an extension.
   *
   * The info path, whether provided, cached, or retrieved from the database, is
   * only returned if the file exists.
   *
   * This function plays a key role in allowing Drupal's extensions (modules,
   * themes, profiles, theme_engines, etc.) to be located in different places
   * depending on a site's configuration. For example, a module 'foo' may
   * legally be located in any of these four places:
   *
   * - core/modules/foo/foo.info.yml
   * - modules/foo/foo.info.yml
   * - sites/all/modules/foo/foo.info.yml
   * - sites/example.com/modules/foo/foo.info.yml
   *
   * while a theme 'bar' may be located in any of the following four places:
   *
   * - core/themes/bar/bar.info.yml
   * - themes/bar/bar.info.yml
   * - sites/all/themes/bar/bar.info.yml
   * - sites/example.com/themes/bar/bar.info.yml
   *
   * An installation profile maybe be located in any of the following places:
   *
   * - core/profiles/baz/baz.info.yml
   * - profiles/baz/baz.info.yml
   *
   * Calling ExtensionList::getPathname('foo') will give you one of the above,
   * depending on where the extension is located and what type it is.
   *
   * @param string $extension_name
   *   The machine name of the extension for which the pathname is requested.
   *
   * @return string
   *   The drupal-root relative filename and path of the requested extension's
   *   .info.yml file.
   *
   * @throws \Drupal\Core\Extension\Exception\UnknownExtensionException
   *   If there is no extension with the supplied machine name.
   */
  public function getPathname($extension_name) {
    if (isset($this->addedPathNames[$extension_name])) {
      return $this->addedPathNames[$extension_name];
    }
    elseif (isset($this->pathNames[$extension_name])) {
      return $this->pathNames[$extension_name];
    }
    elseif (($path_names = $this->getPathNames()) && isset($path_names[$extension_name])) {
      return $path_names[$extension_name];
    }
    throw new UnknownExtensionException("The {$this->type} $extension_name does not exist.");
  }

  /**
   * Gets the path to an extension of a specific type (module, theme, etc.).
   *
   * The path is the directory in which the .info file is located. This name is
   * coming from \SplFileInfo.
   *
   * @param string $extension_name
   *   The machine name of the extension for which the path is requested.
   *
   * @return string
   *   The Drupal-root-relative path to the specified extension.
   *
   * @throws \Drupal\Core\Extension\Exception\UnknownExtensionException
   *   If there is no extension with the supplied name.
   */
  public function getPath($extension_name) {
    return dirname($this->getPathname($extension_name));
  }

  /**
   * Creates the info value for an extension object.
   *
   * @param \Drupal\Core\Extension\Extension $extension
   *   The extension whose info is to be altered.
   *
   * @return array
   *   The extension info array.
   */
  protected function createExtensionInfo(Extension $extension) {
    $info = $this->infoParser->parse($extension->getPathname());

    // Add the info file modification time, so it becomes available for
    // contributed extensions to use for ordering extension lists.
    $info['mtime'] = $extension->getFileInfo()->getMTime();

    // Merge extension type-specific defaults, making sure to replace NULL
    // values.
    foreach ($this->defaults as $key => $default_value) {
      if (!isset($info[$key])) {
        $info[$key] = $default_value;
      }
    }

    return $info;
  }

  /**
   * Tests the compatibility of an extension.
   *
   * @param string $name
   *   The extension name to check.
   *
   * @return bool
   *   TRUE if the extension is incompatible and FALSE if not.
   *
   * @throws \Drupal\Core\Extension\Exception\UnknownExtensionException
   *   If there is no extension with the supplied name.
   */
  public function checkIncompatibility($name) {
    $extension = $this->get($name);
    return $extension->info['core_incompatible'] || (isset($extension->info['php']) && version_compare(phpversion(), $extension->info['php']) < 0);
  }

  /**
   * Array sorting callback; sorts extensions by their name.
   *
   * @param \Drupal\Core\Extension\Extension $a
   *   The first extension to compare.
   * @param \Drupal\Core\Extension\Extension $b
   *   The second extension to compare.
   *
   * @return int
   *   Less than 0 if $a is less than $b, more than 0 if $a is greater than $b,
   *   and 0 if they are equal.
   */
  public static function sortByName(Extension $a, Extension $b): int {
    return strcasecmp($a->info['name'], $b->info['name']);
  }

}
