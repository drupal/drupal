<?php

/**
 * @file
 * Contains \Drupal\Tests\Component\PhpStorage\FileStorageTest.
 */

namespace Drupal\Tests\Component\PhpStorage;

use Drupal\Component\PhpStorage\FileStorage;

/**
 * @coversDefaultClass \Drupal\Component\PhpStorage\FileStorage
 * @group PhpStorage
 */
class FileStorageTest extends PhpStorageTestBase {

  /**
   * Standard test settings to pass to storage instances.
   *
   * @var array
   */
  protected $standardSettings;

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
   * Tests writeable() method.
   *
   * @group Drupal
   * @group PhpStorage
   */
  public function testWriteable() {
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

    // Make sure directory exists prior to removal.
    $this->assertTrue(file_exists(sys_get_temp_dir() . '/php/test'), 'File storage directory does not exist.');

    // Write out some files.
    $php = new FileStorage($this->standardSettings);
    $name = $this->randomMachineName() . '/' . $this->randomMachineName() . '.php';

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
