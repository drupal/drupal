<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\StreamWrapper;

use Drupal\Core\StreamWrapper\StreamWrapperManager;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests Drupal\Core\StreamWrapper\StreamWrapperManager.
 */
#[CoversClass(StreamWrapperManager::class)]
#[Group('File')]
#[RunTestsInSeparateProcesses]
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
  protected function setUp(): void {
    parent::setUp();
    $this->streamWrapperManager = \Drupal::service('stream_wrapper_manager');
  }

  /**
   * Tests uri scheme.
   *
   * @legacy-covers ::getScheme
   */
  #[DataProvider('providerTestUriScheme')]
  public function testUriScheme($uri, $expected): void {
    $this->assertSame($expected, StreamWrapperManager::getScheme($uri));
  }

  /**
   * Data provider.
   */
  public static function providerTestUriScheme(): array {
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
