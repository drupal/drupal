<?php

declare(strict_types=1);

namespace Drupal\Tests\file\Kernel\Plugin\Validation\Constraint;

use Drupal\file\Entity\File;
use Drupal\Tests\file\Kernel\Validation\FileValidatorTestBase;

// cspell:ignore räme

/**
 * Tests the FileEncodingConstraintValidator.
 *
 * @group file
 * @coversDefaultClass \Drupal\file\Plugin\Validation\Constraint\FileEncodingConstraintValidator
 */
class FileEncodingConstraintValidatorTest extends FileValidatorTestBase {

  /**
   * Tests the FileEncodingConstraintValidator.
   *
   * @param array $file_properties
   *   The properties of the file being validated.
   * @param string[] $encodings
   *   An array of the allowed file encodings.
   * @param string[] $expected_errors
   *   The expected error messages as string.
   *
   * @dataProvider providerTestFileValidateEncodings
   * @covers ::validate
   */
  public function testFileEncodings(array $file_properties, array $encodings, array $expected_errors): void {
    $data = 'Räme';
    $data = mb_convert_encoding($data, $file_properties['encoding']);
    file_put_contents($file_properties['uri'], $data);
    $file = File::create($file_properties);
    // Test for failure.
    $validators = [
      'FileEncoding' => [
        'encodings' => $encodings,
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
   * Data provider for ::testFileEncoding.
   *
   * @return array[][]
   *   The test cases.
   */
  public static function providerTestFileValidateEncodings(): array {
    $utf8_encoded_txt_file_properties = [
      'filename' => 'druplicon.txt',
      'uri' => 'public://druplicon.txt',
      'status' => 0,
      'encoding' => 'UTF-8',
    ];
    $windows1252_encoded_txt_file = [
      'filename' => 'druplicon-win.txt',
      'uri' => 'public://druplicon-win.txt',
      'status' => 1,
      'encoding' => 'windows-1252',
    ];
    return [
      'UTF-8 encoded file validated with "UTF-8" encoding' => [
        'file_properties' => $utf8_encoded_txt_file_properties,
        'encodings' => ['UTF-8'],
        'expected_errors' => [],
      ],
      'Windows-1252 encoded file validated with "UTF-8" encoding' => [
        'file_properties' => $windows1252_encoded_txt_file,
        'encodings' => ['UTF-8'],
        'expected_errors' => [
          'The file is encoded with ASCII. It must be encoded with UTF-8',
        ],
      ],
    ];
  }

  /**
   * Helper function that returns a .po file with invalid encoding.
   */
  public function getInvalidEncodedPoFile() {
    return <<< EOF
msgid ""
msgstr ""
"Project-Id-Version: Drupal 8\\n"
"MIME-Version: 1.0\\n"
"Content-Type: text/plain; charset=Windows-1252\\n"
"Content-Transfer-Encoding: 8bit\\n"
"Plural-Forms: nplurals=2; plural=(n > 1);\\n"

msgid "Swamp"
msgstr "Räme"
EOF;
  }

}
