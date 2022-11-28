<?php

namespace Drupal\Tests\Core\Asset;

use Drupal\Core\Asset\LibraryDiscovery;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Asset\LibraryDiscovery
 * @group Asset
 */
class LibraryDiscoveryTest extends UnitTestCase {

  /**
   * The tested library discovery service.
   *
   * @var \Drupal\Core\Asset\LibraryDiscovery
   */
  protected $libraryDiscovery;

  /**
   * The mocked library discovery cache collector.
   *
   * @var \Drupal\Core\Cache\CacheCollectorInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $libraryDiscoveryCollector;

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
    $this->libraryDiscoveryCollector = $this->getMockBuilder('Drupal\Core\Asset\LibraryDiscoveryCollector')
      ->disableOriginalConstructor()
      ->getMock();
    $this->libraryDiscovery = new LibraryDiscovery($this->libraryDiscoveryCollector);
    $this->libraryDiscoveryCollector->expects($this->once())
      ->method('get')
      ->with('test')
      ->willReturn($this->libraryData);
  }

  /**
   * @covers ::getLibrariesByExtension
   */
  public function testGetLibrariesByExtension() {
    $this->libraryDiscovery->getLibrariesByExtension('test');
    // Verify that subsequent calls don't trigger hook_library_info_alter()
    // and hook_js_settings_alter() invocations, nor do they talk to the
    // collector again. This ensures that the alterations made by
    // hook_library_info_alter() and hook_js_settings_alter() implementations
    // are statically cached, as desired.
    $this->libraryDiscovery->getLibraryByName('test', 'test_1');
    $this->libraryDiscovery->getLibrariesByExtension('test');
  }

  /**
   * Tests getting a library by name.
   *
   * @covers ::getLibraryByName
   */
  public function testGetLibraryByName() {
    $this->assertSame($this->libraryData['test_1'], $this->libraryDiscovery->getLibraryByName('test', 'test_1'));
  }

  /**
   * Tests getting a deprecated library.
   */
  public function testAssetLibraryDeprecation() {
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
