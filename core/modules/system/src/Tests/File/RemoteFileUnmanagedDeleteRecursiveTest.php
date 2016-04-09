<?php

namespace Drupal\system\Tests\File;

/**
 * Tests the unmanaged file delete recursive function.
 *
 * @group File
 */
class RemoteFileUnmanagedDeleteRecursiveTest extends UnmanagedDeleteRecursiveTest {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('file_test');

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

  protected function setUp() {
    parent::setUp();
    $this->config('system.file')->set('default_scheme', 'dummy-remote')->save();
  }
}
