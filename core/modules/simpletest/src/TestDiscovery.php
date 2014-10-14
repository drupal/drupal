<?php

/**
 * @file
 * Contains \Drupal\simpletest\TestDiscovery.
 */

namespace Drupal\simpletest;

use Composer\Autoload\ClassLoader;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ExtensionDiscovery;
use PHPUnit_Util_Test;

/**
 * Discovers available tests.
 */
class TestDiscovery {

  /**
   * The class loader.
   *
   * @var \Composer\Autoload\ClassLoader
   */
  protected $classLoader;

  /**
   * Backend for caching discovery results.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBackend;

  /**
   * Cached map of all test namespaces to respective directories.
   *
   * @var array
   */
  protected $testNamespaces;

  /**
   * Cached list of all available extension names, keyed by extension type.
   *
   * @var array
   */
  protected $availableExtensions;

  /**
   * Constructs a new test discovery.
   *
   * @param \Composer\Autoload\ClassLoader $class_loader
   *   The class loader.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   (optional) Backend for caching discovery results.
   */
  public function __construct(ClassLoader $class_loader, CacheBackendInterface $cache_backend = NULL) {
    $this->classLoader = $class_loader;
    $this->cacheBackend = $cache_backend;
  }

  /**
   * Registers test namespaces of all available extensions.
   *
   * @return array
   *   An associative array whose keys are PSR-4 namespace prefixes and whose
   *   values are directory names.
   */
  public function registerTestNamespaces() {
    if (isset($this->testNamespaces)) {
      return $this->testNamespaces;
    }
    $this->testNamespaces = array();

    $existing = $this->classLoader->getPrefixesPsr4();

    // Add PHPUnit test namespace of Drupal core.
    $this->testNamespaces['Drupal\\Tests\\'] = [DRUPAL_ROOT . '/core/tests/Drupal/Tests'];

    $this->availableExtensions = array();
    foreach ($this->getExtensions() as $name => $extension) {
      $this->availableExtensions[$extension->getType()][$name] = $name;

      $base_path = DRUPAL_ROOT . '/' . $extension->getPath();

      // Add namespace of disabled/uninstalled extensions.
      if (!isset($existing["Drupal\\$name\\"])) {
        $this->classLoader->addPsr4("Drupal\\$name\\", "$base_path/src");
      }
      // Add Simpletest test namespace.
      $this->testNamespaces["Drupal\\$name\\Tests\\"][] = "$base_path/src/Tests";

      // Add PHPUnit test namespace.
      $this->testNamespaces["Drupal\\Tests\\$name\\"][] = "$base_path/tests/src";
    }

    foreach ($this->testNamespaces as $prefix => $paths) {
      $this->classLoader->addPsr4($prefix, $paths);
    }

    return $this->testNamespaces;
  }

  /**
   * Discovers all available tests in all extensions.
   *
   * @param string $extension
   *   (optional) The name of an extension to limit discovery to; e.g., 'node'.
   *
   * @return array
   *   An array of tests keyed by the first @group specified in each test's
   *   PHPDoc comment block, and then keyed by class names. For example:
   *   @code
   *     $groups['block'] => array(
   *       'Drupal\block\Tests\BlockTest' => array(
   *         'name' => 'Drupal\block\Tests\BlockTest',
   *         'description' => 'Tests block UI CRUD functionality.',
   *         'group' => 'block',
   *       ),
   *     );
   *   @endcode
   *
   * @throws \ReflectionException
   *   If a discovered test class does not match the expected class name.
   *
   * @todo Remove singular grouping; retain list of groups in 'group' key.
   * @see https://www.drupal.org/node/2296615
   * @todo Add base class groups 'Kernel' + 'Web', complementing 'PHPUnit'.
   */
  public function getTestClasses($extension = NULL) {
    if (!isset($extension)) {
      if ($this->cacheBackend && $cache = $this->cacheBackend->get('simpletest:discovery:classes')) {
        return $cache->data;
      }
    }
    $list = array();

    $classmap = $this->findAllClassFiles($extension);

    // Prevent expensive class loader lookups for each reflected test class by
    // registering the complete classmap of test classes to the class loader.
    // This also ensures that test classes are loaded from the discovered
    // pathnames; a namespace/classname mismatch will throw an exception.
    $this->classLoader->addClassMap($classmap);

    foreach ($classmap as $classname => $pathname) {
      try {
        $class = new \ReflectionClass($classname);
      }
      catch (\ReflectionException $e) {
        // Re-throw with expected pathname.
        $message = $e->getMessage() . " in expected $pathname";
        throw new \ReflectionException($message, $e->getCode(), $e);
      }
      // Skip interfaces, abstract classes, and traits.
      if (!$class->isInstantiable()) {
        continue;
      }
      // Skip non-test classes.
      if (!$class->isSubclassOf('Drupal\simpletest\TestBase') && !$class->isSubclassOf('PHPUnit_Framework_TestCase')) {
        continue;
      }
      $info = static::getTestInfo($class);

      // Skip this test class if it requires unavailable modules.
      // @todo PHPUnit skips tests with unmet requirements when executing a test
      //   (instead of excluding them upfront). Refactor test runner to follow
      //   that approach.
      // @see https://www.drupal.org/node/1273478
      if (!empty($info['requires']['module'])) {
        if (array_diff($info['requires']['module'], $this->availableExtensions['module'])) {
          continue;
        }
      }

      $list[$info['group']][$classname] = $info;
    }

    // Sort the groups and tests within the groups by name.
    uksort($list, 'strnatcasecmp');
    foreach ($list as &$tests) {
      uksort($tests, 'strnatcasecmp');
    }

    // Allow modules extending core tests to disable originals.
    \Drupal::moduleHandler()->alter('simpletest', $list);

    if (!isset($extension)) {
      if ($this->cacheBackend) {
        $this->cacheBackend->set('simpletest:discovery:classes', $list);
      }
    }
    return $list;
  }

  /**
   * Discovers all class files in all available extensions.
   *
   * @param string $extension
   *   (optional) The name of an extension to limit discovery to; e.g., 'node'.
   *
   * @return array
   *   A classmap containing all discovered class files; i.e., a map of
   *   fully-qualified classnames to pathnames.
   */
  public function findAllClassFiles($extension = NULL) {
    $classmap = array();
    $namespaces = $this->registerTestNamespaces();
    if (isset($extension)) {
      // Include tests in the \Drupal\Tests\{$extension} namespace.
      $pattern = "/Drupal\\\(Tests\\\)?$extension\\\/";
      $namespaces = array_intersect_key($namespaces, array_flip(preg_grep($pattern, array_keys($namespaces))));
    }
    foreach ($namespaces as $namespace => $paths) {
      foreach ($paths as $path) {
        if (!is_dir($path)) {
          continue;
        }
        $classmap += static::scanDirectory($namespace, $path);
      }
    }
    return $classmap;
  }

  /**
   * Scans a given directory for class files.
   *
   * @param string $namespace_prefix
   *   The namespace prefix to use for discovered classes. Must contain a
   *   trailing namespace separator (backslash).
   *   For example: 'Drupal\\node\\Tests\\'
   * @param string $path
   *   The directory path to scan.
   *   For example: '/path/to/drupal/core/modules/node/tests/src'
   *
   * @return array
   *   An associative array whose keys are fully-qualified class names and whose
   *   values are corresponding filesystem pathnames.
   *
   * @throws \InvalidArgumentException
   *   If $namespace_prefix does not end in a namespace separator (backslash).
   *
   * @todo Limit to '*Test.php' files (~10% less files to reflect/introspect).
   * @see https://www.drupal.org/node/2296635
   */
  public static function scanDirectory($namespace_prefix, $path) {
    if (substr($namespace_prefix, -1) !== '\\') {
      throw new \InvalidArgumentException("Namespace prefix for $path must contain a trailing namespace separator.");
    }
    $flags = \FilesystemIterator::UNIX_PATHS;
    $flags |= \FilesystemIterator::SKIP_DOTS;
    $flags |= \FilesystemIterator::FOLLOW_SYMLINKS;
    $flags |= \FilesystemIterator::CURRENT_AS_SELF;

    $iterator = new \RecursiveDirectoryIterator($path, $flags);
    $filter = new \RecursiveCallbackFilterIterator($iterator, function ($current, $key, $iterator) {
      if ($iterator->hasChildren()) {
        return TRUE;
      }
      return $current->isFile() && $current->getExtension() === 'php';
    });
    $files = new \RecursiveIteratorIterator($filter);
    $classes = array();
    foreach ($files as $fileinfo) {
      $class = $namespace_prefix;
      if ('' !== $subpath = $fileinfo->getSubPath()) {
        $class .= strtr($subpath, '/', '\\') . '\\';
      }
      $class .= $fileinfo->getBasename('.php');
      $classes[$class] = $fileinfo->getPathname();
    }
    return $classes;
  }

  /**
   * Retrieves information about a test class for UI purposes.
   *
   * @param \ReflectionClass $class
   *   The reflected test class.
   *
   * @return array
   *   An associative array containing:
   *   - name: The test class name.
   *   - description: The test (PHPDoc) summary.
   *   - group: The test's first @group (parsed from PHPDoc annotations).
   *   - requires: An associative array containing test requirements parsed from
   *     PHPDoc annotations:
   *     - module: List of Drupal module extension names the test depends on.
   *
   * @throws \LogicException
   *   If the class does not have a PHPDoc summary line or @coversDefaultClass
   *   annotation.
   * @throws \LogicException
   *   If the class does not have a @group annotation.
   */
  public static function getTestInfo(\ReflectionClass $class) {
    $classname = $class->getName();
    $info = array(
      'name' => $classname,
    );

    // Automatically convert @coversDefaultClass into summary.
    $annotations = static::parseTestClassAnnotations($class);
    if (isset($annotations['coversDefaultClass'][0])) {
      $info['description'] = 'Tests ' . $annotations['coversDefaultClass'][0] . '.';
    }
    elseif ($summary = static::parseTestClassSummary($class)) {
      $info['description'] = $summary;
    }
    else {
      throw new \LogicException(sprintf('Missing PHPDoc summary line on %s in %s.', $classname, $class->getFileName()));
    }

    // Reduce to @group and @requires.
    $info += array_intersect_key($annotations, array('group' => 1, 'requires' => 1));

    // @todo Remove legacy getInfo() methods.
    if (method_exists($classname, 'getInfo')) {
      $legacy_info = $classname::getInfo();

      // Derive the primary @group from the namespace to ensure that legacy
      // tests are not located in different groups than converted tests.
      $classparts = explode('\\', $classname);
      if ($classparts[1] === 'Tests') {
        if ($classparts[2] === 'Component' || $classparts[2] === 'Core') {
          // Drupal\Tests\Component\{group}\...
          $info['group'][] = $classparts[3];
        }
        else {
          // Drupal\Tests\{group}\...
          $info['group'][] = $classparts[2];
        }
      }
      elseif ($classparts[1] === 'system' && $classparts[3] !== 'System') {
        // Drupal\system\Tests\{group}\...
        $info['group'][] = $classparts[3];
      }
      else {
        // Drupal\{group}\Tests\...
        $info['group'][] = $classparts[1];
      }

      if (isset($legacy_info['dependencies'])) {
        $info += array('requires' => array());
        $info['requires'] += array('module' => array());
        $info['requires']['module'] = array_merge($info['requires']['module'], $legacy_info['dependencies']);
      }
    }

    // Process @group information.
    // @todo Support multiple @groups + change UI to expose a group select
    //   dropdown to filter tests by group instead of collapsible table rows.
    // @see https://www.drupal.org/node/2296615
    // @todo Replace single enforced PHPUnit group with base class groups.
    if ($class->isSubclassOf('PHPUnit_Framework_TestCase')) {
      $info['group'] = 'PHPUnit';
    }
    else {
      if (empty($info['group'])) {
        throw new \LogicException("Missing @group for $classname.");
      }
      $info['group'] = reset($info['group']);
    }

    return $info;
  }

  /**
   * Parses the phpDoc summary line of a test class.
   *
   * @param \ReflectionClass $class
   *   The reflected test class.
   *
   * @return string
   *   The parsed phpDoc summary line.
   */
  public static function parseTestClassSummary(\ReflectionClass $class) {
    $phpDoc = $class->getDocComment();
    // Normalize line endings.
    $phpDoc = preg_replace('/\r\n|\r/', '\n', $phpDoc);
    // Strip leading and trailing doc block lines.
    //$phpDoc = trim($phpDoc, "* /\n");
    $phpDoc = substr($phpDoc, 4, -4);

    // Extract actual phpDoc content.
    $phpDoc = explode("\n", $phpDoc);
    array_walk($phpDoc, function (&$value) {
      $value = trim($value, "* /\n");
    });

    // Extract summary; allowed to it wrap and continue on next line.
    list($summary) = explode("\n\n", implode("\n", $phpDoc));
    if ($summary === '') {
      throw new \LogicException(sprintf('Missing phpDoc on %s.', $class->getName()));
    }
    return $summary;
  }

  /**
   * Parses annotations in the phpDoc of a test class.
   *
   * @param \ReflectionClass $class
   *   The reflected test class.
   *
   * @return array
   *   An associative array that contains all annotations on the test class;
   *   typically including:
   *   - group: A list of @group values.
   *   - requires: An associative array of @requires values; e.g.:
   *     - module: A list of Drupal module dependencies that are required to
   *       exist.
   *
   * @see PHPUnit_Util_Test::parseTestMethodAnnotations()
   * @see http://phpunit.de/manual/current/en/incomplete-and-skipped-tests.html#incomplete-and-skipped-tests.skipping-tests-using-requires
   */
  public static function parseTestClassAnnotations(\ReflectionClass $class) {
    $annotations = PHPUnit_Util_Test::parseTestMethodAnnotations($class->getName())['class'];

    // @todo Enhance PHPUnit upstream to allow for custom @requires identifiers.
    // @see PHPUnit_Util_Test::getRequirements()
    // @todo Add support for 'PHP', 'OS', 'function', 'extension'.
    // @see https://www.drupal.org/node/1273478
    if (isset($annotations['requires'])) {
      foreach ($annotations['requires'] as $i => $value) {
        list($type, $value) = explode(' ', $value, 2);
        if ($type === 'module') {
          $annotations['requires']['module'][$value] = $value;
          unset($annotations['requires'][$i]);
        }
      }
    }
    return $annotations;
  }

  /**
   * Returns all available extensions.
   *
   * @return \Drupal\Core\Extension\Extension[]
   *   An array of Extension objects, keyed by extension name.
   */
  protected function getExtensions() {
    $listing = new ExtensionDiscovery();
    // Ensure that tests in all profiles are discovered.
    $listing->setProfileDirectories(array());
    $extensions = $listing->scan('module', TRUE);
    $extensions += $listing->scan('profile', TRUE);
    $extensions += $listing->scan('theme', TRUE);
    return $extensions;
  }

}
