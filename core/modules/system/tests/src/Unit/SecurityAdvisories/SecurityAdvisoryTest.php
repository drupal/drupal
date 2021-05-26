<?php

namespace Drupal\Tests\system\Unit\SecurityAdvisories;

use Drupal\Tests\UnitTestCase;
use Drupal\system\SecurityAdvisories\SecurityAdvisory;

/**
 * @coversDefaultClass \Drupal\system\SecurityAdvisories\SecurityAdvisory
 *
 * @group system
 */
class SecurityAdvisoryTest extends UnitTestCase {

  /**
   * Tests creating with valid data.
   *
   * @param mixed[] $changes
   *   The changes to the valid data set to test.
   * @param mixed[] $expected
   *   The expected changes for the object methods.
   *
   * @covers ::createFromArray
   * @covers ::isCoreAdvisory
   * @covers ::isPsa
   *
   * @dataProvider providerCreateFromArray
   */
  public function testCreateFromArray(array $changes, array $expected = []): void {
    $data = $changes;
    $data += $this->getValidData();
    $expected += $data;

    $sa = SecurityAdvisory::createFromArray($data);
    $this->assertInstanceOf(SecurityAdvisory::class, $sa);
    $this->assertSame($expected['title'], $sa->getTitle());
    $this->assertSame($expected['project'], $sa->getProject());
    $this->assertSame($expected['type'], $sa->getProjectType());
    $this->assertSame($expected['link'], $sa->getUrl());
    $this->assertSame($expected['insecure'], $sa->getInsecureVersions());
    $this->assertSame($expected['is_psa'], $sa->isPsa());
    $this->assertSame($expected['type'] === 'core', $sa->isCoreAdvisory());
  }

  /**
   * Data provider for testCreateFromArray().
   */
  public function providerCreateFromArray(): array {
    return [
      // For 'is_psa' the return value should converted to any array.
      [
        ['is_psa' => 1],
        ['is_psa' => TRUE],
      ],
      [
        ['is_psa' => '1'],
        ['is_psa' => TRUE],
      ],
      [
        ['is_psa' => TRUE],
        ['is_psa' => TRUE],
      ],
      [
        ['is_psa' => 0],
        ['is_psa' => FALSE],
      ],
      [
        ['is_psa' => '0'],
        ['is_psa' => FALSE],
      ],
      [
        ['is_psa' => FALSE],
        ['is_psa' => FALSE],
      ],
      // Test cases that ensure ::isCoreAdvisory only returns TRUE for core.
      [
        ['type' => 'module'],
      ],
      [
        ['type' => 'theme'],
      ],
      [
        ['type' => 'core'],
      ],
    ];
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
    $expected_message = 'Malformed security advisory:.*' . preg_quote("[$missing_field]:", '/');
    $expected_message .= '.*This field is missing';
    $this->expectExceptionMessageMatches("/$expected_message/s");
    SecurityAdvisory::createFromArray($data);
  }

  /**
   * Data provider for testCreateFromArrayMissingField().
   */
  public function providerCreateFromArrayMissingField(): array {
    return [
      'title' => ['title'],
      'link' => ['link'],
      'project' => ['project'],
      'type' => ['type'],
      'is_psa' => ['is_psa'],
      'insecure' => ['insecure'],
    ];
  }

  /**
   * Tests exceptions for invalid field types.
   *
   * @param string $invalid_field
   *   The field to test for an invalid value.
   * @param string $expected_type_message
   *   The expected message for the field.
   *
   * @covers ::createFromArray
   *
   * @dataProvider providerCreateFromArrayInvalidField
   */
  public function testCreateFromArrayInvalidField(string $invalid_field, string $expected_type_message): void {
    $data = $this->getValidData();
    // Set the field a value that is not valid for any of the fields in the
    // feed.
    $data[$invalid_field] = new \stdClass();
    $this->expectException(\UnexpectedValueException::class);
    $expected_message = 'Malformed security advisory:.*' . preg_quote("[$invalid_field]:", '/');
    $expected_message .= ".*$expected_type_message";
    $this->expectExceptionMessageMatches("/$expected_message/s");
    SecurityAdvisory::createFromArray($data);
  }

  /**
   * Data provider for testCreateFromArrayInvalidField().
   */
  public function providerCreateFromArrayInvalidField(): array {
    return [
      'title' => ['title', 'This value should be of type string.'],
      'link' => ['link', 'This value should be of type string.'],
      'project' => ['project', 'This value should be of type string.'],
      'type' => ['type', 'This value should be of type string.'],
      'is_psa' => ['is_psa', 'The value you selected is not a valid choice.'],
      'insecure' => ['insecure', 'This value should be of type array.'],
    ];
  }

  /**
   * Gets valid data for a security advisory.
   *
   * @return mixed[]
   *   The data for the security advisory.
   */
  protected function getValidData(): array {
    return [
      'title' => 'Generic Module1 Test - Moderately critical - Access bypass - SA-CONTRIB-2019-02-02',
      'link' => 'https://www.drupal.org/SA-CONTRIB-2019-02-02',
      'project' => 'generic_module1_test',
      'type' => 'module',
      'is_psa' => FALSE,
      'insecure' => [
        '8.x-1.1',
      ],
      'pubDate' => 'Tue, 19 Mar 2019 12 => 50 => 00 +0000',
      // New fields added to the JSON feed should be ignored and not cause a
      // validation error.
      'unknown_field' => 'ignored value',
    ];
  }

}
