<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\File\MimeType;

use Drupal\KernelTests\KernelTestBase;

// cspell:ignore garply tarz

/**
 * Tests filename mimetype detection.
 *
 * Installing the 'file_deprecated_test' module allows the legacy hook
 * file_deprecated_test_file_mimetype_mapping_alter to execute and add some
 * mappings. We check here that they are.
 *
 * @group File
 * @group legacy
 * @coversDefaultClass \Drupal\Core\File\MimeType\ExtensionMimeTypeGuesser
 */
class ExtensionMimeTypeGuesserLegacyTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['file_deprecated_test', 'file_test'];

  /**
   * Tests mapping of mimetypes from filenames.
   *
   * @covers ::guessMimeType
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

    $this->expectDeprecation(
      'The deprecated alter hook hook_file_mimetype_mapping_alter() is implemented in these locations: file_deprecated_test_file_mimetype_mapping_alter. This hook is deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. Implement a \Drupal\Core\File\Event\MimeTypeMapLoadedEvent listener instead. See https://www.drupal.org/node/3494040'
    );

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

  /**
   * Tests mapping of mimetypes from filenames.
   *
   * @covers ::guessMimeType
   * @covers ::setMapping
   */
  public function testFileMimeTypeDetectionCustomMapping(): void {
    /** @var \Drupal\Core\File\MimeType\ExtensionMimeTypeGuesser $extension_guesser */
    $extension_guesser = \Drupal::service('file.mime_type.guesser.extension');

    // Pass in a custom mapping.
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

    $this->expectDeprecation(
      'Drupal\Core\File\MimeType\ExtensionMimeTypeGuesser::setMapping() is deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. Use \Drupal\Core\File\MimeType\MimeTypeMapInterface::addMapping() instead or define your own MimeTypeMapInterface implementation. See https://www.drupal.org/node/3494040'
    );
    $extension_guesser->setMapping($mapping);

    $test_case = [
      'test.jar' => 'application/java-archive',
      'test.jpeg' => 'image/jpeg',
      'test.jpg' => 'image/jpeg',
      'test.jar.jpg' => 'image/jpeg',
      'test.jpg.jar' => 'application/java-archive',
      'test.pcf.z' => 'application/x-font',
      'test.garply.waldo' => 'application/x-garply-waldo',
      'pcf.z' => 'application/x-compress',
      'jar' => NULL,
      'garply.waldo' => NULL,
      'some.junk' => NULL,
      'foo.file_test_1' => 'made_up/file_test_1',
      'foo.file_test_2' => 'made_up/file_test_2',
      'foo.doc' => 'made_up/doc',
      'test.ogg' => 'audio/ogg',
      'foobar.z' => 'application/x-compress',
      'foobar.tar' => 'application/x-tar',
      'foobar.tar.z' => 'application/x-tarz',
    ];

    foreach ($test_case as $input => $expected) {
      $output = $extension_guesser->guessMimeType($input);
      $this->assertSame($expected, $output, 'Failed for extension ' . $input);
    }
  }

}
