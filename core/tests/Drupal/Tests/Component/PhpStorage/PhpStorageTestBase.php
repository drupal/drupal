<?php

/**
 * @file
 * Contains \Drupal\Tests\Component\PhpStorage\PhpStorageTestBase.
 */

namespace Drupal\Tests\Component\PhpStorage;

use Drupal\Tests\UnitTestCase;
use Drupal\Core\PhpStorage\PhpStorageFactory;

/**
 * Base test for PHP storages.
 */
abstract class PhpStorageTestBase extends UnitTestCase {

  /**
   * The storage factory object.
   *
   * @var \Drupal\Component\PhpStorage\PhpStorageFactory
   */
  protected $storageFactory;

  /**
   * {@inheritdoc}
   */
  function setUp() {
    parent::setUp();

    $this->storageFactory = new PhpStorageFactory();
  }

  /**
   * Assert that a PHP storage's load/save/delete operations work.
   */
  public function assertCRUD($php) {
    $name = $this->randomName() . '/' . $this->randomName() . '.php';

    // Find a global that doesn't exist.
    do {
      $random = mt_rand(10000, 100000);
    } while (isset($GLOBALS[$random]));

    // Write out a PHP file and ensure it's successfully loaded.
    $code = "<?php\n\$GLOBALS[$random] = TRUE;";
    $success = $php->save($name, $code);
    $this->assertSame($success, TRUE);
    $php->load($name);
    $this->assertTrue($GLOBALS[$random]);

    // If the file was successfully loaded, it must also exist, but ensure the
    // exists() method returns that correctly.
    $this->assertSame($php->exists($name), TRUE);

    // Delete the file, and then ensure exists() returns FALSE.
    $success = $php->delete($name);
    $this->assertSame($success, TRUE);
    $this->assertSame($php->exists($name), FALSE);

    // Ensure delete() can be called on a non-existing file. It should return
    // FALSE, but not trigger errors.
    $this->assertSame($php->delete($name), FALSE);
  }

}
