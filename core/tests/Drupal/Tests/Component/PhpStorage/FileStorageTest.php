<?php

namespace Drupal\Tests\Component\PhpStorage;

use Drupal\Component\PhpStorage\FileStorage;
use Drupal\Component\Utility\Random;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\Error\Warning;

/**
 * @coversDefaultClass \Drupal\Component\PhpStorage\FileStorage
 * @group Drupal
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
  protected function setUp() {
    parent::setUp();

    $this->standardSettings = [
      'directory' => $this->directory,
      'bin' => 'test',
    ];
  }

  /**
   * Tests basic load/save/delete operations.
   *
   * @covers ::load
   * @covers ::save
   * @covers ::exists
   * @covers ::delete
   */
  public function testCRUD() {
    $php = new FileStorage($this->standardSettings);
    $this->assertCRUD($php);
  }

  /**
   * @covers ::writeable
   */
  public function testWriteable() {
    $php = new FileStorage($this->standardSettings);
    $this->assertTrue($php->writeable());
  }

  /**
   * @covers ::deleteAll
   */
  public function testDeleteAll() {
    // Random generator.
    $random_generator = new Random();

    // Write out some files.
    $php = new FileStorage($this->standardSettings);

    $name = $random_generator->name(8, TRUE) . '/' . $random_generator->name(8, TRUE) . '.php';

    // Find a global that doesn't exist.
    do {
      $random = mt_rand(10000, 100000);
    } while (isset($GLOBALS[$random]));

    // Write out a PHP file and ensure it's successfully loaded.
    $code = "<?php\n\$GLOBALS[$random] = TRUE;";
    $this->assertTrue($php->save($name, $code), 'Saved php file');
    $php->load($name);
    $this->assertTrue($GLOBALS[$random], 'File saved correctly with correct value');

    // Make sure directory exists prior to removal.
    $this->assertDirectoryExists($this->directory . '/test');

    $this->assertTrue($php->deleteAll(), 'Delete all reported success');
    $this->assertFalse($php->load($name));
    $this->assertDirectoryNotExists($this->directory . '/test');

    // Should still return TRUE if directory has already been deleted.
    $this->assertTrue($php->deleteAll(), 'Delete all succeeds with nothing to delete');
    unset($GLOBALS[$random]);
  }

  /**
   * @covers ::createDirectory
   */
  public function testCreateDirectoryFailWarning() {
    $directory = new vfsStreamDirectory('permissionDenied', 0200);
    $storage = new FileStorage([
      'directory' => $directory->url(),
      'bin' => 'test',
    ]);
    $code = "<?php\n echo 'here';";
    $this->expectException(Warning::class);
    $this->expectExceptionMessage('mkdir(): Permission Denied');
    $storage->save('subdirectory/foo.php', $code);
  }

}
