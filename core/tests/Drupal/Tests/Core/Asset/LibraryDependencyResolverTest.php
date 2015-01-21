<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Asset\LibraryDependencyResolverTest.
 */

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
   * @var \Drupal\Core\Asset\LibraryDiscoveryInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $libraryDiscovery;

  /**
   * The mocked module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $moduleHandler;

  /**
   * Test library data.
   *
   * @var array
   */
  protected $libraryData = array(
    'no_deps_a' => ['js' => [], 'css' => []],
    'no_deps_b' => ['js' => [], 'css' => []],
    'no_deps_c' => ['js' => [], 'css' => []],
    'deps_a' => ['js' => [], 'css' => [], 'dependencies' => ['test/no_deps_a']],
    'deps_b' => ['js' => [], 'css' => [], 'dependencies' => ['test/no_deps_a', 'test/no_deps_b']],
    'deps_c' => ['js' => [], 'css' => [], 'dependencies' => ['test/no_deps_b', 'test/no_deps_a']],
    'nested_deps_a' => ['js' => [], 'css' => [], 'dependencies' => ['test/deps_a']],
    'nested_deps_b' => ['js' => [], 'css' => [], 'dependencies' => ['test/nested_deps_a']],
    'nested_deps_c' => ['js' => [], 'css' => [], 'dependencies' => ['test/nested_deps_b']],
  );

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->libraryDiscovery = $this->getMockBuilder('Drupal\Core\Asset\LibraryDiscovery')
      ->disableOriginalConstructor()
      ->setMethods(['getLibrariesByExtension'])
      ->getMock();
    $this->libraryDiscovery->expects($this->any())
      ->method('getLibrariesByExtension')
      ->with('test')
      ->will($this->returnValue($this->libraryData));
    $this->libraryDependencyResolver= new LibraryDependencyResolver($this->libraryDiscovery);
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
      [['test/nested_deps_a', 'test/nested_deps_c'],                       ['test/no_deps_a', 'test/deps_a', 'test/nested_deps_a', 'test/nested_deps_b', 'test/nested_deps_c']],
      [['test/nested_deps_b', 'test/nested_deps_c'],                       ['test/no_deps_a', 'test/deps_a', 'test/nested_deps_a', 'test/nested_deps_b', 'test/nested_deps_c']],
      [['test/nested_deps_c', 'test/nested_deps_a'],                       ['test/no_deps_a', 'test/deps_a', 'test/nested_deps_a', 'test/nested_deps_b', 'test/nested_deps_c']],
      [['test/nested_deps_a', 'test/nested_deps_b', 'test/nested_deps_c'], ['test/no_deps_a', 'test/deps_a', 'test/nested_deps_a', 'test/nested_deps_b', 'test/nested_deps_c']],
      [['test/nested_deps_a', 'test/nested_deps_c', 'test/nested_deps_b'], ['test/no_deps_a', 'test/deps_a', 'test/nested_deps_a', 'test/nested_deps_b', 'test/nested_deps_c']],
      [['test/nested_deps_b', 'test/nested_deps_a', 'test/nested_deps_c'], ['test/no_deps_a', 'test/deps_a', 'test/nested_deps_a', 'test/nested_deps_b', 'test/nested_deps_c']],
      [['test/nested_deps_b', 'test/nested_deps_c', 'test/nested_deps_a'], ['test/no_deps_a', 'test/deps_a', 'test/nested_deps_a', 'test/nested_deps_b', 'test/nested_deps_c']],
      [['test/nested_deps_c', 'test/nested_deps_a', 'test/nested_deps_b'], ['test/no_deps_a', 'test/deps_a', 'test/nested_deps_a', 'test/nested_deps_b', 'test/nested_deps_c']],
      [['test/nested_deps_c', 'test/nested_deps_b', 'test/nested_deps_a'], ['test/no_deps_a', 'test/deps_a', 'test/nested_deps_a', 'test/nested_deps_b', 'test/nested_deps_c']],
      // Complex dependencies, combining the above, with many intersections.
      [['test/deps_c', 'test/nested_deps_b'],                   ['test/no_deps_b', 'test/no_deps_a', 'test/deps_c', 'test/deps_a', 'test/nested_deps_a', 'test/nested_deps_b']],
      [['test/no_deps_a', 'test/deps_c', 'test/nested_deps_b'], ['test/no_deps_a', 'test/no_deps_b', 'test/deps_c', 'test/deps_a', 'test/nested_deps_a', 'test/nested_deps_b']],
      [['test/nested_deps_b', 'test/deps_c', 'test/no_deps_c'], ['test/no_deps_a', 'test/deps_a', 'test/nested_deps_a', 'test/nested_deps_b', 'test/no_deps_b', 'test/deps_c', 'test/no_deps_c']],
    ];
  }

  /**
   * @covers ::getLibrariesWithDependencies()
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
      [['test/nested_deps_a', 'test/nested_deps_c'],                       ['test/nested_deps_c']],
      [['test/nested_deps_b', 'test/nested_deps_c'],                       ['test/nested_deps_c']],
      [['test/nested_deps_c', 'test/nested_deps_a'],                       ['test/nested_deps_c']],
      [['test/nested_deps_a', 'test/nested_deps_b', 'test/nested_deps_c'], ['test/nested_deps_c']],
      [['test/nested_deps_a', 'test/nested_deps_c', 'test/nested_deps_b'], ['test/nested_deps_c']],
      [['test/nested_deps_b', 'test/nested_deps_a', 'test/nested_deps_c'], ['test/nested_deps_c']],
      [['test/nested_deps_b', 'test/nested_deps_c', 'test/nested_deps_a'], ['test/nested_deps_c']],
      [['test/nested_deps_c', 'test/nested_deps_a', 'test/nested_deps_b'], ['test/nested_deps_c']],
      [['test/nested_deps_c', 'test/nested_deps_b', 'test/nested_deps_a'], ['test/nested_deps_c']],
      // Complex dependencies, combining the above, with many intersections.
      [['test/deps_c', 'test/nested_deps_b'],                   ['test/deps_c', 'test/nested_deps_b']],
      [['test/no_deps_a', 'test/deps_c', 'test/nested_deps_b'], ['test/deps_c', 'test/nested_deps_b']],
      [['test/nested_deps_b', 'test/deps_c', 'test/no_deps_c'], ['test/nested_deps_b', 'test/deps_c', 'test/no_deps_c']],
    ];
  }

  /**
   * @covers ::getMinimalRepresentativeSubset()
   *
   * @dataProvider providerTestGetMinimalRepresentativeSubset
   */
  public function testGetMinimalRepresentativeSubset(array $libraries, array $expected) {
    $this->assertEquals($expected, $this->libraryDependencyResolver->getMinimalRepresentativeSubset($libraries));
  }

}
