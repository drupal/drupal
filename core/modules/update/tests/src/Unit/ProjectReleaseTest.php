<?php

namespace Drupal\Tests\update\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\update\ProjectRelease;

/**
 * @coversDefaultClass \Drupal\update\ProjectRelease
 *
 * @group update
 */
class ProjectReleaseTest extends UnitTestCase {

  /**
   * Tests creating with valid data.
   *
   * @param mixed[] $data
   *   The data to test. It will be combined with ::getValidData() results.
   * @param mixed[] $expected
   *   The values expected to be returned from the object methods.
   *
   * @covers ::createFromArray
   * @covers ::isInsecure
   * @covers ::isSecurityRelease
   * @covers ::isPublished
   * @covers ::isUnsupported
   * @covers ::isUnsupported
   *
   * @dataProvider providerCreateFromArray
   */
  public function testCreateFromArray(array $data, array $expected = []): void {
    $data += $this->getValidData();
    $expected += $data;
    // If not set provide default values that match ::getValidData().
    $expected += [
      'is_published' => TRUE,
      'is_unsupported' => TRUE,
      'is_security_release' => TRUE,
      'is_insecure' => TRUE,
    ];

    $release = ProjectRelease::createFromArray($data);

    $this->assertInstanceOf(ProjectRelease::class, $release);
    $this->assertSame($expected['version'], $release->getVersion());
    $this->assertSame($expected['date'], $release->getDate());
    $this->assertSame($expected['download_link'], $release->getDownloadUrl());
    $this->assertSame($expected['release_link'], $release->getReleaseUrl());
    $this->assertSame($expected['core_compatibility_message'], $release->getCoreCompatibilityMessage());
    $this->assertSame($expected['core_compatible'], $release->isCoreCompatible());
    $this->assertSame($expected['is_published'], $release->isPublished());
    $this->assertSame($expected['is_unsupported'], $release->isUnsupported());
    $this->assertSame($expected['is_security_release'], $release->isSecurityRelease());
    $this->assertSame($expected['is_insecure'], $release->isInsecure());
  }

  /**
   * Data provider for testCreateFromArray().
   *
   * @return mixed
   *   Test cases for testCreateFromArray().
   */
  public function providerCreateFromArray(): array {
    return [
      'default valid' => [
        'data' => [],
      ],
      'valid with extra field' => [
        'data' => ['extra' => 'This value is ignored and will not trigger a validation error.'],
      ],
      'no release types' => [
        'data' => [
          'terms' => [
            'Release type' => [],
          ],
        ],
        'expected' => [
          'is_unsupported' => FALSE,
          'is_security_release' => FALSE,
          'is_insecure' => FALSE,
        ],
      ],
      'unpublished' => [
        'data' => [
          'status' => 'unpublished',
        ],
        'expected' => [
          'is_published' => FALSE,
        ],
      ],
      'core_compatible false' => [
        'data' => [
          'core_compatible' => FALSE,
        ],
      ],
      'core_compatible NULL' => [
        'data' => [
          'core_compatible' => NULL,
        ],
      ],
    ];
  }

  /**
   * Tests that optional fields can be omitted.
   *
   * @covers ::createFromArray
   */
  public function testOptionalFields(): void {
    $data = $this->getValidData();
    unset(
      $data['core_compatible'],
      $data['core_compatibility_message'],
      $data['download_link'],
      $data['date'],
      $data['terms']
    );
    $release = ProjectRelease::createFromArray($data);
    $this->assertNull($release->isCoreCompatible());
    $this->assertNull($release->getCoreCompatibilityMessage());
    $this->assertNull($release->getDate());
    // Confirm that all getters that rely on 'terms' default to FALSE.
    $this->assertFalse($release->isSecurityRelease());
    $this->assertFalse($release->isInsecure());
    $this->assertFalse($release->isUnsupported());
  }

  /**
   * Tests exceptions with missing fields.
   *
   * @param string $missing_field
   *   The field to test.
   *
   * @covers ::createFromArray
   *
   * @dataProvider providerCreateFromArrayMissingField
   */
  public function testCreateFromArrayMissingField(string $missing_field): void {
    $data = $this->getValidData();
    unset($data[$missing_field]);
    $this->expectException(\UnexpectedValueException::class);
    $expected_message = 'Malformed release data:.*' . preg_quote("[$missing_field]:", '/');
    $expected_message .= '.*This field is missing';
    $this->expectExceptionMessageMatches("/$expected_message/s");
    ProjectRelease::createFromArray($data);
  }

  /**
   * Data provider for testCreateFromArrayMissingField().
   */
  public function providerCreateFromArrayMissingField(): array {
    return [
      'status' => ['status'],
      'version' => ['version'],
      'release_link' => ['release_link'],
    ];
  }

  /**
   * Tests exceptions for invalid field types.
   *
   * @param string $invalid_field
   *   The field to test for an invalid value.
   * @param mixed $invalid_value
   *   The invalid value to use in the field.
   * @param string $expected_message
   *   The expected message for the field.
   *
   * @covers ::createFromArray
   *
   * @dataProvider providerCreateFromArrayInvalidField
   */
  public function testCreateFromArrayInvalidField(string $invalid_field, $invalid_value, string $expected_message): void {
    $data = $this->getValidData();
    // Set the field a value that is not valid for any of the fields in the
    // feed.
    $data[$invalid_field] = $invalid_value;
    $this->expectException(\UnexpectedValueException::class);
    $expected_exception_message = 'Malformed release data:.*' . preg_quote("[$invalid_field]:", '/');
    $expected_exception_message .= ".*$expected_message";
    $this->expectExceptionMessageMatches("/$expected_exception_message/s");
    ProjectRelease::createFromArray($data);
  }

  /**
   * Data provider for testCreateFromArrayInvalidField().
   */
  public function providerCreateFromArrayInvalidField(): array {
    return [
      'status other' => [
        'invalid_field' => 'status',
        'invalid_value' => 'other',
        'expected_message' => 'The value you selected is not a valid choice.',
      ],
      'status non-string' => [
        'invalid_field' => 'status',
        'invalid_value' => new \stdClass(),
        'expected_message' => 'The value you selected is not a valid choice.',
      ],
      'terms non-array' => [
        'invalid_field' => 'terms',
        'invalid_value' => 'Unsupported',
        'expected_message' => 'This value should be of type array.',
      ],
      'version blank' => [
        'invalid_field' => 'version',
        'invalid_value' => '',
        'expected_message' => 'This value should not be blank.',
      ],
      'core_compatibility_message blank' => [
        'invalid_field' => 'core_compatibility_message',
        'invalid_value' => '',
        'expected_message' => 'This value should not be blank.',
      ],
      'download_link blank' => [
        'invalid_field' => 'download_link',
        'invalid_value' => '',
        'expected_message' => 'This value should not be blank.',
      ],
      'release_link blank' => [
        'invalid_field' => 'release_link',
        'invalid_value' => '',
        'expected_message' => 'This value should not be blank.',
      ],
      'date non-numeric' => [
        'invalid_field' => 'date',
        'invalid_value' => '2 weeks ago',
        'expected_message' => 'This value should be of type numeric.',
      ],
      'core_compatible string' => [
        'invalid_field' => 'core_compatible',
        'invalid_value' => 'Nope',
        'expected_message' => 'This value should be of type boolean.',
      ],
    ];
  }

  /**
   * Gets valid data for a project release.
   *
   * @return mixed[]
   *   The data for the project release.
   */
  protected function getValidData(): array {
    return [
      'status' => 'published',
      'release_link' => 'https://example.com/release-link',
      'version' => '8.0.0',
      'download_link' => 'https://example.com/download-link',
      'core_compatibility_message' => 'This is compatible',
      'date' => 1452229200,
      'terms' => [
        'Release type' => ['Security update', 'Unsupported', 'Insecure'],
      ],
      'core_compatible' => TRUE,
    ];
  }

}
