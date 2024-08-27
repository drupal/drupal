<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\File;

/**
 * Tests the unmanaged file save data function.
 *
 * @group File
 */
class RemoteFileSaveDataTest extends FileSaveDataTest {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['file_test'];

  /**
   * A stream wrapper scheme to register for the test.
   *
   * @var string
   */
  protected $scheme = 'dummy-remote';

  /**
   * A fully-qualified stream wrapper class name to register for the test.
   *
   * @var string
   */
  protected $classname = 'Drupal\file_test\StreamWrapper\DummyRemoteStreamWrapper';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->config('system.file')->set('default_scheme', 'dummy-remote')->save();
  }

}
