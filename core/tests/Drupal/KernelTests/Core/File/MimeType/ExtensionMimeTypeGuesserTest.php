<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\File\MimeType;

use Drupal\KernelTests\KernelTestBase;

// cspell:ignore garply tarz

/**
 * Tests filename mimetype detection.
 *
 * Installing the 'file_test' module allows DummyMimeTypeMapLoadedSubscriber
 * to execute and add some mappings. We check here that they are.
 *
 * @group File
 */
class ExtensionMimeTypeGuesserTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['file_test'];

  /**
   * Tests mapping of mimetypes from filenames.
   */
  public function testGuessMimeType(): void {
    $prefixes = ['public://', 'private://', 'temporary://', 'dummy-remote://'];

    $test_case = [
      'test.jar' => 'application/java-archive',
      'test.jpeg' => 'image/jpeg',
      'test.JPEG' => 'image/jpeg',
      'test.jpg' => 'image/jpeg',
      'test.jar.jpg' => 'image/jpeg',
      'test.jpg.jar' => 'application/java-archive',
      'test.pcf.Z' => 'application/x-font',
      'test.garply.waldo' => 'application/x-garply-waldo',
      'pcf.z' => 'application/x-compress',
      'jar' => NULL,
      'garply.waldo' => NULL,
      'some.junk' => NULL,
      // Mime type added by file_test_mimetype_alter()
      'foo.file_test_1' => 'made_up/file_test_1',
      'foo.file_test_2' => 'made_up/file_test_2',
      'foo.doc' => 'made_up/doc',
      'test.ogg' => 'audio/ogg',
      'foobar.z' => 'application/x-compress',
      'foobar.tar' => 'application/x-tar',
      'foobar.tar.z' => 'application/x-tarz',
      'foobar.0.zip' => 'application/zip',
      'foobar..zip' => 'application/zip',
    ];

    /** @var \Drupal\Core\File\MimeType\ExtensionMimeTypeGuesser $guesser */
    $guesser = \Drupal::service('file.mime_type.guesser.extension');
    // Test using default mappings.
    foreach ($test_case as $input => $expected) {
      // Test stream [URI].
      foreach ($prefixes as $prefix) {
        $output = $guesser->guessMimeType($prefix . $input);
        $this->assertSame($expected, $output);
      }

      // Test normal path equivalent.
      $output = $guesser->guessMimeType($input);
      $this->assertSame($expected, $output);
    }
  }

}
