<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Uuid\UuidTest.
*/

namespace Drupal\system\Tests\Uuid;

use Drupal\Component\Uuid\Uuid;
use Drupal\simpletest\UnitTestBase;

/**
 * Tests the Drupal\Component\Uuid\Uuid class.
 */
class UuidUnitTest extends UnitTestBase {

  /**
   * The UUID object to be used for generating UUIDs.
   *
   * @var Drupal\Component\Uuid\UuidInterface
   */
  protected $uuid;

  public static function getInfo() {
    return array(
      'name' => 'UUID handling',
      'description' => "Test the handling of Universally Unique IDentifiers (UUIDs).",
      'group' => 'UUID',
    );
  }

  public function setUp() {
    // Initiate the generator. This will lazy-load uuid.inc.
    $this->uuid = new Uuid();
    parent::setUp();
  }

  /**
   * Test generating a UUID.
   */
  public function testGenerateUuid() {
    $uuid = $this->uuid->generate();
    $this->assertTrue($this->uuid->isValid($uuid), 'UUID generation works.');
  }

  /**
   * Test that generated UUIDs are unique.
   */
  public function testUuidIsUnique() {
    $uuid1 = $this->uuid->generate();
    $uuid2 = $this->uuid->generate();
    $this->assertNotEqual($uuid1, $uuid2, 'Same UUID was not generated twice.');
  }

  /**
   * Test UUID validation.
   */
  function testUuidValidation() {
    // These valid UUIDs.
    $uuid_fqdn = '6ba7b810-9dad-11d1-80b4-00c04fd430c8';
    $uuid_min = '00000000-0000-0000-0000-000000000000';
    $uuid_max = 'ffffffff-ffff-ffff-ffff-ffffffffffff';

    $this->assertTrue($this->uuid->isValid($uuid_fqdn), t('FQDN namespace UUID (@uuid) is valid', array('@uuid' => $uuid_fqdn)));
    $this->assertTrue($this->uuid->isValid($uuid_min), t('Minimum UUID value (@uuid) is valid', array('@uuid' => $uuid_min)));
    $this->assertTrue($this->uuid->isValid($uuid_max), t('Maximum UUID value (@uuid) is valid', array('@uuid' => $uuid_max)));

    // These are invalid UUIDs.
    $invalid_format = '0ab26e6b-f074-4e44-9da-601205fa0e976';
    $invalid_length = '0ab26e6b-f074-4e44-9daf-1205fa0e9761f';

    $this->assertFalse($this->uuid->isValid($invalid_format), t('@uuid is not a valid UUID', array('@uuid' => $invalid_format)));
    $this->assertFalse($this->uuid->isValid($invalid_length), t('@uuid is not a valid UUID', array('@uuid' => $invalid_length)));

  }
}
