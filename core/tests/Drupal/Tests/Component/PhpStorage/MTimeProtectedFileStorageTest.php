<?php

/**
 * @file
 * Definition of Drupal\Tests\Component\PhpStorage\MTimeProtectedFileStorageTest.
 */

namespace Drupal\Tests\Component\PhpStorage;

use Drupal\Component\PhpStorage\PhpStorageFactory;

/**
 * Tests the directory mtime based PHP loader implementation.
 */
class MTimeProtectedFileStorageTest extends PhpStorageTestBase {

  /**
   * The expected test results for the security test.
   *
   * The default implementation protects against even the filemtime change so
   * both iterations will return FALSE.
   */
  protected $expected = array(FALSE, FALSE);

  protected $storageClass = 'Drupal\Component\PhpStorage\MTimeProtectedFileStorage';

  public static function getInfo() {
    return array(
      'name' => 'MTime protected file storage',
      'description' => 'Tests the MTimeProtectedFileStorage implementation.',
      'group' => 'PHP Storage',
    );
  }

  function setUp() {
    global $conf;
    parent::setUp();
    $this->secret = $this->randomName();
    $conf['php_storage']['simpletest'] = array(
      'class' => $this->storageClass,
      'directory' => sys_get_temp_dir() . '/php',
      'secret' => $this->secret,
    );
  }

  /**
   * Tests basic load/save/delete operations.
   */
  function testCRUD() {
    $php = $this->storageFactory->get('simpletest');
    $this->assertSame(get_class($php), $this->storageClass);
    $this->assertCRUD($php);
  }

  /**
   * Tests the security of the MTimeProtectedFileStorage implementation.
   *
   * We test two attacks: first changes the file mtime, then the directory
   * mtime too.
   */
  function testSecurity() {
    $php = $this->storageFactory->get('simpletest');
    $name = 'simpletest.php';
    $php->save($name, '<?php');
    $expected_root_directory = sys_get_temp_dir() . '/php/simpletest';
    $expected_directory = $expected_root_directory . '/' . $name;
    $directory_mtime = filemtime($expected_directory);
    $expected_filename = $expected_directory . '/' . hash_hmac('sha256', $name, $this->secret . $directory_mtime) . '.php';

    // Ensure the file exists and that it and the containing directory have
    // minimal permissions. fileperms() can return high bits unrelated to
    // permissions, so mask with 0777.
    $this->assertTrue(file_exists($expected_filename));
    $this->assertSame(fileperms($expected_filename) & 0777, 0444);
    $this->assertSame(fileperms($expected_directory) & 0777, 0777);

    // Ensure the root directory for the bin has a .htaccess file denying web
    // access.
    $this->assertSame(file_get_contents($expected_root_directory . '/.htaccess'), "SetHandler Drupal_Security_Do_Not_Remove_See_SA_2006_006\nDeny from all\nOptions None\nOptions +FollowSymLinks");

    // Ensure that if the file is replaced with an untrusted one (due to another
    // script's file upload vulnerability), it does not get loaded. Since mtime
    // granularity is 1 second, we cannot prevent an attack that happens within
    // a second of the initial save().
    sleep(1);
    for ($i = 0; $i < 2; $i++) {
      $storageFactory = new PhpStorageFactory();
      $php = $this->storageFactory->get('simpletest');
      $GLOBALS['hacked'] = FALSE;
      $untrusted_code = "<?php\n" . '$GLOBALS["hacked"] = TRUE;';
      chmod($expected_directory, 0700);
      chmod($expected_filename, 0700);
      if ($i) {
        // Now try to write the file in such a way that the directory mtime
        // changes and invalidates the hash.
        file_put_contents($expected_filename . '.tmp', $untrusted_code);
        rename($expected_filename . '.tmp', $expected_filename);
      }
      else {
        // On the first try do not change the directory mtime but the filemtime
        // is now larger than the directory mtime.
        file_put_contents($expected_filename, $untrusted_code);
      }
      chmod($expected_filename, 0400);
      chmod($expected_directory, 0100);
      $this->assertSame(file_get_contents($expected_filename), $untrusted_code);
      $this->assertSame($php->exists($name), $this->expected[$i]);
      $this->assertSame($php->load($name), $this->expected[$i]);
      $this->assertSame($GLOBALS['hacked'], $this->expected[$i]);
    }
  }
}
