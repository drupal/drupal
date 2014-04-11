<?php

/**
 * @file
 * Contains \Drupal\Tests\Component\PhpStorage\FileStorageTest.
 */

namespace Drupal\Tests\Component\PhpStorage;

use Drupal\Component\PhpStorage\FileStorage;
use Drupal\Component\PhpStorage\FileReadOnlyStorage;

/**
 * Tests the simple file storage.
 *
 * @group Drupal
 * @group PhpStorage
 *
 * @coversDefaultClass \Drupal\Component\PhpStorage\FileStorage
 * @coversDefaultClass \Drupal\Component\PhpStorage\FileReadOnlyStorage
 */
class FileStorageTest extends PhpStorageTestBase {

  /**
   * Standard test settings to pass to storage instances.
   *
   * @var array
   */
  protected $standardSettings;

  /**
   * Read only test settings to pass to storage instances.
   *
   * @var array
   */
  protected $readonlyStorage;

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Simple file storage',
      'description' => 'Tests the FileStorage implementation.',
      'group' => 'PHP Storage',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $dir_path = sys_get_temp_dir() . '/php';

    $this->standardSettings = array(
      'directory' => $dir_path,
      'bin' => 'test',
    );
    $this->readonlyStorage = array(
      'directory' => $dir_path,
      // Let this read from the bin where the other instance is writing.
      'bin' => 'test',
    );
  }

  /**
   * Tests basic load/save/delete operations.
   *
   * @group Drupal
   * @group PhpStorage
   */
  public function testCRUD() {
    $php = new FileStorage($this->standardSettings);
    $this->assertCRUD($php);
  }

  /**
   * Tests writing with one class and reading with another.
   *
   * @group Drupal
   * @group PhpStorage
   */
  public function testReadOnly() {
    $php = new FileStorage($this->standardSettings);
    $name = $this->randomName() . '/' . $this->randomName() . '.php';

    // Find a global that doesn't exist.
    do {
      $random = mt_rand(10000, 100000);
    } while (isset($GLOBALS[$random]));

    // Write out a PHP file and ensure it's successfully loaded.
    $code = "<?php\n\$GLOBALS[$random] = TRUE;";
    $success = $php->save($name, $code);
    $this->assertSame($success, TRUE);
    $php_read = new FileReadOnlyStorage($this->readonlyStorage);
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
    $php_read = new FileReadOnlyStorage($this->readonlyStorage);
    $this->assertFalse($php_read->writeable());

    $php = new FileStorage($this->standardSettings);
    $this->assertTrue($php->writeable());
  }

  /**
   * Tests deleteAll() method.
   *
   * @group Drupal
   * @group PhpStorage
   */
  public function testDeleteAll() {
    $php_read = new FileReadOnlyStorage($this->readonlyStorage);
    $this->assertFalse($php_read->deleteAll());

    // Make sure directory exists prior to removal.
    $this->assertTrue(file_exists(sys_get_temp_dir() . '/php/test'), 'File storage directory does not exist.');

    // Write out some files.
    $php = new FileStorage($this->standardSettings);
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
    $this->assertFalse(file_exists(sys_get_temp_dir() . '/php/test'), 'File storage directory still exists after call to deleteAll().');

    // Should still return TRUE if directory has already been deleted.
    $this->assertTrue($php->deleteAll());
  }

}
