<?php

declare(strict_types=1);

namespace Drupal\Tests\file\Kernel\Plugin\Validation\Constraint;

use Drupal\file\Entity\File;
use Drupal\Tests\file\Kernel\Validation\FileValidatorTestBase;

/**
 * Tests the FileExtensionConstraintValidator.
 *
 * @group file
 * @coversDefaultClass \Drupal\file\Plugin\Validation\Constraint\FileExtensionConstraintValidator
 */
class FileExtensionConstraintValidatorTest extends FileValidatorTestBase {

  /**
   * Tests the FileExtensionConstraintValidator.
   *
   * @param array $file_properties
   *   The properties of the file being validated.
   * @param string[] $extensions
   *   An array of the allowed file extensions.
   * @param string[] $expected_errors
   *   The expected error messages as string.
   *
   * @dataProvider providerTestFileValidateExtensionsOnUri
   * @covers ::validate
   */
  public function testFileExtensionOnUri(array $file_properties, array $extensions, array $expected_errors): void {
    $file = File::create($file_properties);
    // Test for failure.
    $validators = [
      'FileExtension' => [
        'extensions' => implode(' ', $extensions),
      ],
    ];
    $violations = $this->validator->validate($file, $validators);
    $actual_errors = [];
    foreach ($violations as $violation) {
      $actual_errors[] = $violation->getMessage();
    }
    $this->assertEquals($expected_errors, $actual_errors);
  }

  /**
   * Data provider for ::testFileExtensionOnUri.
   *
   * @return array[][]
   *   The test cases.
   */
  public static function providerTestFileValidateExtensionsOnUri(): array {
    $temporary_txt_file_properties = [
      'filename' => 'asdf.txt',
      'uri' => 'temporary://asdf',
      'status' => 0,
    ];
    $permanent_txt_file_properties = [
      'filename' => 'asdf.txt',
      'uri' => 'public://asdf_0.txt',
      'status' => 1,
    ];
    $permanent_png_file_properties = [
      'filename' => 'The Druplicon',
      'uri' => 'public://druplicon.png',
      'status' => 1,
    ];
    return [
      'Temporary txt validated with "asdf", "txt", "pork"' => [
        'file_properties' => $temporary_txt_file_properties,
        'extensions' => ['asdf', 'txt', 'pork'],
        'expected_errors' => [],
      ],
      'Temporary txt validated with "exe" and "png"' => [
        'file_properties' => $temporary_txt_file_properties,
        'extensions' => ['exe', 'png'],
        'expected_errors' => [
          'Only files with the following extensions are allowed: exe png.',
        ],
      ],
      'Permanent txt validated with "asdf", "txt", "pork"' => [
        'file_properties' => $permanent_txt_file_properties,
        'extensions' => ['asdf', 'txt', 'pork'],
        'expected_errors' => [],
      ],
      'Permanent txt validated with "exe" and "png"' => [
        'file_properties' => $permanent_txt_file_properties,
        'extensions' => ['exe', 'png'],
        'expected_errors' => [
          'Only files with the following extensions are allowed: exe png.',
        ],
      ],
      'Permanent png validated with "png", "gif", "jpg", "jpeg"' => [
        'file_properties' => $permanent_png_file_properties,
        'extensions' => ['png', 'gif', 'jpg', 'jpeg'],
        'expected_errors' => [],
      ],
      'Permanent png validated with "exe" and "txt"' => [
        'file_properties' => $permanent_png_file_properties,
        'extensions' => ['exe', 'txt'],
        'expected_errors' => [
          'Only files with the following extensions are allowed: exe txt.',
        ],
      ],
    ];
  }

}
