<?php

namespace Drupal\KernelTests\Core\StreamWrapper;

use Drupal\Core\StreamWrapper\StreamWrapperManager;
use Drupal\KernelTests\KernelTestBase;

/**
 * @coversDefaultClass \Drupal\Core\StreamWrapper\StreamWrapperManager
 * @group File
 */
class StreamWrapperManagerTest extends KernelTestBase {

  /**
   * The stream wrapper manager.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface
   */
  protected $streamWrapperManager;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->streamWrapperManager = \Drupal::service('stream_wrapper_manager');
  }

  /**
   * @covers ::getScheme
   *
   * @dataProvider providerTestUriScheme
   */
  public function testUriScheme($uri, $expected) {
    $this->assertSame($expected, StreamWrapperManager::getScheme($uri));
  }

  /**
   * Data provider.
   */
  public function providerTestUriScheme() {
    $data = [];
    $data[] = [
      'public://filename',
      'public',
    ];
    $data[] = [
      'public://extra://',
      'public',
    ];
    $data[] = [
      'invalid',
      FALSE,
    ];
    return $data;
  }

}
