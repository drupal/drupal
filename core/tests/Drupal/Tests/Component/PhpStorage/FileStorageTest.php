<?php

/**
 * @file
 * Definition of Drupal\Tests\Component\PhpStorage\FileStorageTest.
 */

namespace Drupal\Tests\Component\PhpStorage;

/**
 * Tests the simple file storage.
 */
class FileStorageTest extends PhpStorageTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Simple file storage',
      'description' => 'Tests the FileStorage implementation.',
      'group' => 'PHP Storage',
    );
  }

  function setUp() {
    global $conf;
    parent::setUp();
    $dir_path = sys_get_temp_dir() . '/php';
    $conf['php_storage']['simpletest'] = array(
      'class' => 'Drupal\Component\PhpStorage\FileStorage',
      'directory' => $dir_path,
    );
    $conf['php_storage']['readonly'] = array(
      'class' => 'Drupal\Component\PhpStorage\FileReadOnlyStorage',
      'directory' => $dir_path,
      // Let this read from the bin where the other instance is writing.
      'bin' => 'simpletest',
    );
  }

  /**
   * Tests basic load/save/delete operations.
   */
  function testCRUD() {
    $php = $this->storageFactory->get('simpletest');
    $this->assertInstanceOf('Drupal\Component\PhpStorage\FileStorage', $php);
    $this->assertCRUD($php);
  }

  /**
   * Tests writing with one class and reading with another.
   */
  function testReadOnly() {
    $php = $this->storageFactory->get('simpletest');
    $name = $this->randomName() . '/' . $this->randomName() . '.php';

    // Find a global that doesn't exist.
    do {
      $random = mt_rand(10000, 100000);
    } while (isset($GLOBALS[$random]));

    // Write out a PHP file and ensure it's successfully loaded.
    $code = "<?php\n\$GLOBALS[$random] = TRUE;";
    $success = $php->save($name, $code);
    $this->assertSame($success, TRUE);
    $php_read = $this->storageFactory->get('readonly');
    $php_read->load($name);
    $this->assertTrue($GLOBALS[$random]);

    // If the file was successfully loaded, it must also exist, but ensure the
    // exists() method returns that correctly.
    $this->assertSame($php_read->exists($name), TRUE);
    // Saving and deleting should always fail.
    $this->assertFalse($php_read->save($name, $code));
    $this->assertFalse($php_read->delete($name));
  }
}
