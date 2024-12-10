<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\File;

// cspell:ignore garply tarz

/**
 * Tests filename mimetype detection.
 *
 * @group File
 */
class MimeTypeTest extends FileTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['file_test'];

  /**
   * Tests mapping of mimetypes from filenames.
   */
  public function testFileMimeTypeDetection(): void {
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
      'jar' => 'application/octet-stream',
      'garply.waldo' => 'application/octet-stream',
      'some.junk' => 'application/octet-stream',
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

    $guesser = $this->container->get('file.mime_type.guesser');
    // Test using default mappings.
    foreach ($test_case as $input => $expected) {
      // Test stream [URI].
      foreach ($prefixes as $prefix) {
        $output = $guesser->guessMimeType($prefix . $input);
        $this->assertSame($expected, $output);
      }

      // Test normal path equivalent
      $output = $guesser->guessMimeType($input);
      $this->assertSame($expected, $output);
    }

    // Now test the extension guesser by passing in a custom mapping.
    $mapping = [
      'mimetypes' => [
        0 => 'application/java-archive',
        1 => 'image/jpeg',
      ],
      'extensions' => [
        'jar' => 0,
        'jpg' => 1,
      ],
    ];

    $test_case = [
      'test.jar' => 'application/java-archive',
      'test.jpeg' => NULL,
      'test.jpg' => 'image/jpeg',
      'test.jar.jpg' => 'image/jpeg',
      'test.jpg.jar' => 'application/java-archive',
      'test.pcf.z' => NULL,
      'test.garply.waldo' => NULL,
      'pcf.z' => NULL,
      'jar' => NULL,
      'garply.waldo' => NULL,
      'some.junk' => NULL,
      'foo.file_test_1' => NULL,
      'foo.file_test_2' => NULL,
      'foo.doc' => NULL,
      'test.ogg' => NULL,
      'foobar.z' => NULL,
      'foobar.tar' => NULL,
      'foobar.tar.z' => NULL,
    ];
    $extension_guesser = $this->container->get('file.mime_type.guesser.extension');
    $extension_guesser->setMapping($mapping);

    foreach ($test_case as $input => $expected) {
      $output = $extension_guesser->guessMimeType($input);
      $this->assertSame($expected, $output);
    }
  }

}
