<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\File\ExtensionMimeTypeGuesserTest.
 */

namespace Drupal\Tests\Core\File {

use Drupal\Core\File\MimeType\ExtensionMimeTypeGuesser;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\File\MimeType\ExtensionMimeTypeGuesser
 * @group File
 */
class ExtensionMimeTypeGuesserTest extends UnitTestCase {

  /**
   * @covers ::guess
   * @dataProvider guesserDataProvider
   */
  public function testGuesser($path, $mime_type) {
    $guesser = new ExtensionMimeTypeGuesser();
    $this->assertEquals($mime_type, $guesser->guess($path));
  }

  /**
   * Provides data for  ExtensionMimeTypeGuesserTest::testGuesser().
   */
  public function guesserDataProvider() {
    return [
      ['test.jar', 'application/java-archive'],
      ['test.jpeg', 'image/jpeg'],
      ['test.JPEG', 'image/jpeg'],
      ['test.jpg', 'image/jpeg'],
      ['test.jar.jpg', 'image/jpeg'],
      ['test.jpg.jar', 'application/java-archive'],
      ['test.pcf.Z', 'application/x-font'],
      ['pcf.z', 'application/octet-stream'],
      ['jar', 'application/octet-stream'],
      ['some.junk', 'application/octet-stream'],
      ['foo.file_test_1', 'application/octet-stream'],
      ['foo.file_test_2', 'application/octet-stream'],
      ['foo.doc', 'application/msword'],
      ['test.ogg', 'audio/ogg'],
    ];
  }

}

}
namespace {
  if (!function_exists('drupal_basename')) {
    function drupal_basename($uri, $suffix = NULL) {
      return basename($uri, $suffix);
    }
  }
}
