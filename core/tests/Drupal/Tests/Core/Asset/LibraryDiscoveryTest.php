<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Asset\LibraryDiscoveryTest.
 */

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
   * @var \Drupal\Core\Cache\CacheCollectorInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $libraryDiscoveryCollector;

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
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->libraryDiscoveryCollector = $this->getMockBuilder('Drupal\Core\Asset\LibraryDiscoveryCollector')
      ->disableOriginalConstructor()
      ->getMock();
    $this->moduleHandler = $this->getMock('Drupal\Core\Extension\ModuleHandlerInterface');
    $this->libraryDiscovery = new LibraryDiscovery($this->libraryDiscoveryCollector, $this->moduleHandler);
  }

  /**
   * @covers ::getLibrariesByExtension()
   */
  public function testGetLibrariesByExtension() {
    $this->libraryDiscoveryCollector->expects($this->once())
      ->method('get')
      ->with('test')
      ->willReturn($this->libraryData);
    $this->moduleHandler->expects($this->exactly(2))
      ->method('alter')
      ->with(
        'library',
        $this->logicalOr($this->libraryData['test_1'], $this->libraryData['test_2']),
        $this->logicalOr('test/test_1', 'test/test_2')
      );

    $this->libraryDiscovery->getLibrariesbyExtension('test');
    // Verify that subsequent calls don't trigger hook_library_alter()
    // invocations, nor do they talk to the collector again. This ensures that
    // the alterations made by hook_library_alter() implementations are
    // statically cached, as desired.
    $this->libraryDiscovery->getLibraryByName('test', 'test_1');
    $this->libraryDiscovery->getLibrariesbyExtension('test');
  }

}
