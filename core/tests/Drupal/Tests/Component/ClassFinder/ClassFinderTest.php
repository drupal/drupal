<?php

declare(strict_types=1);

namespace Drupal\Tests\Component\ClassFinder;

use Composer\Autoload\ClassLoader;
use Drupal\Component\ClassFinder\ClassFinder;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Drupal\Component\ClassFinder\ClassFinder
 * @group ClassFinder
 */
class ClassFinderTest extends TestCase {

  /**
   * @covers ::findFile
   */
  public function testFindFile(): void {
    $finder = new ClassFinder();

    // The full path is returned therefore only tests with
    // assertStringEndsWith() so the test is portable.
    $expected_path = str_replace('/', DIRECTORY_SEPARATOR, 'core/tests/Drupal/Tests/Component/ClassFinder/ClassFinderTest.php');
    $this->assertStringEndsWith($expected_path, $finder->findFile(ClassFinderTest::class));
    $class = 'Not\\A\\Class';
    $this->assertNull($finder->findFile($class));

    // Register an autoloader that can find this class.
    $loader = new ClassLoader();
    $loader->addClassMap([$class => __FILE__]);
    $loader->register();
    $this->assertEquals(__FILE__, $finder->findFile($class));
    // This shouldn't prevent us from finding the original file.
    $this->assertStringEndsWith($expected_path, $finder->findFile(ClassFinderTest::class));

    // Clean up the additional autoloader after the test.
    $loader->unregister();
  }

}
