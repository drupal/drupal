<?php

declare(strict_types=1);

namespace Drupal\Tests\TestTools\ErrorHandler;

use Drupal\TestTools\ErrorHandler\DrupalDebugClassLoader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

/**
 * Tests the DrupalDebugClassLoader.
 */
#[CoversClass(DrupalDebugClassLoader::class)]
#[Group('TestTools')]
class DrupalDebugClassLoaderTest extends TestCase {

  /**
   * A DrupalDebugClassLoader instance for testing.
   */
  private DrupalDebugClassLoader $loader;

  /**
   * {@inheritdoc}
   */
  public static function setUpBeforeClass(): void {
    // Load fixture classes.
    require_once __DIR__ . '/../../../../fixtures/TestTools/drupal_debug_classloader_test_classes.php';
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->loader = new DrupalDebugClassLoader(function (): void {});
  }

  /**
   * Tests that cross-module return type deprecations are generated.
   */
  public function testCrossModuleReturnTypeDeprecation(): void {
    $deprecations = $this->getReturnTypeDeprecations('Drupal\drupal_debug_test_other\ChildWithoutReturnType');
    $this->assertSame(['Method "Drupal\drupal_debug_test_core\ParentWithReturn::testMethod()" might add "string" as a native return type declaration in the future. Do the same in child class "Drupal\drupal_debug_test_other\ChildWithoutReturnType" now to avoid errors or add an explicit @return annotation to suppress this message.'], $deprecations);
  }

  /**
   * Tests scenarios that should NOT trigger cross-module deprecations.
   */
  #[TestWith(['Drupal\drupal_debug_test_core\SameModuleChild'])]
  #[TestWith(['Drupal\drupal_debug_test_other\ChildWithNativeReturnType'])]
  #[TestWith(['Drupal\drupal_debug_test_other\ChildWithReturnAnnotation'])]
  #[TestWith(['Drupal\drupal_debug_test_other\ChildWithDeprecatedMethod'])]
  #[TestWith(['Drupal\drupal_debug_test_other\ChildWithoutOverride'])]
  public function testNoDeprecation(string $class): void {
    $this->assertEmpty($this->getReturnTypeDeprecations($class));
  }

  /**
   * Returns only the return-type deprecations for a given class.
   */
  private function getReturnTypeDeprecations(string $class): array {
    $deprecations = $this->loader->checkAnnotations(new \ReflectionClass($class), $class);
    return array_values(array_filter($deprecations, fn($d): bool => str_contains($d, 'might add')));
  }

}
