<?php

namespace Drupal\Tests\Core\Asset;

use Drupal\Core\Asset\LibraryDependencyResolver;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Asset\LibraryDependencyResolver
 * @group Asset
 */
class LibraryDependencyResolverTest extends UnitTestCase {

  /**
   * The tested library dependency resolver.
   *
   * @var \Drupal\Core\Asset\LibraryDependencyResolver
   */
  protected $libraryDependencyResolver;

  /**
   * The mocked library discovery service.
   *
   * @var \Drupal\Core\Asset\LibraryDiscoveryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $libraryDiscovery;

  /**
   * The mocked module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $moduleHandler;

  /**
   * Test library data.
   *
   * @var array
   */
  protected $libraryData = [
    'no_deps_a' => ['js' => [], 'css' => []],
    'no_deps_b' => ['js' => [], 'css' => []],
    'no_deps_c' => ['js' => [], 'css' => []],
    'deps_a' => ['js' => [], 'css' => [], 'dependencies' => ['test/no_deps_a']],
    'deps_b' => ['js' => [], 'css' => [], 'dependencies' => ['test/no_deps_a', 'test/no_deps_b']],
    'deps_c' => ['js' => [], 'css' => [], 'dependencies' => ['test/no_deps_b', 'test/no_deps_a']],
    'nested_deps_a' => ['js' => [], 'css' => [], 'dependencies' => ['test/deps_a']],
    'nested_deps_b' => ['js' => [], 'css' => [], 'dependencies' => ['test/nested_deps_a']],
    'nested_deps_c' => ['js' => [], 'css' => [], 'dependencies' => ['test/nested_deps_b']],
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->libraryDiscovery = $this->getMockBuilder('Drupal\Core\Asset\LibraryDiscovery')
      ->disableOriginalConstructor()
      ->onlyMethods(['getLibrariesByExtension'])
      ->getMock();
    $this->libraryDiscovery->expects($this->any())
      ->method('getLibrariesByExtension')
      ->with('test')
      ->willReturn($this->libraryData);
    $this->libraryDependencyResolver = new LibraryDependencyResolver($this->libraryDiscovery);
  }

  /**
   * Provides test data for ::testGetLibrariesWithDependencies().
   */
  public function providerTestGetLibrariesWithDependencies() {
    return [
      // Empty list of libraries.
      [[], []],
      // Without dependencies.
      [['test/no_deps_a'], ['test/no_deps_a']],
      [['test/no_deps_a', 'test/no_deps_b'], ['test/no_deps_a', 'test/no_deps_b']],
      [['test/no_deps_b', 'test/no_deps_a'], ['test/no_deps_b', 'test/no_deps_a']],
      // Single-level (direct) dependencies.
      [['test/deps_a'], ['test/no_deps_a', 'test/deps_a']],
      [['test/deps_b'], ['test/no_deps_a', 'test/no_deps_b', 'test/deps_b']],
      [['test/deps_c'], ['test/no_deps_b', 'test/no_deps_a', 'test/deps_c']],
      [['test/deps_a', 'test/deps_b'], ['test/no_deps_a', 'test/deps_a', 'test/no_deps_b', 'test/deps_b']],
      [['test/deps_a', 'test/deps_c'], ['test/no_deps_a', 'test/deps_a', 'test/no_deps_b', 'test/deps_c']],
      [['test/deps_a', 'test/deps_b', 'test/deps_c'], ['test/no_deps_a', 'test/deps_a', 'test/no_deps_b', 'test/deps_b', 'test/deps_c']],
      [['test/deps_b', 'test/deps_a'], ['test/no_deps_a', 'test/no_deps_b', 'test/deps_b', 'test/deps_a']],
      [['test/deps_b', 'test/deps_c'], ['test/no_deps_a', 'test/no_deps_b', 'test/deps_b', 'test/deps_c']],
      [['test/deps_c', 'test/deps_b'], ['test/no_deps_b', 'test/no_deps_a', 'test/deps_c', 'test/deps_b']],
      // Multi-level (indirect) dependencies.
      [['test/nested_deps_a'], ['test/no_deps_a', 'test/deps_a', 'test/nested_deps_a']],
      [['test/nested_deps_b'], ['test/no_deps_a', 'test/deps_a', 'test/nested_deps_a', 'test/nested_deps_b']],
      [['test/nested_deps_c'], ['test/no_deps_a', 'test/deps_a', 'test/nested_deps_a', 'test/nested_deps_b', 'test/nested_deps_c']],
      [['test/nested_deps_a', 'test/nested_deps_b'], ['test/no_deps_a', 'test/deps_a', 'test/nested_deps_a', 'test/nested_deps_b']],
      [['test/nested_deps_b', 'test/nested_deps_a'], ['test/no_deps_a', 'test/deps_a', 'test/nested_deps_a', 'test/nested_deps_b']],
      [['test/nested_deps_a', 'test/nested_deps_c'], ['test/no_deps_a', 'test/deps_a', 'test/nested_deps_a', 'test/nested_deps_b', 'test/nested_deps_c']],
      [['test/nested_deps_b', 'test/nested_deps_c'], ['test/no_deps_a', 'test/deps_a', 'test/nested_deps_a', 'test/nested_deps_b', 'test/nested_deps_c']],
      [['test/nested_deps_c', 'test/nested_deps_a'], ['test/no_deps_a', 'test/deps_a', 'test/nested_deps_a', 'test/nested_deps_b', 'test/nested_deps_c']],
      [['test/nested_deps_a', 'test/nested_deps_b', 'test/nested_deps_c'], ['test/no_deps_a', 'test/deps_a', 'test/nested_deps_a', 'test/nested_deps_b', 'test/nested_deps_c']],
      [['test/nested_deps_a', 'test/nested_deps_c', 'test/nested_deps_b'], ['test/no_deps_a', 'test/deps_a', 'test/nested_deps_a', 'test/nested_deps_b', 'test/nested_deps_c']],
      [['test/nested_deps_b', 'test/nested_deps_a', 'test/nested_deps_c'], ['test/no_deps_a', 'test/deps_a', 'test/nested_deps_a', 'test/nested_deps_b', 'test/nested_deps_c']],
      [['test/nested_deps_b', 'test/nested_deps_c', 'test/nested_deps_a'], ['test/no_deps_a', 'test/deps_a', 'test/nested_deps_a', 'test/nested_deps_b', 'test/nested_deps_c']],
      [['test/nested_deps_c', 'test/nested_deps_a', 'test/nested_deps_b'], ['test/no_deps_a', 'test/deps_a', 'test/nested_deps_a', 'test/nested_deps_b', 'test/nested_deps_c']],
      [['test/nested_deps_c', 'test/nested_deps_b', 'test/nested_deps_a'], ['test/no_deps_a', 'test/deps_a', 'test/nested_deps_a', 'test/nested_deps_b', 'test/nested_deps_c']],
      // Complex dependencies, combining the above, with many intersections.
      [['test/deps_c', 'test/nested_deps_b'], ['test/no_deps_b', 'test/no_deps_a', 'test/deps_c', 'test/deps_a', 'test/nested_deps_a', 'test/nested_deps_b']],
      [['test/no_deps_a', 'test/deps_c', 'test/nested_deps_b'], ['test/no_deps_a', 'test/no_deps_b', 'test/deps_c', 'test/deps_a', 'test/nested_deps_a', 'test/nested_deps_b']],
      [['test/nested_deps_b', 'test/deps_c', 'test/no_deps_c'], ['test/no_deps_a', 'test/deps_a', 'test/nested_deps_a', 'test/nested_deps_b', 'test/no_deps_b', 'test/deps_c', 'test/no_deps_c']],
    ];
  }

  /**
   * @covers ::getLibrariesWithDependencies
   *
   * @dataProvider providerTestGetLibrariesWithDependencies
   */
  public function testGetLibrariesWithDependencies(array $libraries, array $expected) {
    $this->assertEquals($expected, $this->libraryDependencyResolver->getLibrariesWithDependencies($libraries));
  }

  /**
   * Provides test data for ::testGetMinimalRepresentativeSubset().
   */
  public function providerTestGetMinimalRepresentativeSubset() {
    return [
      // Empty list of libraries.
      [[], []],
      // Without dependencies.
      [['test/no_deps_a'], ['test/no_deps_a']],
      [['test/no_deps_a', 'test/no_deps_b'], ['test/no_deps_a', 'test/no_deps_b']],
      [['test/no_deps_b', 'test/no_deps_a'], ['test/no_deps_b', 'test/no_deps_a']],
      // Single-level (direct) dependencies.
      [['test/deps_a'], ['test/deps_a']],
      [['test/deps_b'], ['test/deps_b']],
      [['test/deps_c'], ['test/deps_c']],
      [['test/deps_a', 'test/deps_b'], ['test/deps_a', 'test/deps_b']],
      [['test/deps_a', 'test/deps_c'], ['test/deps_a', 'test/deps_c']],
      [['test/deps_a', 'test/deps_b', 'test/deps_c'], ['test/deps_a', 'test/deps_b', 'test/deps_c']],
      [['test/deps_b', 'test/deps_a'], ['test/deps_b', 'test/deps_a']],
      [['test/deps_b', 'test/deps_c'], ['test/deps_b', 'test/deps_c']],
      [['test/deps_c', 'test/deps_b'], ['test/deps_c', 'test/deps_b']],
      // Multi-level (indirect) dependencies.
      [['test/nested_deps_a'], ['test/nested_deps_a']],
      [['test/nested_deps_b'], ['test/nested_deps_b']],
      [['test/nested_deps_c'], ['test/nested_deps_c']],
      [['test/nested_deps_a', 'test/nested_deps_b'], ['test/nested_deps_b']],
      [['test/nested_deps_b', 'test/nested_deps_a'], ['test/nested_deps_b']],
      [['test/nested_deps_a', 'test/nested_deps_c'], ['test/nested_deps_c']],
      [['test/nested_deps_b', 'test/nested_deps_c'], ['test/nested_deps_c']],
      [['test/nested_deps_c', 'test/nested_deps_a'], ['test/nested_deps_c']],
      [['test/nested_deps_a', 'test/nested_deps_b', 'test/nested_deps_c'], ['test/nested_deps_c']],
      [['test/nested_deps_a', 'test/nested_deps_c', 'test/nested_deps_b'], ['test/nested_deps_c']],
      [['test/nested_deps_b', 'test/nested_deps_a', 'test/nested_deps_c'], ['test/nested_deps_c']],
      [['test/nested_deps_b', 'test/nested_deps_c', 'test/nested_deps_a'], ['test/nested_deps_c']],
      [['test/nested_deps_c', 'test/nested_deps_a', 'test/nested_deps_b'], ['test/nested_deps_c']],
      [['test/nested_deps_c', 'test/nested_deps_b', 'test/nested_deps_a'], ['test/nested_deps_c']],
      // Complex dependencies, combining the above, with many intersections.
      [['test/deps_c', 'test/nested_deps_b'], ['test/deps_c', 'test/nested_deps_b']],
      [['test/no_deps_a', 'test/deps_c', 'test/nested_deps_b'], ['test/deps_c', 'test/nested_deps_b']],
      [['test/nested_deps_b', 'test/deps_c', 'test/no_deps_c'], ['test/nested_deps_b', 'test/deps_c', 'test/no_deps_c']],
    ];
  }

  /**
   * @covers ::getMinimalRepresentativeSubset
   *
   * @dataProvider providerTestGetMinimalRepresentativeSubset
   */
  public function testGetMinimalRepresentativeSubset(array $libraries, array $expected) {
    $this->assertEquals($expected, $this->libraryDependencyResolver->getMinimalRepresentativeSubset($libraries));
  }

  /**
   * @covers ::getMinimalRepresentativeSubset
   */
  public function testGetMinimalRepresentativeSubsetInvalidInput() {
    $this->expectException(\AssertionError::class);
    $this->expectExceptionMessage('$libraries can\'t contain duplicate items.');
    $this->libraryDependencyResolver->getMinimalRepresentativeSubset(['test/no_deps_a', 'test/no_deps_a']);
  }

}
