<?php

/**
 * @file
 * Contains \Drupal\Tests\Component\PhpStorage\FileStorageTest.
 */

namespace Drupal\Tests\Component\PhpStorage;

use Drupal\Component\Utility\Settings;

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

  public function setUp() {
    parent::setUp();
    $dir_path = sys_get_temp_dir() . '/php';
    $settings['php_storage']['simpletest'] = array(
      'class' => 'Drupal\Component\PhpStorage\FileStorage',
      'directory' => $dir_path,
    );
    $settings['php_storage']['readonly'] = array(
      'class' => 'Drupal\Component\PhpStorage\FileReadOnlyStorage',
      'directory' => $dir_path,
      // Let this read from the bin where the other instance is writing.
      'bin' => 'simpletest',
    );
    new Settings($settings);
  }

  /**
   * Tests basic load/save/delete operations.
   *
   * @group Drupal
   * @group PhpStorage
   */
  public function testCRUD() {
    $php = $this->storageFactory->get('simpletest');
    $this->assertInstanceOf('Drupal\Component\PhpStorage\FileStorage', $php);
    $this->assertCRUD($php);
  }

  /**
   * Tests writing with one class and reading with another.
   *
   * @group Drupal
   * @group PhpStorage
   */
  public function testReadOnly() {
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

  /**
   * Tests writeable() method.
   *
   * @group Drupal
   * @group PhpStorage
   */
  public function testWriteable() {
    $php_read = $this->storageFactory->get('readonly');
    $this->assertFalse($php_read->writeable());

    $php = $this->storageFactory->get('simpletest');
    $this->assertTrue($php->writeable());
  }

  /**
   * Tests deleteAll() method.
   *
   * @group Drupal
   * @group PhpStorage
   */
  public function testDeleteAll() {
    $php_read = $this->storageFactory->get('readonly');
    $this->assertFalse($php_read->deleteAll());

    // Make sure directory exists prior to removal.
    $this->assertTrue(file_exists(sys_get_temp_dir() . '/php/simpletest'), 'File storage directory does not exist.');

    // Write out some files.
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
    $php->load($name);
    $this->assertTrue($GLOBALS[$random]);

    $this->assertTrue($php->deleteAll());
    $this->assertFalse($php->load($name));
    $this->assertFalse(file_exists(sys_get_temp_dir() . '/php/simpletest'), 'File storage directory still exists after call to deleteAll().');

    // Should still return TRUE if directory has already been deleted.
    $this->assertTrue($php->deleteAll());
  }

}
