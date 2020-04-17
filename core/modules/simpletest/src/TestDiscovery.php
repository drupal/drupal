<?php

namespace Drupal\simpletest;

@trigger_error(__NAMESPACE__ . '\\TestDiscovery is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use \Drupal\Core\Test\TestDiscovery instead. See https://www.drupal.org/node/2949692', E_USER_DEPRECATED);

use Doctrine\Common\Reflection\StaticReflectionParser;
use Drupal\Component\Annotation\Reflection\MockFileFinder;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Test\Exception\MissingGroupException;
use Drupal\Core\Test\TestDiscovery as CoreTestDiscovery;

/**
 * Discovers available tests.
 *
 * This class provides backwards compatibility for code which uses the legacy
 * \Drupal\simpletest\TestDiscovery.
 *
 * @deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use
 *   \Drupal\Core\Test\TestDiscovery instead.
 *
 * @see https://www.drupal.org/node/2949692
 */
class TestDiscovery extends CoreTestDiscovery {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a new test discovery.
   *
   * @param string $root
   *   The app root.
   * @param $class_loader
   *   The class loader. Normally Composer's ClassLoader, as included by the
   *   front controller, but may also be decorated; e.g.,
   *   \Symfony\Component\ClassLoader\ApcClassLoader.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct($root, $class_loader, ModuleHandlerInterface $module_handler) {
    parent::__construct($root, $class_loader);
    $this->moduleHandler = $module_handler;
  }

  /**
   * Discovers all available tests in all extensions.
   *
   * This method is a near-duplicate of
   * \Drupal\Core\Tests\TestDiscovery::getTestClasses(). It exists so that we
   * can provide a BC invocation of hook_simpletest_alter().
   *
   * @param string $extension
   *   (optional) The name of an extension to limit discovery to; e.g., 'node'.
   * @param string[] $types
   *   An array of included test types.
   *
   * @return array
   *   An array of tests keyed by the group name. If a test is annotated to
   *   belong to multiple groups, it will appear under all group keys it belongs
   *   to.
   * @code
   *     $groups['block'] => array(
   *       'Drupal\Tests\block\Functional\BlockTest' => array(
   *         'name' => 'Drupal\Tests\block\Functional\BlockTest',
   *         'description' => 'Tests block UI CRUD functionality.',
   *         'group' => 'block',
   *         'groups' => ['block', 'group2', 'group3'],
   *       ),
   *     );
   * @endcode
   */
  public function getTestClasses($extension = NULL, array $types = []) {
    if (!isset($extension) && empty($types)) {
      if (!empty($this->testClasses)) {
        return $this->testClasses;
      }
    }
    $list = [];

    $classmap = $this->findAllClassFiles($extension);

    // Prevent expensive class loader lookups for each reflected test class by
    // registering the complete classmap of test classes to the class loader.
    // This also ensures that test classes are loaded from the discovered
    // pathnames; a namespace/classname mismatch will throw an exception.
    $this->classLoader->addClassMap($classmap);

    foreach ($classmap as $classname => $pathname) {
      $finder = MockFileFinder::create($pathname);
      $parser = new StaticReflectionParser($classname, $finder, TRUE);
      try {
        $info = static::getTestInfo($classname, $parser->getDocComment());
      }
      catch (MissingGroupException $e) {
        // If the class name ends in Test and is not a migrate table dump.
        if (preg_match('/Test$/', $classname) && strpos($classname, 'migrate_drupal\Tests\Table') === FALSE) {
          throw $e;
        }
        // If the class is @group annotation just skip it. Most likely it is an
        // abstract class, trait or test fixture.
        continue;
      }
      // Skip this test class if it is a Simpletest-based test and requires
      // unavailable modules. TestDiscovery should not filter out module
      // requirements for PHPUnit-based test classes.
      // @todo Move this behavior to \Drupal\simpletest\TestBase so tests can be
      //       marked as skipped, instead.
      // @see https://www.drupal.org/node/1273478
      if ($info['type'] == 'Simpletest') {
        if (!empty($info['requires']['module'])) {
          if (array_diff($info['requires']['module'], $this->availableExtensions['module'])) {
            continue;
          }
        }
      }

      foreach ($info['groups'] as $group) {
        $list[$group][$classname] = $info;
      }
    }

    // Sort the groups and tests within the groups by name.
    uksort($list, 'strnatcasecmp');
    foreach ($list as &$tests) {
      uksort($tests, 'strnatcasecmp');
    }

    // Allow modules extending core tests to disable originals.
    $this->moduleHandler->alterDeprecated('Convert your test to a PHPUnit-based one and implement test listeners. See: https://www.drupal.org/node/2939892', 'simpletest', $list);

    if (!isset($extension) && empty($types)) {
      $this->testClasses = $list;
    }

    if ($types) {
      $list = NestedArray::filter($list, function ($element) use ($types) {
        return !(is_array($element) && isset($element['type']) && !in_array($element['type'], $types));
      });
    }

    return $list;
  }

}
