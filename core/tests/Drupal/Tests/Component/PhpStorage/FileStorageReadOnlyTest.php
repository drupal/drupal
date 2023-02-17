<?php

namespace Drupal\Tests\Component\PhpStorage;

use Drupal\Component\PhpStorage\FileStorage;
use Drupal\Component\PhpStorage\FileReadOnlyStorage;
use Drupal\Component\Utility\Random;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;

/**
 * @coversDefaultClass \Drupal\Component\PhpStorage\FileReadOnlyStorage
 *
 * @group Drupal
 * @group PhpStorage
 */
class FileStorageReadOnlyTest extends PhpStorageTestBase {

  use ExpectDeprecationTrait;

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
  protected function setUp(): void {
    parent::setUp();

    $this->standardSettings = [
      'directory' => $this->directory,
      'bin' => 'test',
    ];
    $this->readonlyStorage = [
      'directory' => $this->directory,
      // Let this read from the bin where the other instance is writing.
      'bin' => 'test',
    ];
  }

  /**
   * Tests writing with one class and reading with another.
   */
  public function testReadOnly() {
    // Random generator.
    $random = new Random();

    $php = new FileStorage($this->standardSettings);
    $name = $random->name(8, TRUE) . '/' . $random->name(8, TRUE) . '.php';

    // Find a global that doesn't exist.
    do {
      $random = 'test' . mt_rand(10000, 100000);
    } while (isset($GLOBALS[$random]));

    // Write out a PHP file and ensure it's successfully loaded.
    $code = "<?php\n\$GLOBALS['$random'] = TRUE;";
    $success = $php->save($name, $code);
    $this->assertTrue($success);
    $php_read = new FileReadOnlyStorage($this->readonlyStorage);
    $php_read->load($name);
    $this->assertTrue($GLOBALS[$random]);

    // If the file was successfully loaded, it must also exist, but ensure the
    // exists() method returns that correctly.
    $this->assertTrue($php_read->exists($name));
    // Saving and deleting should always fail.
    $this->assertFalse($php_read->save($name, $code));
    $this->assertFalse($php_read->delete($name));
    unset($GLOBALS[$random]);
  }

  /**
   * @covers ::writeable
   * @group legacy
   */
  public function testWritable() {
    $this->expectDeprecation('Drupal\Component\PhpStorage\FileReadOnlyStorage::writeable() is deprecated in drupal:10.1.0 and will be removed from drupal:11.0.0. There is no replacement. See https://www.drupal.org/node/3155413');
    $php_read = new FileReadOnlyStorage($this->readonlyStorage);
    $this->assertFalse($php_read->writeable());
  }

  /**
   * @covers ::deleteAll
   */
  public function testDeleteAll() {
    // Random generator.
    $random = new Random();

    $php = new FileStorage($this->standardSettings);
    $name = $random->name(8, TRUE) . '/' . $random->name(8, TRUE) . '.php';

    // Find a global that doesn't exist.
    do {
      $random = mt_rand(10000, 100000);
    } while (isset($GLOBALS[$random]));

    // Write our the file so we can test deleting.
    $code = "<?php\n\$GLOBALS[$random] = TRUE;";
    $this->assertTrue($php->save($name, $code));

    $php_read = new FileReadOnlyStorage($this->readonlyStorage);
    $this->assertFalse($php_read->deleteAll());

    // Make sure directory exists prior to removal.
    $this->assertDirectoryExists($this->directory . '/test');
  }

}
