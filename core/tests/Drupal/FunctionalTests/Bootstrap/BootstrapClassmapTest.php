<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\Bootstrap;

use Composer\Autoload\ClassLoader;
use Drupal\Composer\Plugin\Scaffold\Plugin;
use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests that the classmap matches the files loaded before APCu is available.
 *
 * Until DrupalKernel::boot() sets the APCu prefix on the class loader, each
 * class that is not in Composer's classmap costs one or more filesystem
 * lookups. Drupal adds these files to the classmap in two places:
 * - core/composer.json lists files from Drupal core.
 * - \Drupal\Composer\Plugin\Scaffold\Plugin::BOOTSTRAP_CLASSMAP lists files
 *   from vendor packages.
 *
 * @see \Drupal\Core\DrupalKernel::boot()
 */
#[Group('Bootstrap')]
#[RunTestsInSeparateProcesses]
class BootstrapClassmapTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Classes that are loaded during bootstrap only in some environments.
   *
   * @var string[]
   */
  protected const IGNORED_CLASSES = [
    // Only loaded when assertions are enabled.
    'Drupal\Component\Assertion\Inspector',
    // Only loaded when the request comes from a test runner.
    'Drupal\Component\Utility\Crypt',
    'Drupal\Core\Test\TestDatabase',
  ];

  /**
   * Tests that the classmap matches the files loaded during bootstrap.
   */
  public function testBootstrapClassmap(): void {
    $vendor_dir = dirname((new \ReflectionClass(ClassLoader::class))->getFileName(), 2);

    // Production sites should set trusted host patterns, which loads an extra
    // class during bootstrap.
    $settings['settings']['trusted_host_patterns'] = (object) [
      'value' => ['^' . preg_quote(parse_url($this->baseUrl, PHP_URL_HOST)) . '$'],
      'required' => TRUE,
    ];
    $this->writeSettings($settings);

    // Each test run uses its own front controller, so parallel runs do not
    // delete each other's file.
    $front_controller = 'bootstrap_classmap_' . $this->databasePrefix . '.php';
    copy($this->root . '/core/tests/fixtures/bootstrap_classmap.php.template', $this->root . '/' . $front_controller);
    try {
      $this->drupalGet($this->baseUrl . '/' . $front_controller);
    }
    finally {
      unlink($this->root . '/' . $front_controller);
    }
    $this->assertSession()->statusCodeEquals(200);
    $loaded_files = json_decode($this->getSession()->getPage()->getContent(), TRUE, flags: JSON_THROW_ON_ERROR);

    // Composer includes its own files directly, and module classes cannot be
    // added to Composer's classmap. Development packages are not installed on
    // production sites.
    $ignored_dirs = [
      realpath($vendor_dir . '/composer'),
      realpath($this->root . '/core/modules'),
    ];
    $installed = require $vendor_dir . '/composer/installed.php';
    foreach ($installed['versions'] as $package) {
      if ($package['dev_requirement'] && isset($package['install_path'])) {
        $ignored_dirs[] = realpath($package['install_path']);
      }
    }

    $loaded = [];
    foreach ($loaded_files as $class => $file) {
      if (in_array($class, static::IGNORED_CLASSES, TRUE)) {
        continue;
      }
      foreach (array_filter($ignored_dirs) as $ignored_dir) {
        if (str_starts_with($file, $ignored_dir . '/')) {
          continue 2;
        }
      }
      $loaded[] = $this->relativePath($file, $vendor_dir);
    }

    $classmap = [];
    $core_composer = json_decode(file_get_contents($this->root . '/core/composer.json'), TRUE, flags: JSON_THROW_ON_ERROR);
    foreach ($core_composer['autoload']['classmap'] as $file) {
      $classmap[] = $this->relativePath(realpath($this->root . '/core/' . $file), $vendor_dir);
    }
    foreach (Plugin::BOOTSTRAP_CLASSMAP as $package_name => $files) {
      foreach ($files as $file) {
        $classmap[] = $this->relativePath(realpath($vendor_dir . '/' . $package_name . '/' . $file), $vendor_dir);
      }
    }
    // The scaffold plugin generates this file and always adds it.
    $classmap[] = 'vendor/drupal/DrupalInstalled.php';

    // These files are loaded during bootstrap by some database drivers but not
    // others. They must still be in the classmap whenever they are loaded.
    $database_dependent_files = [
      'core/lib/Drupal/Component/Utility/Unicode.php',
      'core/lib/Drupal/Core/Database/Statement/PdoResult.php',
      'core/lib/Drupal/Core/Database/Statement/PdoTrait.php',
      'core/lib/Drupal/Core/Database/Statement/PrefetchedResult.php',
      'core/lib/Drupal/Core/Database/StatementWrapperIterator.php',
      'core/lib/Drupal/Core/Database/Transaction/ClientConnectionTransactionState.php',
      'core/lib/Drupal/Core/Database/Transaction/TransactionManagerBase.php',
      'core/lib/Drupal/Core/Database/Transaction/TransactionManagerInterface.php',
    ];
    $this->assertSame([], array_values(array_diff($loaded, $classmap)), 'Files loaded before APCu is available are missing from the classmap.');
    $this->assertSame([], array_values(array_diff($classmap, $loaded, $database_dependent_files)), 'Classmap files are not loaded before APCu is available.');
  }

  /**
   * Converts an absolute file path to a path for comparison.
   *
   * @param string|false $file
   *   The absolute file path, or FALSE if the file does not exist.
   * @param string $vendor_dir
   *   The Composer vendor directory.
   *
   * @return string
   *   The path relative to the vendor directory with a 'vendor/' prefix, or
   *   relative to the Drupal root.
   */
  protected function relativePath(string|false $file, string $vendor_dir): string {
    $this->assertIsString($file, 'Classmap file exists.');
    $vendor_dir = realpath($vendor_dir) . '/';
    if (str_starts_with($file, $vendor_dir)) {
      return 'vendor/' . substr($file, strlen($vendor_dir));
    }
    return substr($file, strlen(realpath($this->root)) + 1);
  }

}
