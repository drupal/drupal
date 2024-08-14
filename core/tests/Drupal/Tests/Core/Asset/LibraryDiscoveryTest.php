<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Asset;

use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Asset\LibraryDiscovery
 * @group Asset
 */
class LibraryDiscoveryTest extends UnitTestCase {

  /**
   * The tested library discovery service.
   *
   * @var \Drupal\Core\Asset\LibraryDiscoveryCollector
   */
  protected $libraryDiscovery;

  /**
   * The cache tags invalidator.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $cacheTagsInvalidator;

  /**
   * Test library data.
   *
   * @var array
   */
  protected $libraryData = [
    'test_1' => [
      'js' => [],
      'css' => [
        'foo.css' => [],
      ],
    ],
    'test_2' => [
      'js' => [
        'bar.js' => [],
      ],
      'css' => [],
    ],
    'test_3' => [
      'js' => [
        'baz.js' => [],
      ],
      'css' => [],
      'deprecated' => 'The "%library_id%" asset library is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use the test_2 library instead. See https://www.example.com',
    ],
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->cacheTagsInvalidator = $this->createMock('Drupal\Core\Cache\CacheTagsInvalidatorInterface');
    $this->libraryDiscovery = $this->getMockBuilder('Drupal\Core\Asset\LibraryDiscoveryCollector')
      ->onlyMethods(['resolveCacheMiss', 'getLibrariesByExtension'])
      ->disableOriginalConstructor()
      ->getMock();
    $this->libraryDiscovery->expects($this->any())
      ->method('resolveCacheMiss')
      ->with('test')
      ->willReturn($this->libraryData);
    $this->libraryDiscovery->expects($this->any())
      ->method('getLibrariesByExtension')
      ->with('test')
      ->willReturn($this->libraryData);
  }

  /**
   * Tests getting a library by name.
   *
   * @covers ::getLibraryByName
   */
  public function testGetLibraryByName(): void {
    $this->assertSame($this->libraryData['test_1'], $this->libraryDiscovery->getLibraryByName('test', 'test_1'));
  }

  /**
   * Tests getting a deprecated library.
   */
  public function testAssetLibraryDeprecation(): void {
    $previous_error_handler = set_error_handler(function ($severity, $message, $file, $line) use (&$previous_error_handler) {
      // Convert deprecation error into a catchable exception.
      if ($severity === E_USER_DEPRECATED) {
        throw new \ErrorException($message, 0, $severity, $file, $line);
      }
      if ($previous_error_handler) {
        return $previous_error_handler($severity, $message, $file, $line);
      }
    });

    try {
      $this->libraryDiscovery->getLibraryByName('test', 'test_3');
      $this->fail('No deprecation error triggered.');
    }
    catch (\ErrorException $e) {
      $this->assertSame('The "test/test_3" asset library is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use the test_2 library instead. See https://www.example.com', $e->getMessage());
    }

    restore_error_handler();
  }

}
