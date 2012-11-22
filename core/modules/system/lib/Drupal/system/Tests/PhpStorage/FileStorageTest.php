<?php

/**
 * @file
 * Definition of Drupal\system\Tests\PhpStorage\FileStorageTest.
 */

namespace Drupal\system\Tests\PhpStorage;

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
    $conf['php_storage']['simpletest'] = array(
      'class' => 'Drupal\Component\PhpStorage\FileStorage',
      'directory' => DRUPAL_ROOT . '/' . variable_get('file_public_path', conf_path() . '/files') . '/php',
    );
    $conf['php_storage']['readonly'] = array(
      'class' => 'Drupal\Component\PhpStorage\FileReadOnlyStorage',
      'directory' => DRUPAL_ROOT . '/' . variable_get('file_public_path', conf_path() . '/files') . '/php',
      // Let this read from the bin where the other instance is writing.
      'bin' => 'simpletest',
    );
  }

  /**
   * Tests basic load/save/delete operations.
   */
  function testCRUD() {
    $php = $this->storageFactory->get('simpletest');
    $this->assertIdentical(get_class($php), 'Drupal\Component\PhpStorage\FileStorage');
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
    $this->assertIdentical($success, TRUE);
    $php_read = $this->storageFactory->get('readonly');
    $php_read->load($name);
    $this->assertTrue($GLOBALS[$random]);

    // If the file was successfully loaded, it must also exist, but ensure the
    // exists() method returns that correctly.
    $this->assertIdentical($php_read->exists($name), TRUE);
    // Saving and deleting should always fail.
    $this->assertFalse($php_read->save($name, $code));
    $this->assertFalse($php_read->delete($name));
  }
}
