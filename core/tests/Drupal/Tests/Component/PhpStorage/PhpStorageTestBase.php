<?php

/**
 * @file
 * Contains \Drupal\Tests\Component\PhpStorage\PhpStorageTestBase.
 */

namespace Drupal\Tests\Component\PhpStorage;

use Drupal\Component\PhpStorage\PhpStorageInterface;
use Drupal\Tests\UnitTestCase;
use org\bovigo\vfs\vfsStream;

/**
 * Base test for PHP storages.
 */
abstract class PhpStorageTestBase extends UnitTestCase {

  /**
   * A unique per test class directory path to test php storage.
   *
   * @var string
   */
  protected $directory;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    vfsStream::setup('exampleDir');
    $this->directory = vfsStream::url('exampleDir');
  }

  /**
   * Assert that a PHP storage's load/save/delete operations work.
   */
  public function assertCRUD($php) {
    $name = $this->randomMachineName() . '/' . $this->randomMachineName() . '.php';

    // Find a global that doesn't exist.
    do {
      $random = mt_rand(10000, 100000);
    } while (isset($GLOBALS[$random]));

    // Write out a PHP file and ensure it's successfully loaded.
    $code = "<?php\n\$GLOBALS[$random] = TRUE;";
    $success = $php->save($name, $code);
    $this->assertTrue($success, 'Saved php file');
    $php->load($name);
    $this->assertTrue($GLOBALS[$random], 'File saved correctly with correct value');

    // Run additional asserts.
    $this->additionalAssertCRUD($php, $name);

    // If the file was successfully loaded, it must also exist, but ensure the
    // exists() method returns that correctly.
    $this->assertTrue($php->exists($name), 'Exists works correctly');

    // Delete the file, and then ensure exists() returns FALSE.
    $this->assertTrue($php->delete($name), 'Delete succeeded');
    $this->assertFalse($php->exists($name), 'Delete deleted file');

    // Ensure delete() can be called on a non-existing file. It should return
    // FALSE, but not trigger errors.
    $this->assertFalse($php->delete($name), 'Delete fails on missing file');
    unset($GLOBALS[$random]);
  }

  /**
   * Additional asserts to be run.
   *
   * @param \Drupal\Component\PhpStorage\PhpStorageInterface $php
   *   The PHP storage object.
   * @param string $name
   *   The name of an object. It should exist in the storage.
   */
  protected function additionalAssertCRUD(PhpStorageInterface $php, $name) {
    // By default do not do any additional asserts. This is a way of extending
    // tests in contrib.
  }

}
